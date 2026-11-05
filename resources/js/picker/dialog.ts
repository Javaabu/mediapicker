import { createLibraryClient, LibraryError, type LibraryClient } from "../api"
import { debounce, documentDirection, h, icon } from "../dom"
import { translator } from "../strings"
import type { FileTypeOption, MediaItem, Strings } from "../types"

export interface MediaPickerOptions {
  /** The library's index URL, e.g. `/admin/media`. */
  url: string
  /** Choose several. Default false. */
  multiple?: boolean
  /** Only these file types (`image`, `video`, …). */
  type?: string | string[] | null
  /** Already chosen (they start selected). */
  selected?: MediaItem[]
  /** The file types to filter by, when `type` doesn't limit them. */
  types?: FileTypeOption[]
  /** Allow uploading from the dialog. Default true. */
  upload?: boolean
  /** The `accept` attribute of the upload input. */
  accept?: string
  /** The dialog's title. Default "Choose media". */
  title?: string
  strings?: Partial<Strings> | null
  /** A client to use instead of one for `url` (tests, custom APIs). */
  client?: LibraryClient
}

/**
 * Opens the media library in a modal dialog. Resolves with the chosen media, or null when
 * cancelled. Framework-free: the Blade picker field and the block-editor blocks use it.
 */
export function openMediaPicker(options: MediaPickerOptions): Promise<MediaItem[] | null> {
  return new Promise((resolve) => {
    const picker = new MediaPickerDialog(options, resolve)
    picker.open()
  })
}

/** "Choose an image", "Choose images", "Choose a video", or "Choose media" */
export function defaultTitle(t: ReturnType<typeof translator>, types: string[], multiple: boolean): string {
  if (types.length === 1 && types[0] === "image") return t(multiple ? "choose_images" : "choose_image")
  if (types.length === 1 && types[0] === "video" && !multiple) return t("choose_video")
  if (types.length === 1 && types[0] === "document" && !multiple) return t("choose_file")
  return t("choose_media")
}

class MediaPickerDialog {
  private readonly client: LibraryClient
  private readonly t: ReturnType<typeof translator>
  private readonly multiple: boolean
  private readonly types: string[]
  private items: MediaItem[] = []
  private selected = new Map<number, MediaItem>()
  private page = 1
  private lastPage = 1
  private search = ""
  private typeFilter = ""
  private loading = false
  private request: AbortController | null = null
  private resolved = false

  private readonly dialog: HTMLDialogElement
  private readonly list: HTMLDivElement
  private readonly status: HTMLParagraphElement
  private readonly error: HTMLDivElement
  private readonly more: HTMLButtonElement
  private readonly choose: HTMLButtonElement
  private readonly uploads: HTMLUListElement

  constructor(
    private readonly options: MediaPickerOptions,
    private readonly resolve: (media: MediaItem[] | null) => void,
  ) {
    this.client = options.client ?? createLibraryClient(options.url)
    this.t = translator(options.strings)
    this.multiple = options.multiple ?? false
    this.types = options.type ? (Array.isArray(options.type) ? options.type : [options.type]) : []

    for (const item of options.selected ?? []) this.selected.set(item.id, item)

    const title = options.title ?? defaultTitle(this.t, this.types, this.multiple)
    const titleId = `mp-title-${Math.random().toString(36).slice(2)}`

    this.list = h("div", { class: "mp-grid", role: "group", "aria-label": this.t("media_library") })
    this.status = h("p", { class: "mp-status", role: "status", "aria-live": "polite" })
    this.error = h("div", { class: "mp-error", role: "alert", hidden: true })
    this.more = h(
      "button",
      { type: "button", class: "mp-button mp-more", hidden: true, onClick: () => this.load(this.page + 1) },
      this.t("load_more"),
    )
    this.choose = h(
      "button",
      { type: "button", class: "mp-button mp-button--primary", disabled: true, onClick: () => this.finish() },
      this.t("choose"),
    )
    this.uploads = h("ul", { class: "mp-uploads", "aria-label": this.t("uploading") })

    this.dialog = h(
      "dialog",
      { class: "mp-dialog", "aria-labelledby": titleId, dir: documentDirection() },
      h(
        "div",
        { class: "mp-header" },
        h("h2", { id: titleId, class: "mp-title" }, title),
        h(
          "button",
          { type: "button", class: "mp-icon-button", "aria-label": this.t("close"), onClick: () => this.cancel() },
          icon("x"),
        ),
      ),
      this.toolbar(),
      this.uploads,
      this.error,
      h("div", { class: "mp-body" }, this.list, this.more),
      h(
        "div",
        { class: "mp-footer" },
        this.status,
        h(
          "div",
          { class: "mp-actions" },
          h("button", { type: "button", class: "mp-button", onClick: () => this.cancel() }, this.t("cancel")),
          this.choose,
        ),
      ),
    )

    this.dialog.addEventListener("cancel", (event) => {
      event.preventDefault()
      this.cancel()
    })
  }

  open(): void {
    document.body.append(this.dialog)
    this.dialog.showModal()
    this.updateSelection()
    void this.load(1)
    this.dialog.querySelector<HTMLInputElement>(".mp-search input")?.focus()
  }

