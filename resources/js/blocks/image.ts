import { defineBlock } from "@javaabu/block-editor"

import type { MediaItem } from "../types"
import { ImageRender } from "./render"
import { changeButton, h, insertPicked, mediaNode, pick, strings, updateAt } from "./shared"

/** The attributes of an image block for a library image. */
export function imageAttrs(media: MediaItem): Record<string, unknown> {
  return {
    mediaId: media.id,
    src: media.preview ?? media.url,
    width: media.width,
    height: media.height,
    alt: media.alt ?? "",
  }
}

/** `image`: an image from the media library (replaces the core URL image block). */
export const image = defineBlock({
  name: "image",
  extensions: ({ block }) => [
    mediaNode(
      block,
      "figure",
      (props, attrs) => {
        if (!attrs.src) return null
        const t = strings("image")
        return h(
          "figure",
          { class: "block-image mp-block-image" },
          h("img", { src: String(attrs.src), alt: String(attrs.alt ?? ""), draggable: "false" }),
          attrs.caption ? h("figcaption", { dir: "auto" }, String(attrs.caption)) : null,
          changeButton(t("change"), async () => {
            const chosen = await pick("image", { type: "image" })
            if (chosen?.[0]) updateAt(props, imageAttrs(chosen[0]))
          }),
        )
      },
      (t) => t("choose_image"),
    ),
  ],
  insert: (editor, context) =>
    void insertPicked(editor, "image", { type: "image" }, (media) => imageAttrs(media[0] as MediaItem), context),
  render: ImageRender,
})
