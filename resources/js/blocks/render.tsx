import type { BlockRenderProps } from "@javaabu/block-editor"

import { Icon } from "../react/Icon"
import type { GalleryItem } from "./gallery"

const text = (value: unknown) => (typeof value === "string" && value !== "" ? value : null)
const num = (value: unknown) => (typeof value === "number" ? value : undefined)

/** The image block in `<BlockContent>`: the same markup as `mediapicker::blocks.image`. */
export function ImageRender({ attrs }: BlockRenderProps) {
  const img = (
    <img
      src={String(attrs.src ?? "")}
      alt={String(attrs.alt ?? "")}
      width={num(attrs.width)}
      height={num(attrs.height)}
      loading="lazy"
      decoding="async"
    />
  )

  return (
    <figure className="block-image mp-block mp-block-image">
      {text(attrs.link) ? <a href={String(attrs.link)}>{img}</a> : img}
      {text(attrs.caption) ? <figcaption dir="auto">{String(attrs.caption)}</figcaption> : null}
    </figure>
  )
}

export function GalleryRender({ attrs }: BlockRenderProps) {
  const items = Array.isArray(attrs.items) ? (attrs.items as GalleryItem[]) : []
  const columns = Math.max(1, Math.min(6, Number(attrs.columns ?? 3)))

  return (
    <figure className="mp-block mp-block-gallery" style={{ "--mp-gallery-columns": columns } as React.CSSProperties}>
      <ul>
        {items.map((item, index) => (
          <li key={item.id ?? index}>
            <a href={item.src}>
              <img
                src={item.thumb ?? item.src}
                alt={item.alt ?? ""}
                width={item.width ?? undefined}
                height={item.height ?? undefined}
                loading="lazy"
                decoding="async"
              />
            </a>
          </li>
        ))}
      </ul>
      {text(attrs.caption) ? <figcaption dir="auto">{String(attrs.caption)}</figcaption> : null}
    </figure>
  )
}

export function FileRender({ attrs }: BlockRenderProps) {
  return (
    <p className="mp-block mp-block-file">
      <Icon name="file-text" />
      <a href={String(attrs.href ?? "")} download dir="auto">
        {text(attrs.label) ?? text(attrs.fileName) ?? "Download"}
      </a>
      {text(attrs.size) ? <small dir="ltr">{String(attrs.size)}</small> : null}
    </p>
  )
}

export function VideoRender({ attrs }: BlockRenderProps) {
  const autoplay = attrs.autoplay === true

  return (
    <figure className="mp-block mp-block-video">
      <video
        src={String(attrs.src ?? "")}
        controls={attrs.controls !== false}
        autoPlay={autoplay}
        loop={attrs.loop === true}
        muted={attrs.muted === true || autoplay}
        playsInline
        preload="metadata"
      />
      {text(attrs.caption) ? <figcaption dir="auto">{String(attrs.caption)}</figcaption> : null}
    </figure>
  )
}
