import type { LibraryClient } from "../../../resources/js/api"
import type { MediaItem, Paginated } from "../../../resources/js/types"

export function mediaItem(overrides: Partial<MediaItem> = {}): MediaItem {
  const id = overrides.id ?? 1
  return {
    id,
    uuid: `uuid-${id}`,
    name: `ބީޗް ${id}`,
    file_name: `beach-${id}.jpg`,
    mime_type: "image/jpeg",
    type: "image",
    icon: "image",
    size: 2048,
    human_size: "2 KB",
    width: 340,
    height: 280,
    description: null,
    alt: "ކަނޑު",
    caption: null,
    url: `/storage/${id}/beach.jpg`,
    thumb: `/storage/${id}/conversions/beach-mediapicker-thumb.jpg`,
    preview: null,
    crops: {},
    translations: null,
    owner: null,
    created_at: "2026-11-05T10:00:00+05:00",
    updated_at: "2026-11-05T10:00:00+05:00",
    can: { update: true, delete: true },
    links: { show: `/media/${id}`, edit: `/media/${id}/edit`, update: `/media/${id}`, destroy: `/media/${id}` },
    ...overrides,
  }
}

export function page(items: MediaItem[], current = 1, last = 1): Paginated<MediaItem> {
  return {
    data: items,
    links: { first: null, last: null, prev: null, next: null },
    meta: {
      current_page: current,
      last_page: last,
      per_page: 24,
      total: items.length,
      from: 1,
      to: items.length,
      path: "/media",
      links: [],
    },
  }
}

/** A client over an in-memory library */
export function fakeClient(items: MediaItem[], options: { last?: number } = {}) {
  const calls: { list: unknown[]; upload: File[]; crop: unknown[] } = { list: [], upload: [], crop: [] }
  let next = 100

  const client: LibraryClient = {
    async list(query = {}) {
      calls.list.push(query)
      const search = (query.search ?? "").toString()
      const matching = items.filter((item) => !search || item.name.includes(search))
      return page(matching, query.page ?? 1, options.last ?? 1)
    },
    async find(id) {
      return items.find((item) => item.id === Number(id)) ?? mediaItem({ id: Number(id) })
    },
    async upload(file, upload = {}) {
      calls.upload.push(file)
      upload.onProgress?.(1)
      return mediaItem({ id: next++, name: file.name.replace(/\.[^.]+$/, "") })
    },
    async update(id, details) {
      return { ...mediaItem({ id: Number(id) }), ...details }
    },
    async crop(id, preset, crop) {
      calls.crop.push({ id, preset, crop })
      return mediaItem({ id: Number(id), crops: { [preset]: { rotation: 0, ...crop } } })
    },
    async destroy() {},
  }

  return { client, calls }
}
