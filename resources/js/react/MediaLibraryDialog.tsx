import { Alert, Button, Modal } from "@javaabu/forms"
import { MediaLister, type MediaItem as ListerItem } from "@javaabu/onu"
import { useMemo, useRef, useState } from "react"

import type { LibraryClient } from "../api"
import { defaultTitle } from "../picker/dialog"
import { translator } from "../strings"
import type { CropPreset, FileTypeOption, MediaItem, Strings } from "../types"
import { CropBeforeUpload } from "./CropBeforeUpload"
import { Icon } from "./Icon"
import { useLibrary } from "./useLibrary"

export interface MediaLibraryDialogProps {
  open: boolean
  onOpenChange: (open: boolean) => void
  /** Called with the chosen media when the user clicks Choose. */
  onChoose: (media: MediaItem[]) => void
  /** The library's index URL, e.g. `/admin/media`. */
  url: string
  multiple?: boolean
  /** Only these file types. */
  type?: string | string[] | null
  /** Already chosen; they start selected. */
  selected?: MediaItem[]
  /** File types to filter by, when `type` doesn't limit them. */
  types?: FileTypeOption[]
  /** Allow uploads. Default true. */
  upload?: boolean
  accept?: string
  /** Crop new images to this preset before they're uploaded. */
  cropOnUpload?: CropPreset | null
  title?: string
  strings?: Partial<Strings> | null
  client?: LibraryClient
}

function toListerItem(item: MediaItem): ListerItem {
  return {
    id: item.id,
    name: item.name,
    url: item.url,
    thumbnailUrl: item.thumb ?? undefined,
    alt: item.alt ?? "",
    type: item.mime_type,
    size: item.size,
  }
}

/**
 * The media library in a modal: search, a type filter, uploads with progress (and an
 * optional crop first), and single or multiple selection
 */
export function MediaLibraryDialog({
  open,
  onOpenChange,
  onChoose,
  url,
  multiple = false,
  type = null,
  selected = [],
  types = [],
  upload = true,
  accept,
  cropOnUpload = null,
  title,
  strings,
  client,
}: MediaLibraryDialogProps) {
  const t = useMemo(() => translator(strings), [strings])
  const library = useLibrary({ url, type, enabled: open, client })
  const [chosen, setChosen] = useState<Map<number, MediaItem>>(() => new Map(selected.map((item) => [item.id, item])))
  const [cropping, setCropping] = useState<File[]>([])
  const fileInput = useRef<HTMLInputElement>(null)
  const limited = useMemo(() => (type ? (Array.isArray(type) ? type : [type]) : []), [type])

  // Start from the field's value each time the dialog opens
  const [wasOpen, setWasOpen] = useState(open)
  if (open !== wasOpen) {
    setWasOpen(open)
    if (open) setChosen(new Map(selected.map((item) => [item.id, item])))
  }

  const select = (ids: Array<string | number>) => {
    const byId = new Map([...library.items, ...chosen.values()].map((item) => [item.id, item]))
    setChosen(
      new Map(
        ids.map((id) => [Number(id), byId.get(Number(id))]).filter((entry): entry is [number, MediaItem] => !!entry[1]),
      ),
    )
  }

  const add = (media: MediaItem | null) => {
    if (!media) return
    setChosen((current) => (multiple ? new Map(current).set(media.id, media) : new Map([[media.id, media]])))
  }

  const uploadFiles = (files: File[]) => {
    const images = cropOnUpload ? files.filter((file) => file.type.startsWith("image/")) : []
    const others = files.filter((file) => !images.includes(file))

    others.forEach((file) => void library.upload(file).then(add))
    if (images.length) setCropping(images)
  }

  const typeOptions = types.filter((option) => !limited.length || limited.includes(option.value))

  const toolbar = (
    <>
      {typeOptions.length > 1 ? (
        <select
          className="mp-input mp-select"
          aria-label={t("type")}
          value={library.typeFilter}
          onChange={(event) => library.setTypeFilter(event.target.value)}
        >
          <option value="">{t("all_types")}</option>
          {typeOptions.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      ) : null}
      {upload ? (
        <>
          <Button type="button" color="secondary" onClick={() => fileInput.current?.click()}>
            <Icon name="upload" /> {t("upload")}
          </Button>
          <input
            ref={fileInput}
            type="file"
            hidden
            multiple
            accept={accept}
            data-testid="mediapicker-upload"
            onChange={(event) => {
              uploadFiles(Array.from(event.target.files ?? []))
              event.target.value = ""
            }}
          />
        </>
      ) : null}
    </>
  )

  const current = cropping[0]

  return (
    <Modal
      open={open}
      onOpenChange={onOpenChange}
      size="xl"
      title={title ?? defaultTitle(t, limited, multiple)}
      footer={
        <div className="flex w-full flex-wrap items-center justify-between gap-2">
          <span className="text-sm text-muted-foreground" role="status">
            {chosen.size ? t("selected", { count: chosen.size }) : t("no_selection")}
          </span>
          <div className="flex gap-2">
            <Button type="button" color="secondary" onClick={() => onOpenChange(false)}>
              {t("cancel")}
            </Button>
            <Button
              type="button"
              disabled={!chosen.size}
              onClick={() => {
                onChoose(Array.from(chosen.values()))
                onOpenChange(false)
              }}
            >
              {t("choose")}
            </Button>
          </div>
        </div>
      }
    >
      <div className="mp-library flex flex-col gap-3">
        {library.error ? <Alert type="danger">{library.error}</Alert> : null}

        {library.uploads.length ? (
          <ul className="mp-uploads" aria-label={t("uploading")}>
            {library.uploads.map((item) => (
              <li key={item.key} className="mp-upload">
                <span dir="auto">{item.name}</span>
                <progress className="mp-progress" max={100} value={Math.round(item.progress * 100)} />
              </li>
            ))}
          </ul>
        ) : null}

        {current && cropOnUpload ? (
          <CropBeforeUpload
            file={current}
            preset={cropOnUpload}
            strings={strings}
            onDone={(crop) => {
              void library.upload(current, crop ? { crops: { [cropOnUpload.name]: crop } } : {}).then(add)
              setCropping((files) => files.slice(1))
            }}
          />
        ) : (
          <MediaLister
            items={library.items.map(toListerItem)}
            selectionMode={multiple ? "multiple" : "single"}
            selected={Array.from(chosen.keys())}
            onSelectedChange={select}
            search={library.search}
            onSearchChange={library.setSearch}
            searchThaana
            loading={library.loading}
            hasMore={library.hasMore}
            onLoadMore={library.loadMore}
            emptyMessage={t(library.search || library.typeFilter ? "no_matching_media" : "no_media")}
            toolbar={toolbar}
            label={t("media_library")}
          />
        )}
      </div>
    </Modal>
  )
}
