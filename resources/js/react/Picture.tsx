import type { ImgHTMLAttributes } from "react"

import type { ImageData } from "../types"

export interface PictureProps extends Omit<ImgHTMLAttributes<HTMLImageElement>, "src" | "srcSet" | "width" | "height"> {
  /** From `Mediapicker::image($attachment, 'card')` (or `ImageData::for()`) on the server. */
  image: ImageData | null | undefined
}

/**
 * An attached image as `<picture>`: the same markup as `<x-mediapicker::image>`, from the
 * data the server prepares (sources per format, srcset, width, height, alt).
 */
export function Picture({ image, sizes, alt, loading = "lazy", ...props }: PictureProps) {
  if (!image) return null

  const img = (
    <img
      {...props}
      src={image.src}
      srcSet={image.srcset ?? undefined}
      sizes={image.srcset ? (sizes ?? image.sizes ?? undefined) : undefined}
      width={image.width ?? undefined}
      height={image.height ?? undefined}
      alt={alt ?? image.alt}
      loading={loading}
      decoding="async"
    />
  )

  if (!image.sources.length) return img

  return (
    <picture>
      {image.sources.map((source) => (
        <source key={source.type} type={source.type} srcSet={source.srcset} sizes={sizes ?? image.sizes ?? undefined} />
      ))}
      {img}
    </picture>
  )
}
