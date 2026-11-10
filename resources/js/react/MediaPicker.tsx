import { Button } from "@javaabu/forms"
import { useMemo, useState } from "react"

import type { LibraryClient } from "../api"
import { translator } from "../strings"
import type { CropPreset, FileTypeOption, MediaItem, Strings } from "../types"
import { Icon } from "./Icon"
import { MediaLibraryDialog } from "./MediaLibraryDialog"

type Value = MediaItem | MediaItem[] | null

export interface MediaPickerProps {
  /** The chosen media: one item (or null), or a list with `multiple`. */
  value: Value
  onChange: (value: Value) => void
  /** The library's index URL, e.g. `/admin/media`. */
  url: string
  multiple?: boolean
  /** Only these file types (`image`, `video`, …). */
  type?: string | string[] | null
  /** Hidden inputs with this name carry the ids in a plain form submit (`images[]` for several). */
  name?: string
  /** The input id, for a label's `htmlFor`. */
  id?: string
  disabled?: boolean
  invalid?: boolean
  /** Ids of elements that describe the field (help, errors). */
  describedBy?: string
  types?: FileTypeOption[]
  upload?: boolean
  accept?: string
  cropOnUpload?: CropPreset | null
  strings?: Partial<Strings> | null
  client?: LibraryClient
}

/**
 * A field value of library media: previews of the chosen media, and buttons to choose
 * (in the library dialog), reorder and remove. Controlled with `value` / `onChange`.
 */
export function MediaPicker({
  value,
  onChange,
  url,
  multiple = false,
  type = null,
  name,
  id,
  disabled = false,
  invalid = false,
  describedBy,
  types,
  upload,
  accept,
  cropOnUpload,
  strings,
  client,
}: MediaPickerProps) {
  const t = useMemo(() => translator(strings), [strings])
  const [open, setOpen] = useState(false)
  const items = value === null ? [] : Array.isArray(value) ? value : [value]

  const set = (next: MediaItem[]) => onChange(multiple ? next : (next[0] ?? null))

  const move = (index: number, by: number) => {
    const next = [...items]
    const [item] = next.splice(index, 1)
    if (!item) return
    next.splice(index + by, 0, item)
    set(next)
  }

  const base = name?.replace(/\[\]$/, "")

  return (
    <div className="mp-field" aria-invalid={invalid || undefined}>
      {name ? (
        items.length ? (
          items.map((item) => <input key={item.id} type="hidden" name={name} value={item.id} />)
        ) : (
          <input type="hidden" name={base} value="" />
        )
      ) : null}

      {items.length ? (
        <ul className="mp-field__items">
          {items.map((item, index) => (
            <li key={item.id} className="mp-field__item" data-id={item.id}>
              {item.thumb ? (
                <img src={item.thumb} alt={item.alt ?? ""} className="mp-thumb" loading="lazy" />
              ) : (
                <span className="mp-thumb mp-thumb--icon">
                  <Icon name={item.icon} />
                </span>
              )}
              <span className="mp-name" dir="auto" title={item.name}>
                {item.name}
              </span>
              {!disabled ? (
                <span className="mp-field__item-actions">
                  {multiple ? (
                    <>
                      <button
                        type="button"
                        className="mp-icon-button"
                        aria-label={`${t("move_up")}: ${item.name}`}
                        disabled={index === 0}
                        onClick={() => move(index, -1)}
                      >
                        <Icon name="arrow-up" />
                      </button>
                      <button
                        type="button"
                        className="mp-icon-button"
                        aria-label={`${t("move_down")}: ${item.name}`}
                        disabled={index === items.length - 1}
                        onClick={() => move(index, 1)}
                      >
                        <Icon name="arrow-down" />
                      </button>
                    </>
                  ) : null}
                  <button
                    type="button"
                    className="mp-icon-button mp-button--danger"
                    aria-label={`${t("remove")}: ${item.name}`}
                    onClick={() => set(items.filter((other) => other.id !== item.id))}
                  >
                    <Icon name="x" />
                  </button>
                </span>
              ) : null}
            </li>
          ))}
        </ul>
      ) : (
        <p className="mp-field__empty">{t("no_selection")}</p>
      )}

      <div className="mp-field__buttons">
        <Button
          type="button"
          color="secondary"
          id={id}
          disabled={disabled}
          aria-describedby={describedBy}
          aria-invalid={invalid || undefined}
          onClick={() => setOpen(true)}
        >
          <Icon name={multiple ? "images" : "image"} /> {items.length ? t("change") : t("choose")}
        </Button>
        {items.length && !disabled ? (
          <Button type="button" color="link" onClick={() => set([])}>
            {t("remove")}
          </Button>
        ) : null}
      </div>

      <MediaLibraryDialog
        open={open}
        onOpenChange={setOpen}
        onChoose={set}
        url={url}
        multiple={multiple}
        type={type}
        selected={items}
        types={types}
        upload={upload}
        accept={accept}
        cropOnUpload={cropOnUpload}
        strings={strings}
        client={client}
      />
    </div>
  )
}
