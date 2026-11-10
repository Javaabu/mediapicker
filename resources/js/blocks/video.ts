import { defineBlock } from "@javaabu/block-editor"

import type { MediaItem } from "../types"
import { VideoRender } from "./render"
import { changeButton, h, insertPicked, mediaNode, pick, strings, updateAt } from "./shared"

export function videoAttrs(media: MediaItem): Record<string, unknown> {
  return { mediaId: media.id, src: media.url, mimeType: media.mime_type }
}

/** `mediaVideo`: an uploaded video from the media library. */
export const mediaVideo = defineBlock({
  name: "mediaVideo",
  extensions: ({ block }) => [
    mediaNode(
      block,
      "figure",
      (props, attrs) => {
        if (!attrs.src) return null
        const t = strings("mediaVideo")
        return h(
          "figure",
          { class: "mp-block-video" },
          h("video", { src: String(attrs.src), controls: true, preload: "metadata" }),
          attrs.caption ? h("figcaption", { dir: "auto" }, String(attrs.caption)) : null,
          changeButton(t("change"), async () => {
            const chosen = await pick("mediaVideo", { type: "video" })
            if (chosen?.[0]) updateAt(props, videoAttrs(chosen[0]))
          }),
        )
      },
      (t) => t("choose_video"),
    ),
  ],
  insert: (editor, context) =>
    void insertPicked(editor, "mediaVideo", { type: "video" }, (media) => videoAttrs(media[0] as MediaItem), context),
  render: VideoRender,
})
