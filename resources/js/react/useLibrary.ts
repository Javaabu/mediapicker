import { useCallback, useEffect, useMemo, useRef, useState } from "react"

import { createLibraryClient, LibraryError, type LibraryClient, type UploadOptions } from "../api"
import type { MediaItem } from "../types"

export interface Upload {
  key: string
  name: string
  progress: number
}

export interface UseLibraryOptions {
  url: string
  /** Only these types. */
  type?: string | string[] | null
  /** Don't load until true (a closed dialog). Default true. */
  enabled?: boolean
  perPage?: number
  client?: LibraryClient
}

/**
 * Loads a library's media page by page, with search and a type filter, and uploads to it
 */
export function useLibrary({ url, type = null, enabled = true, perPage = 24, client: givenClient }: UseLibraryOptions) {
  const client = useMemo(() => givenClient ?? createLibraryClient(url), [givenClient, url])
  const types = useMemo(() => (type ? (Array.isArray(type) ? type : [type]) : []), [type])
  const typesKey = types.join(",")

  const [items, setItems] = useState<MediaItem[]>([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [search, setSearch] = useState("")
  const [typeFilter, setTypeFilter] = useState("")
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [uploads, setUploads] = useState<Upload[]>([])
  const request = useRef<AbortController | null>(null)

  const load = useCallback(
    async (nextPage: number) => {
      request.current?.abort()
      const controller = new AbortController()
      request.current = controller
      setLoading(true)
      setError(null)

      try {
        const result = await client.list(
          {
            search,
            type: typeFilter ? [typeFilter] : typesKey ? typesKey.split(",") : null,
            page: nextPage,
            per_page: perPage,
          },
          controller.signal,
        )
        setItems((current) => (nextPage === 1 ? result.data : [...current, ...result.data]))
        setPage(result.meta.current_page)
        setLastPage(result.meta.last_page)
      } catch (caught) {
        if ((caught as Error).name !== "AbortError") setError((caught as Error).message)
      } finally {
        if (request.current === controller) setLoading(false)
      }
    },
    [client, search, typeFilter, typesKey, perPage],
  )

  useEffect(() => {
    if (!enabled) return
    const timer = setTimeout(() => void load(1), search ? 300 : 0)
    return () => clearTimeout(timer)
  }, [enabled, load, search])

  useEffect(() => () => request.current?.abort(), [])

  const upload = useCallback(
    async (file: File, options: Omit<UploadOptions, "onProgress" | "type"> = {}): Promise<MediaItem | null> => {
      const key = `${file.name}-${Date.now()}-${Math.random()}`
      setUploads((current) => [...current, { key, name: file.name, progress: 0 }])

      try {
        const media = await client.upload(file, {
          ...options,
          type: types.length ? types : null,
          onProgress: (progress) =>
            setUploads((current) => current.map((item) => (item.key === key ? { ...item, progress } : item))),
        })
        setItems((current) => [media, ...current.filter((item) => item.id !== media.id)])
        return media
      } catch (caught) {
        const message =
          caught instanceof LibraryError ? (Object.values(caught.errors)[0]?.[0] ?? caught.message) : String(caught)
        setError(`${file.name}: ${message}`)
        return null
      } finally {
        setUploads((current) => current.filter((item) => item.key !== key))
      }
    },
    [client, types],
  )

  return {
    client,
    items,
    page,
    hasMore: page < lastPage,
    loadMore: () => void load(page + 1),
    reload: () => void load(1),
    search,
    setSearch,
    typeFilter,
    setTypeFilter,
    loading,
    error,
    setError,
    uploads,
    upload,
  }
}
