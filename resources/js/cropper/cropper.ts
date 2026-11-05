import Cropper from "cropperjs"
import "cropperjs/dist/cropper.css"

import { createLibraryClient, type LibraryClient } from "../api"
import { h } from "../dom"
import { translator } from "../strings"
import type { Crop, CropPreset, MediaItem, Strings } from "../types"

/** What the Blade edit page puts in `data-mediapicker-cropper`. */
export interface CropperConfig {
  /** The library's index URL; crops are saved to `{url}/{id}/crops/{preset}`. */
  url: string
  media: Pick<MediaItem, "id" | "url" | "name" | "width" | "height" | "crops">
  presets: CropPreset[]
  strings?: Partial<Strings>
}

/**
 * The Blade cropper: a preset per button, a crop box on the image (cropperjs), and Save,
 * which stores the crop without touching the original. Fires `mediapicker:cropped` with the
 * updated media.
 */
export class CropperWidget {
  private readonly t: ReturnType<typeof translator>
  private readonly client: LibraryClient
  private crops: Record<string, Crop>
  private preset: CropPreset
  private cropper: Cropper | null = null
  private readonly image: HTMLImageElement
  private readonly status: HTMLParagraphElement
  private readonly save: HTMLButtonElement
  private readonly presetButtons: HTMLButtonElement[]

  constructor(
    private readonly element: HTMLElement,
    private readonly config: CropperConfig,
    client?: LibraryClient,
  ) {
    this.t = translator(config.strings)
    this.client = client ?? createLibraryClient(config.url)
    this.crops = { ...(config.media.crops ?? {}) }
    this.preset = config.presets[0] as CropPreset

    this.image = h("img", { src: config.media.url, alt: config.media.name, crossorigin: "anonymous" })
    this.status = h("p", { class: "mp-status", role: "status", "aria-live": "polite" })
    this.save = h(
      "button",
      { type: "button", class: "mp-button mp-button--primary", onClick: () => void this.store() },
      this.t("save_crop"),
    )
    this.presetButtons = config.presets.map((preset) =>
      h(
        "button",
        {
          type: "button",
          class: "mp-button",
          "aria-pressed": preset.name === this.preset.name ? "true" : "false",
          "data-preset": preset.name,
          onClick: () => this.choose(preset),
        },
        preset.label,
      ),
    )

    element.classList.add("mp-cropper")
    element.replaceChildren(
      h(
        "div",
        { class: "mp-cropper__presets", role: "group", "aria-label": this.t("aspect_ratio") },
        ...this.presetButtons,
      ),
      h("div", { class: "mp-cropper__stage" }, this.image),
      h(
        "div",
        { class: "mp-cropper__controls" },
        this.save,
        h("button", { type: "button", class: "mp-button", onClick: () => this.reset() }, this.t("reset")),
        this.status,
      ),
    )

    this.cropper = new Cropper(this.image, {
      viewMode: 1,
      autoCropArea: 1,
      zoomable: false,
      rotatable: false,
      scalable: false,
      aspectRatio: this.preset.aspect ?? NaN,
      ready: () => this.applySavedCrop(),
    })

    this.describe()
  }

  /** Switch to another preset: its aspect ratio and its saved crop. */
  choose(preset: CropPreset): void {
    this.preset = preset
    this.presetButtons.forEach((button) =>
      button.setAttribute("aria-pressed", button.dataset.preset === preset.name ? "true" : "false"),
    )
    this.cropper?.setAspectRatio(preset.aspect ?? NaN)
    this.applySavedCrop()
    this.describe()
  }

  /** The current crop box, in source pixels. */
  currentCrop(): Crop {
    const data = this.cropper?.getData(true)

    return {
      x: Math.max(0, data?.x ?? 0),
      y: Math.max(0, data?.y ?? 0),
      width: data?.width ?? this.config.media.width ?? 1,
      height: data?.height ?? this.config.media.height ?? 1,
      rotation: 0,
    }
  }

  async store(): Promise<MediaItem | null> {
    this.save.disabled = true
    this.status.textContent = this.t("saving")

    try {
      const media = await this.client.crop(this.config.media.id, this.preset.name, this.currentCrop())
      this.crops = { ...media.crops }
      this.status.textContent = this.t("saved")
      this.element.dispatchEvent(
        new CustomEvent("mediapicker:cropped", { bubbles: true, detail: { media, preset: this.preset.name } }),
      )
      return media
    } catch {
      this.status.textContent = this.t("error")
      return null
    } finally {
      this.save.disabled = false
    }
  }

  reset(): void {
    this.cropper?.reset()
  }

  destroy(): void {
    this.cropper?.destroy()
    this.cropper = null
  }

  private applySavedCrop(): void {
    const crop = this.crops[this.preset.name]
    if (crop) this.cropper?.setData({ x: crop.x, y: crop.y, width: crop.width, height: crop.height })
  }

  private describe(): void {
    this.status.textContent = this.crops[this.preset.name] ? this.t("cropped") : this.t("not_cropped")
  }
}

const mounted = new WeakMap<HTMLElement, CropperWidget>()

/** Mounts every `[data-mediapicker-cropper]` inside the root that isn't mounted yet. */
export function mountCroppers(root: ParentNode = document): CropperWidget[] {
  const widgets: CropperWidget[] = []

  root.querySelectorAll<HTMLElement>("[data-mediapicker-cropper]").forEach((element) => {
    if (mounted.has(element)) return

    const widget = new CropperWidget(element, JSON.parse(element.dataset.mediapickerCropper || "{}") as CropperConfig)
    mounted.set(element, widget)
    widgets.push(widget)
  })

  return widgets
}
