import { Button, Card, LinkButton, TextEntry } from "@javaabu/forms"
import { router } from "@inertiajs/react"
import { useMemo, useState } from "react"

import { translator } from "../../strings"
import type { MediaShowProps } from "../../types"
import { Flash } from "../../react/Flash"
import { Icon } from "../../react/Icon"
import { MediaPreview } from "../../react/MediaPreview"

/** mediapicker::Media/Show: a library media with its details and URL */
export default function Show(props: MediaShowProps) {
  const { media, urls, can } = props
  const t = useMemo(() => translator(props.strings), [props.strings])
  const [copied, setCopied] = useState(false)

  const copy = async () => {
    await navigator.clipboard?.writeText(media.url)
    setCopied(true)
    setTimeout(() => setCopied(false), 2000)
  }

  const destroy = () => {
    if (!urls.destroy || !window.confirm(t("delete_confirm"))) return
    router.delete(urls.destroy)
  }

  const uploaded = media.created_at ? new Date(media.created_at).toLocaleString(document.documentElement.lang || undefined) : null

  return (
    <div className="mp-library flex flex-col gap-6" data-mediapicker>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">
          <bdi>{props.title}</bdi>
        </h1>
        <div className="flex flex-wrap gap-2">
          <LinkButton url={urls.index} color="secondary" size="sm" startIcon="chevron-left">
            {t("back_to_library")}
          </LinkButton>
          {can.update && urls.edit ? (
            <LinkButton url={urls.edit} size="sm">
              {t("edit")}
            </LinkButton>
          ) : null}
          {can.delete ? (
            <Button type="button" color="danger" size="sm" onClick={destroy}>
              <Icon name="trash-2" /> {t("delete")}
            </Button>
          ) : null}
        </div>
      </div>

      <Flash />

      <div className="grid gap-6 lg:grid-cols-2">
        <Card title={t("preview")}>
          <MediaPreview media={media} />
        </Card>

        <Card title={t("details")}>
          <div className="flex flex-col gap-3">
            <TextEntry label={t("name")} value={<bdi>{media.name}</bdi>} />
            <TextEntry label={t("file_name")} value={<bdi>{media.file_name}</bdi>} />
            <TextEntry label={t("file_type")} value={<span dir="ltr">{media.mime_type}</span>} />
            <TextEntry label={t("file_size")} value={<span dir="ltr">{media.human_size}</span>} />
            {media.width && media.height ? (
              <TextEntry label={t("dimensions")} value={<span dir="ltr">{`${media.width} × ${media.height}`}</span>} />
            ) : null}
            {uploaded ? <TextEntry label={t("uploaded_at")} value={uploaded} /> : null}
            {media.owner?.name ? <TextEntry label={t("uploaded_by")} value={<bdi>{media.owner.name}</bdi>} /> : null}
            {media.description ? <TextEntry label={t("description")} value={<bdi>{media.description}</bdi>} /> : null}
            {media.alt ? <TextEntry label={t("alt")} value={<bdi>{media.alt}</bdi>} /> : null}
            {media.caption ? <TextEntry label={t("caption")} value={<bdi>{media.caption}</bdi>} /> : null}
            <div className="flex flex-wrap items-center gap-2">
              <input className="mp-input flex-1" dir="ltr" readOnly value={media.url} aria-label={t("url")} />
              <Button type="button" color="secondary" size="sm" onClick={() => void copy()}>
                <Icon name="copy" /> {copied ? t("copied") : t("copy_url")}
              </Button>
              <LinkButton url={media.url} color="secondary" size="sm" target="_blank" rel="noreferrer">
                <Icon name="download" /> {t("download")}
              </LinkButton>
            </div>
          </div>
        </Card>
      </div>
    </div>
  )
}
