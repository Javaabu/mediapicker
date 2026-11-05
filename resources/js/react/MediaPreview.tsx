import type { MediaItem } from "../types"
import { Icon } from "./Icon"

/** A large preview of a library media: the image, a video or audio player, or the file's icon. */
export function MediaPreview({ media, className = "" }: { media: MediaItem; className?: string }) {
  if (media.type === "image") {
    return (
      <img
        src={media.preview ?? media.url}
        alt={media.alt ?? ""}
        width={media.width ?? undefined}
        height={media.height ?? undefined}
        className={`block h-auto max-w-full rounded-md ${className}`}
      />
    )
  }

  if (media.type === "video") {
    return <video src={media.url} controls preload="metadata" className={`block max-w-full rounded-md ${className}`} />
  }

  if (media.type === "audio") {
    return <audio src={media.url} controls preload="metadata" className={`block w-full ${className}`} />
  }

  return (
    <a href={media.url} target="_blank" rel="noreferrer" className={`flex items-center gap-3 rounded-md border p-4 ${className}`}>
      <Icon name={media.icon} className="mp-icon h-8 w-8" />
      <bdi>{media.file_name}</bdi>
    </a>
  )
}
