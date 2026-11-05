import { defineBlock } from "@javaabu/block-editor"

import type { MediaItem } from "../types"
import { GalleryRender } from "./render"
import { changeButton, h, insertPicked, mediaNode, pick, strings, updateAt } from "./shared"

export interface GalleryItem {
  id: number | null
  src: string
  thumb?: string | null
  alt?: string | null
  width?: number | null
  height?: number | null
}

export function galleryItems(media: MediaItem[]): GalleryItem[] {
  return media.map((item) => ({
    id: item.id,
    src: item.url,
    thumb: item.preview ?? item.thumb ?? item.url,
    alt: item.alt ?? "",
    width: item.width,
    height: item.height,
  }))
}

/** `gallery`: images from the media library in a grid. */
export const gallery = defineBlock({
  name: "gallery",
  extensions: ({ block }) => [
    mediaNode(
      block,
      "figure",
      (props, attrs) => {
        const items = Array.isArray(attrs.items) ? (attrs.items as GalleryItem[]) : []
        if (!items.length) return null
        const t = strings("gallery")
        return h(
          "figure",
          { class: "mp-block-gallery", style: `--mp-gallery-columns: ${Number(attrs.columns ?? 3)}` },
          h(
            "ul",
            {},
            ...items.map((item) =>
              h("li", {}, h("img", { src: item.thumb ?? item.src, alt: item.alt ?? "", draggable: "false" })),
            ),
          ),
          attrs.caption ? h("figcaption", { dir: "auto" }, String(attrs.caption)) : null,
          changeButton(t("change"), async () => {
            const selected = items
              .filter((item) => item.id)
              .map(
                (item) =>
                  ({
                    id: item.id,
                    name: item.alt || String(item.id),
                    thumb: item.thumb ?? item.src,
                  }) as unknown as MediaItem,
              )
            const chosen = await pick("gallery", { type: "image", multiple: true, selected })
            if (!chosen?.length) return
            const existing = new Map(items.map((item) => [item.id, item]))
            updateAt(props, {
              items: chosen
                .map((media) => (media.url ? galleryItems([media])[0] : existing.get(media.id)))
                .filter(Boolean),
            })
          }),
        )
      },
      (t) => t("choose_images"),
    ),
  ],
  insert: (editor, context) =>
    void insertPicked(
      editor,
      "gallery",
      { type: "image", multiple: true },
      (media) => ({ items: galleryItems(media) }),
      context,
    ),
  render: GalleryRender,
})
