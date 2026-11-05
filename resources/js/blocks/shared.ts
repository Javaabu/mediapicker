import { Node as TiptapNode, type AnyExtension, type Editor, type NodeViewRendererProps } from "@tiptap/core"
import {
  atomView,
  attributesFrom,
  dataAttrs,
  settingsPrompt,
  type BlockConfig,
  type InsertContext,
} from "@javaabu/block-editor"

import { h, icon } from "../dom"
import { openMediaPicker } from "../picker/dialog"
import { translator, type Translator } from "../strings"
import type { MediaItem } from "../types"
import type { PickerConfig } from "../react/context"

/** The block config from PHP, with the picker config `MediaBlock::toEditor()` adds. */
export type MediaBlockConfig = BlockConfig & { mediapicker?: PickerConfig }

const configs = new Map<string, MediaBlockConfig>()

/** The picker config of a block, kept when the editor builds its extension. */
export function pickerConfig(name: string): PickerConfig | undefined {
  return configs.get(name)?.mediapicker
}

export function strings(name: string): Translator {
  return translator(pickerConfig(name)?.strings)
}

/** Opens the library dialog for a block. */
export function pick(name: string, options: { multiple?: boolean; type?: string | null; selected?: MediaItem[] } = {}) {
  const config = pickerConfig(name)

  return openMediaPicker({
    url: config?.url ?? "",
    multiple: options.multiple ?? false,
    type: options.type ?? null,
    selected: options.selected ?? [],
    types: config?.types,
    accept: config?.accept,
    strings: config?.strings,
  })
}

/** Inserts a block at the selection with attributes from the chosen media. */
export async function insertPicked(
  editor: Editor,
  name: string,
  options: { multiple?: boolean; type?: string | null },
  toAttrs: (media: MediaItem[]) => Record<string, unknown>,
  context: InsertContext,
): Promise<void> {
  void context
  const chosen = await pick(name, options)
  if (!chosen?.length) return
  editor
    .chain()
    .focus()
    .insertContent({ type: name, attrs: toAttrs(chosen) })
    .run()
}

/** Sets attributes of the block at a position. */
export function updateAt(props: NodeViewRendererProps, attrs: Record<string, unknown>): void {
  const pos = typeof props.getPos === "function" ? props.getPos() : undefined
  if (typeof pos !== "number") return
  props.editor.chain().setNodeSelection(pos).updateAttributes(props.node.type.name, attrs).run()
}

/** A "Change" button in a block preview that picks again. */
export function changeButton(label: string, onClick: () => void): HTMLButtonElement {
  return h(
    "button",
    {
      type: "button",
      class: "mp-button mp-block-change",
      "data-be-stop": "",
      onClick: (event: Event) => {
        event.preventDefault()
        event.stopPropagation()
        onClick()
      },
    },
    icon("images"),
    label,
  )
}

/**
 * An atom node for a media block: attributes from PHP, stored as data attributes, and a
 * preview that picks media when empty
 */
export function mediaNode(
  block: BlockConfig,
  tag: string,
  preview: (props: NodeViewRendererProps, attrs: Record<string, unknown>) => globalThis.Node | null,
  emptyText: (t: Translator) => string,
): AnyExtension {
  configs.set(block.name, block as MediaBlockConfig)

  return TiptapNode.create({
    name: block.name,
    group: "block",
    atom: true,
    draggable: true,
    selectable: true,
    addAttributes: () => attributesFrom(block),
    parseHTML: () => [{ tag: `${tag}[data-block="${block.name}"]` }],
    renderHTML: ({ node }) => [tag, { class: `mp-block`, ...dataAttrs(block.name, node.attrs) }],
    addNodeView() {
      return (props) =>
        atomView(props, (node) => preview(props, node.attrs) ?? settingsPrompt(props, emptyText(strings(block.name))))
    },
  })
}

export { h, icon }
