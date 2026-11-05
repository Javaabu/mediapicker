import type { Crop, MediaItem, Paginated } from "./types"

/** What the library can be filtered by, as the index action reads it. */
export interface LibraryQuery {
  search?: string | null
  type?: string | string[] | null
  owner?: "mine" | null
  orderby?: "created_at" | "name" | "size" | "id"
  order?: "asc" | "desc"
  page?: number
  per_page?: number
}

export interface UploadOptions {
  /** Limit the upload to these file types. */
  type?: string | string[] | null
  name?: string
  description?: string
  alt?: string
  caption?: string
  /** Crops per preset, saved with the upload. */
  crops?: Record<string, Omit<Crop, "rotation"> & { rotation?: number }>
  onProgress?: (fraction: number) => void
  signal?: AbortSignal
}

/** A failed request: `errors` holds Laravel's validation errors. */
export class LibraryError extends Error {
  constructor(
    message: string,
    public status: number,
    public errors: Record<string, string[]> = {},
  ) {
    super(message)
    this.name = "LibraryError"
  }
}

export interface LibraryClient {
  list(query?: LibraryQuery, signal?: AbortSignal): Promise<Paginated<MediaItem>>
  find(id: number | string): Promise<MediaItem>
  upload(file: File, options?: UploadOptions): Promise<MediaItem>
  update(
    id: number | string,
    details: Partial<Pick<MediaItem, "name" | "description" | "alt" | "caption">>,
  ): Promise<MediaItem>
  crop(id: number | string, preset: string, crop: Omit<Crop, "rotation"> & { rotation?: number }): Promise<MediaItem>
  destroy(id: number | string): Promise<void>
}

/** The token Laravel checks on non-GET requests: the XSRF cookie, else the csrf-token meta tag. */
export function csrfHeaders(): Record<string, string> {
  const cookie = typeof document === "undefined" ? null : document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/)
  if (cookie?.[1]) return { "X-XSRF-TOKEN": decodeURIComponent(cookie[1]) }

  const meta =
    typeof document === "undefined" ? null : document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
  return meta?.content ? { "X-CSRF-TOKEN": meta.content } : {}
}

function queryString(query: LibraryQuery): string {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(query)) {
    if (value === null || value === undefined || value === "") continue
    if (Array.isArray(value)) value.forEach((item) => params.append(`${key}[]`, String(item)))
    else params.set(key, String(value))
  }

  const string = params.toString()
  return string ? `?${string}` : ""
}

async function failure(response: Response): Promise<LibraryError> {
  let body: { message?: string; errors?: Record<string, string[]> } = {}
  try {
    body = await response.json()
  } catch {
    // not JSON
  }
  return new LibraryError(body.message || response.statusText || "Request failed", response.status, body.errors ?? {})
}

/**
 * A client for a library's JSON API: the URL of its index route, e.g. `/admin/media`.
 * The pickers, the editor blocks and the React components all use it.
 */
export function createLibraryClient(
  baseUrl: string,
  fetcher: typeof fetch = (...args) => fetch(...args),
): LibraryClient {
  const base = baseUrl.replace(/\/+$/, "")
  const headers = () => ({ Accept: "application/json", "X-Requested-With": "XMLHttpRequest", ...csrfHeaders() })

  async function json<T>(url: string, init: RequestInit = {}): Promise<T> {
    const response = await fetcher(url, {
      credentials: "same-origin",
      ...init,
      headers: {
        ...headers(),
        ...(init.body && !(init.body instanceof FormData) ? { "Content-Type": "application/json" } : {}),
        ...(init.headers ?? {}),
      },
    })
    if (!response.ok) throw await failure(response)
    if (response.status === 204) return undefined as T
    return (await response.json()) as T
  }

  return {
    list: (query = {}, signal) => json<Paginated<MediaItem>>(`${base}${queryString(query)}`, { signal }),

    find: async (id) => (await json<{ data: MediaItem }>(`${base}/${id}`)).data,

    upload(file, options = {}) {
      const data = new FormData()
      data.append("file", file)

      for (const key of ["name", "description", "alt", "caption"] as const) {
        if (options[key]) data.append(key, options[key] as string)
      }

      const types = options.type ? (Array.isArray(options.type) ? options.type : [options.type]) : []
      if (types.length === 1) data.append("type", types[0] as string)
      else types.forEach((type) => data.append("type[]", type))

      for (const [preset, crop] of Object.entries(options.crops ?? {})) {
        for (const [key, value] of Object.entries(crop)) data.append(`crops[${preset}][${key}]`, String(value))
      }

      // XMLHttpRequest, for upload progress
      return new Promise<MediaItem>((resolve, reject) => {
        const request = new XMLHttpRequest()
        request.open("POST", base)
        request.withCredentials = true
        for (const [name, value] of Object.entries(headers())) request.setRequestHeader(name, value)

        request.upload.onprogress = (event) => {
          if (event.lengthComputable) options.onProgress?.(event.loaded / event.total)
        }
        request.onload = () => {
          let body: { data?: MediaItem; message?: string; errors?: Record<string, string[]> } = {}
          try {
            body = JSON.parse(request.responseText || "{}")
          } catch {
            // not JSON
          }
          if (request.status >= 200 && request.status < 300 && body.data) {
            options.onProgress?.(1)
            resolve(body.data)
          } else {
            reject(
              new LibraryError(
                body.message || request.statusText || "Upload failed",
                request.status,
                body.errors ?? {},
              ),
            )
          }
        }
        request.onerror = () => reject(new LibraryError("Upload failed", 0))
        request.onabort = () => reject(new LibraryError("Upload cancelled", 0))
        options.signal?.addEventListener("abort", () => request.abort())

        request.send(data)
      })
    },

    update: async (id, details) =>
      (await json<{ data: MediaItem }>(`${base}/${id}`, { method: "PATCH", body: JSON.stringify(details) })).data,

    crop: async (id, preset, crop) =>
      (
        await json<{ data: MediaItem }>(`${base}/${id}/crops/${encodeURIComponent(preset)}`, {
          method: "PUT",
          body: JSON.stringify(crop),
        })
      ).data,

    destroy: (id) => json<void>(`${base}/${id}`, { method: "DELETE" }),
  }
}
