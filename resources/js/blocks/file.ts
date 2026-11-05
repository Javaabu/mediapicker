import { defineBlock } from "@javaabu/block-editor"

import type { MediaItem } from "../types"
import { FileRender } from "./render"
import { changeButton, h, icon, insertPicked, mediaNode, pick, strings, updateAt } from "./shared"

export function fileAttrs(media: MediaItem): Record<string, unknown> {
  return {
    mediaId: media.id,
    href: media.url,
    fileName: media.file_name,
    size: media.human_size,
    mimeType: media.mime_type,
    label: media.name,
  }
}

/** `file`: a download link to a file from the media library. */
export const file = defineBlock({
  name: "file",
  extensions: ({ block }) => [
    mediaNode(
      block,
      "p",
      (props, attrs) => {
        if (!attrs.href) return null
        const t = strings("file")
        return h(
          "p",
          { class: "mp-block-file" },
          icon("file-text"),
          h("strong", { dir: "auto" }, String(attrs.label || attrs.fileName || "")),
          attrs.size ? h("small", { dir: "ltr" }, String(attrs.size)) : null,
          changeButton(t("change"), async () => {
            const chosen = await pick("file")
            if (chosen?.[0]) updateAt(props, fileAttrs(chosen[0]))
          }),
        )
      },
      (t) => t("choose_file"),
    ),
  ],
  insert: (editor, context) =>
    void insertPicked(editor, "file", {}, (media) => fileAttrs(media[0] as MediaItem), context),
  render: FileRender,
})
