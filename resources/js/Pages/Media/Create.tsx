import "../../../css/mediapicker.css"
import { Alert, Button, LinkButton } from "@javaabu/forms"
import { Link } from "@inertiajs/react"
import { useMemo, useRef, useState } from "react"

import { createLibraryClient, LibraryError } from "../../api"
import { formatBytes, translator } from "../../strings"
import type { MediaCreateProps, MediaItem } from "../../types"
import { Flash } from "../../react/Flash"
import { Icon } from "../../react/Icon"

interface Row {
  key: string
  name: string
  progress: number
  media?: MediaItem
  error?: string
}

/** mediapicker::Media/Create: upload one or more files, with progress, then edit them */
export default function Create(props: MediaCreateProps) {
  const t = useMemo(() => translator(props.strings), [props.strings])
  const client = useMemo(() => createLibraryClient(props.urls.index), [props.urls.index])
  const [rows, setRows] = useState<Row[]>([])
  const input = useRef<HTMLInputElement>(null)

  const update = (key: string, changes: Partial<Row>) =>
    setRows((current) => current.map((row) => (row.key === key ? { ...row, ...changes } : row)))

  const upload = (files: File[]) => {
    for (const file of files) {
      const key = `${file.name}-${Date.now()}-${Math.random()}`
      setRows((current) => [{ key, name: file.name, progress: 0 }, ...current])

      client
        .upload(file, { onProgress: (progress) => update(key, { progress }) })
        .then((media) => update(key, { media, progress: 1 }))
        .catch((error: unknown) =>
          update(key, {
            error:
              error instanceof LibraryError
                ? (Object.values(error.errors)[0]?.[0] ?? error.message)
                : t("upload_failed"),
          }),
        )
    }
  }

  return (
    <div className="mp-library flex flex-col gap-6" data-mediapicker>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">
          <bdi>{props.title}</bdi>
        </h1>
        <LinkButton url={props.urls.index} color="secondary" size="sm" startIcon="chevron-left">
          {t("back_to_library")}
        </LinkButton>
      </div>

      <Flash />

      <div
        className="flex flex-col items-center gap-3 rounded-lg border-2 border-dashed p-8 text-center"
        onDragOver={(event) => event.preventDefault()}
        onDrop={(event) => {
          event.preventDefault()
          upload(Array.from(event.dataTransfer.files))
        }}
      >
        <Icon name="upload" className="mp-icon h-8 w-8" />
        <p>{t("drop_files")}</p>
        <p className="text-sm text-muted-foreground">
          {t("max_size", { size: formatBytes(props.upload.max_size * 1024) })}
        </p>
        <Button type="button" onClick={() => input.current?.click()}>
          {t("choose_files")}
        </Button>
        <input
          ref={input}
          type="file"
          multiple
          hidden
          accept={props.upload.accept}
          aria-label={t("choose_files")}
          onChange={(event) => {
            upload(Array.from(event.target.files ?? []))
            event.target.value = ""
          }}
        />
      </div>

      {rows.length ? (
        <ul className="flex flex-col gap-2" aria-label={t("upload_files")}>
          {rows.map((row) => (
            <li key={row.key} className="flex flex-wrap items-center gap-3 rounded-md border p-3">
              <bdi className="flex-1 truncate">{row.name}</bdi>
              {row.error ? (
                <Alert type="danger" className="m-0">
                  {row.error}
                </Alert>
              ) : row.media?.links ? (
                <Link href={row.media.links.edit} className="underline">
                  {t("edit")}
                </Link>
              ) : (
                <progress
                  className="mp-progress"
                  max={100}
                  value={Math.round(row.progress * 100)}
                  aria-label={row.name}
                />
              )}
            </li>
          ))}
        </ul>
      ) : null}
    </div>
  )
}