  private toolbar(): HTMLElement {
    const search = h("input", {
      type: "search",
      class: "mp-input",
      placeholder: this.t("search_media"),
      "aria-label": this.t("search_media"),
      dir: "auto",
    })
    search.addEventListener(
      "input",
      debounce(() => {
        this.search = search.value.trim()
        void this.load(1)
      }),
    )

    const filters: HTMLElement[] = [h("label", { class: "mp-search" }, icon("search"), search)]

    const typeOptions = (this.options.types ?? []).filter((type) => !this.types.length || this.types.includes(type.value))
    if (typeOptions.length > 1) {
      const select = h(
        "select",
        { class: "mp-input mp-select", "aria-label": this.t("type") },
        h("option", { value: "" }, this.t("all_types")),
        ...typeOptions.map((type) => h("option", { value: type.value }, type.label)),
      )
      select.addEventListener("change", () => {
        this.typeFilter = select.value
        void this.load(1)
      })
      filters.push(select)
    }

    if (this.options.upload !== false) {
      const input = h("input", {
        type: "file",
        class: "mp-file-input",
        multiple: true,
        accept: this.options.accept ?? null,
        tabindex: -1,
        "aria-hidden": "true",
      })
      input.addEventListener("change", () => {
        const files = Array.from(input.files ?? [])
        input.value = ""
        files.forEach((file) => void this.upload(file))
      })
      filters.push(
        h(
          "button",
          { type: "button", class: "mp-button", onClick: () => input.click() },
          icon("upload"),
          this.t("upload"),
        ),
        input,
      )
    }

    return h("div", { class: "mp-toolbar" }, ...filters)
  }

  private async load(page: number): Promise<void> {
    this.request?.abort()
    this.request = new AbortController()
    this.loading = true
    this.showError(null)
    this.dialog.setAttribute("aria-busy", "true")

    try {
      const type = this.typeFilter ? [this.typeFilter] : this.types
      const result = await this.client.list(
        { search: this.search, type, page, per_page: 24, orderby: "created_at", order: "desc" },
        this.request.signal,
      )

      this.items = page === 1 ? result.data : [...this.items, ...result.data]
      this.page = result.meta.current_page
      this.lastPage = result.meta.last_page
      this.render()
    } catch (error) {
      if ((error as Error).name !== "AbortError") this.showError(this.t("error"))
    } finally {
      this.loading = false
      this.dialog.removeAttribute("aria-busy")
    }
  }

  private async upload(file: File): Promise<void> {
    const progress = h("progress", { max: 100, value: 0, class: "mp-progress" })
    const row = h("li", { class: "mp-upload" }, h("span", { dir: "auto" }, file.name), progress)
    this.uploads.append(row)

    try {
      const media = await this.client.upload(file, {
        type: this.types.length ? this.types : null,
        onProgress: (fraction) => (progress.value = Math.round(fraction * 100)),
      })

      row.remove()
      this.items = [media, ...this.items.filter((item) => item.id !== media.id)]
      this.select(media)
      this.render()
    } catch (error) {
      row.remove()
      const message =
        error instanceof LibraryError ? (Object.values(error.errors)[0]?.[0] ?? error.message) : this.t("upload_failed")
      this.showError(`${file.name}: ${message}`)
    }
  }

  private render(): void {
    this.list.replaceChildren(...this.items.map((item) => this.item(item)))

    if (!this.items.length) {
      this.list.append(h("p", { class: "mp-empty" }, this.t(this.search || this.typeFilter ? "no_matching_media" : "no_media")))
    }

    this.more.hidden = this.page >= this.lastPage
    this.updateSelection()
  }

  private item(item: MediaItem): HTMLElement {
    const pressed = this.selected.has(item.id)
    const thumb = item.thumb
      ? h("img", { src: item.thumb, alt: "", loading: "lazy", class: "mp-thumb" })
      : h("span", { class: "mp-thumb mp-thumb--icon" }, icon(item.icon))

    const button = h(
      "button",
      {
        type: "button",
        class: "mp-item",
        "aria-pressed": pressed ? "true" : "false",
        title: item.name,
        "data-id": item.id,
      },
      thumb,
      h("span", { class: "mp-name", dir: "auto" }, item.name),
      h("span", { class: "mp-check" }, icon("check")),
    )

    button.addEventListener("click", () => {
      this.toggle(item)
      this.render()
      this.list.querySelector<HTMLButtonElement>(`[data-id="${item.id}"]`)?.focus()
    })
    button.addEventListener("dblclick", () => {
      if (this.multiple) return
      this.selected = new Map([[item.id, item]])
      this.finish()
    })

    return button
  }

  private toggle(item: MediaItem): void {
    if (this.selected.has(item.id)) {
      this.selected.delete(item.id)
      return
    }
    this.select(item)
  }

  private select(item: MediaItem): void {
    if (!this.multiple) this.selected.clear()
    this.selected.set(item.id, item)
  }

  private updateSelection(): void {
    const count = this.selected.size
    this.choose.disabled = count === 0
    this.announce(count ? this.t("selected", { count }) : this.t("no_selection"))
  }

  private announce(message: string): void {
    if (message) this.status.textContent = message
  }

  private showError(message: string | null): void {
    this.error.hidden = !message
    this.error.textContent = message ?? ""
  }

  private finish(): void {
    this.close(Array.from(this.selected.values()))
  }

  private cancel(): void {
    this.close(null)
  }

  private close(result: MediaItem[] | null): void {
    if (this.resolved) return
    this.resolved = true
    this.request?.abort()
    this.dialog.close()
    this.dialog.remove()
    this.resolve(result)
  }
}
