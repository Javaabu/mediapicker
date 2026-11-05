import { h, icon } from "../dom"
import { translator } from "../strings"
import type { FileTypeOption, MediaItem, Strings } from "../types"
import { openMediaPicker, type MediaPickerOptions } from "./dialog"

/** What `<x-mediapicker::picker>` puts in `data-mediapicker-field`. */
export interface PickerFieldConfig {
  /** The input name; `images[]` for several. */
  name: string
  multiple: boolean
  type: string[]
  url: string
  value: MediaItem[]
  types?: FileTypeOption[]
  accept?: string
  upload?: boolean
  disabled?: boolean
  strings?: Partial<Strings>
}

const mounted = new WeakMap<HTMLElement, PickerField>()

/**
 * The Blade picker field: chosen media with their previews, hidden inputs with their ids,
 * and buttons to choose, reorder and remove. Fires `change` (bubbling) when the value changes.
 */
export class PickerField {
  private value: MediaItem[]
  private readonly t: ReturnType<typeof translator>
  private readonly items: HTMLUListElement
  private readonly inputs: HTMLElement
  private readonly clear: HTMLButtonElement | null

  constructor(
    private readonly element: HTMLElement,
    private readonly config: PickerFieldConfig,
  ) {
    this.value = config.value ?? []
    this.t = translator(config.strings)
    this.items = element.querySelector<HTMLUListElement>(".mp-field__items") ?? element.appendChild(h("ul", { class: "mp-field__items" }))
    this.inputs = element.querySelector<HTMLElement>(".mp-field__inputs") ?? element.appendChild(h("div", { class: "mp-field__inputs" }))
    this.clear = element.querySelector<HTMLButtonElement>("[data-mp-clear]")

    element.querySelector<HTMLButtonElement>("[data-mp-choose]")?.addEventListener("click", () => void this.choose())
    this.clear?.addEventListener("click", () => this.set([]))

    this.render()
  }

  getValue(): MediaItem[] {
    return this.value
  }

  set(value: MediaItem[]): void {
    this.value = this.config.multiple ? value : value.slice(0, 1)
    this.render()
    this.element.dispatchEvent(new CustomEvent("change", { bubbles: true, detail: { value: this.value } }))
  }

  async choose(): Promise<void> {
    const options: MediaPickerOptions = {
      url: this.config.url,
      multiple: this.config.multiple,
      type: this.config.type,
      types: this.config.types,
      accept: this.config.accept,
      upload: this.config.upload,
      strings: this.config.strings,
      selected: this.value,
    }

    const chosen = await openMediaPicker(options)

    if (chosen) this.set(chosen)

    this.element.querySelector<HTMLButtonElement>("[data-mp-choose]")?.focus()
  }

  private render(): void {
    const disabled = this.config.disabled ?? false

    this.items.replaceChildren(...this.value.map((item, index) => this.item(item, index, disabled)))

    const base = this.config.name.replace(/\[\]$/, "")
    const inputs = this.value.length
      ? this.value.map((item) => h("input", { type: "hidden", name: this.config.name, value: item.id }))
      : // an empty value, so the request still says the field was cleared
        [h("input", { type: "hidden", name: base, value: "" })]

    this.inputs.replaceChildren(...inputs)

    if (this.clear) this.clear.hidden = !this.value.length || disabled

    const empty = this.element.querySelector<HTMLElement>(".mp-field__empty")
    if (empty) empty.hidden = this.value.length > 0
  }

  private item(item: MediaItem, index: number, disabled: boolean): HTMLLIElement {
    const actions: HTMLElement[] = []

    if (this.config.multiple && !disabled) {
      actions.push(
        h(
          "button",
          {
            type: "button",
            class: "mp-icon-button",
            "aria-label": `${this.t("move_up")}: ${item.name}`,
            disabled: index === 0,
            onClick: () => this.move(index, -1),
          },
          icon("arrow-up"),
        ),
        h(
          "button",
          {
            type: "button",
            class: "mp-icon-button",
            "aria-label": `${this.t("move_down")}: ${item.name}`,
            disabled: index === this.value.length - 1,
            onClick: () => this.move(index, 1),
          },
          icon("arrow-down"),
        ),
      )
    }

    if (!disabled) {
      actions.push(
        h(
          "button",
          {
            type: "button",
            class: "mp-icon-button mp-button--danger",
            "aria-label": `${this.t("remove")}: ${item.name}`,
            onClick: () => this.set(this.value.filter((other) => other.id !== item.id)),
          },
          icon("x"),
        ),
      )
    }

    return h(
      "li",
      { class: "mp-field__item", "data-id": item.id },
      item.thumb
        ? h("img", { src: item.thumb, alt: item.alt ?? "", class: "mp-thumb", loading: "lazy" })
        : h("span", { class: "mp-thumb mp-thumb--icon" }, icon(item.icon)),
      h("span", { class: "mp-name", dir: "auto", title: item.name }, item.name),
      actions.length ? h("span", { class: "mp-field__item-actions" }, ...actions) : null,
    )
  }

  private move(index: number, by: number): void {
    const value = [...this.value]
    const [item] = value.splice(index, 1)
    if (!item) return
    value.splice(index + by, 0, item)
    this.set(value)
  }
}

/** Mounts every `[data-mediapicker-field]` inside the root that isn't mounted yet. */
export function mountPickerFields(root: ParentNode = document): PickerField[] {
  const fields: PickerField[] = []

  root.querySelectorAll<HTMLElement>("[data-mediapicker-field]").forEach((element) => {
    if (mounted.has(element)) return

    const config = JSON.parse(element.dataset.mediapickerField || "{}") as PickerFieldConfig
    const field = new PickerField(element, config)

    mounted.set(element, field)
    fields.push(field)
  })

  return fields
}

/** The mounted field of an element, to read or set its value from other scripts. */
export function pickerField(element: HTMLElement): PickerField | undefined {
  return mounted.get(element)
}
