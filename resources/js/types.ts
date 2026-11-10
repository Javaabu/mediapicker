import type defaultStrings from "./strings.en.json"

/** A crop of an image for a preset, in source pixels. */
export interface Crop {
  x: number
  y: number
  width: number
  height: number
  rotation: number
}

/** An aspect-ratio preset from `config('mediapicker.crop_presets')`. */
export interface CropPreset {
  name: string
  label: string
  /** Width / height, or null for free cropping. */
  aspect: number | null
}

/** A library media item, as `MediaResource` sends it. */
export interface MediaItem {
  id: number
  uuid: string
  name: string
  file_name: string
  mime_type: string
  /** `image`, `video`, `audio`, `document`, `archive`, or null for other files. */
  type: string | null
  /** A Lucide icon name for the file type. */
  icon: string
  size: number
  human_size: string
  width: number | null
  height: number | null
  description: string | null
  alt: string | null
  caption: string | null
  url: string
  thumb: string | null
  preview: string | null
  crops: Record<string, Crop>
  /** Values per locale (`name_en`, `name_dv`, …) when javaabu/translatable is on. */
  translations: Record<string, string | null> | null
  owner: { id: number | string; name: string | null } | null
  created_at: string | null
  updated_at: string | null
  can: { update: boolean; delete: boolean }
  links: { show: string; edit: string; update: string; destroy: string } | null
}

/** A Laravel paginated resource collection. */
export interface Paginated<T> {
  data: T[]
  links: { first: string | null; last: string | null; prev: string | null; next: string | null }
  meta: {
    current_page: number
    last_page: number
    per_page: number
    total: number
    from: number | null
    to: number | null
    path: string
    links: Array<{ url: string | null; label: string; active: boolean }>
  }
}

export interface FileTypeOption {
  value: string
  label: string
  icon: string
}

/** The UI strings, keyed as in `Javaabu\Mediapicker\Support\Strings`. */
export type StringKey = keyof typeof defaultStrings
export type Strings = Record<StringKey, string>

export interface LibraryFilters {
  search: string | null
  type: string[]
  owner: "mine" | null
  date_from: string | null
  date_to: string | null
  orderby: "created_at" | "name" | "size" | "id"
  order: "asc" | "desc"
  per_page: number
  view: "grid" | "list"
}

export interface LibraryUrls {
  index: string
  create: string
  store: string
  bulk: string
  show?: string
  edit?: string
  update?: string
  destroy?: string
  /** With a `{preset}` placeholder. */
  crop?: string
}

/** What every library page gets from the controller. */
export interface LibraryPageProps {
  title: string
  can: { create: boolean; view_others: boolean; update: boolean | null; delete: boolean | null }
  urls: LibraryUrls
  file_types: FileTypeOption[]
  upload: { accept: string; max_size: number }
  crop_presets: CropPreset[]
  translatable: boolean
  strings: Strings
}

export interface MediaIndexProps extends LibraryPageProps {
  media_items: Paginated<MediaItem>
  filters: LibraryFilters
}

export type MediaCreateProps = LibraryPageProps

export interface MediaShowProps extends LibraryPageProps {
  media: MediaItem
}

export interface MediaEditProps extends MediaShowProps {
  /** The details form, from javaabu/stencil. */
  form: unknown
}

/** An attached image ready to render, from `Javaabu\Mediapicker\Support\ImageData`. */
export interface ImageData {
  src: string
  srcset: string | null
  sizes: string | null
  sources: Array<{ type: string; srcset: string }>
  width: number | null
  height: number | null
  alt: string
}
