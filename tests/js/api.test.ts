import { afterEach, describe, expect, it, vi } from "vitest"

import { createLibraryClient, csrfHeaders, LibraryError } from "../../resources/js/api"
import { format, formatBytes, translator, withDefaults } from "../../resources/js/strings"
import { mediaItem, page } from "./support/fixtures"

function jsonResponse(body: unknown, status = 200) {
  return new Response(JSON.stringify(body), { status, headers: { "Content-Type": "application/json" } })
}

afterEach(() => {
  document.head.innerHTML = ""
  document.cookie = "XSRF-TOKEN=; expires=Thu, 01 Jan 1970 00:00:00 GMT"
})

describe("createLibraryClient", () => {
  it("lists with the filters as query parameters", async () => {
    const fetcher = vi.fn().mockResolvedValue(jsonResponse(page([mediaItem()])))
    const client = createLibraryClient("/admin/media/", fetcher)

    const result = await client.list({ search: "ބީޗް", type: ["image", "video"], page: 2, owner: null })

    expect(result.data[0]?.id).toBe(1)
    const [url, init] = fetcher.mock.calls[0] as [string, RequestInit]
    expect(url).toBe("/admin/media?search=%DE%84%DE%A9%DE%97%DE%B0&type%5B%5D=image&type%5B%5D=video&page=2")
    expect((init.headers as Record<string, string>).Accept).toBe("application/json")
  })

  it("sends the CSRF token with changes", async () => {
    document.head.innerHTML = '<meta name="csrf-token" content="token-123">'
    const fetcher = vi.fn().mockResolvedValue(jsonResponse({ data: mediaItem({ name: "ހުޅުމާލެ" }) }))
    const client = createLibraryClient("/media", fetcher)

    const media = await client.update(1, { name: "ހުޅުމާލެ" })

    expect(media.name).toBe("ހުޅުމާލެ")
    const [url, init] = fetcher.mock.calls[0] as [string, RequestInit]
    expect(url).toBe("/media/1")
    expect(init.method).toBe("PATCH")
    expect((init.headers as Record<string, string>)["X-CSRF-TOKEN"]).toBe("token-123")
    expect(JSON.parse(init.body as string)).toEqual({ name: "ހުޅުމާލެ" })
  })

  it("prefers the XSRF cookie", () => {
    document.cookie = "XSRF-TOKEN=abc%3D"
    expect(csrfHeaders()).toEqual({ "X-XSRF-TOKEN": "abc=" })
  })

  it("saves crops", async () => {
    const fetcher = vi.fn().mockResolvedValue(jsonResponse({ data: mediaItem() }))
    await createLibraryClient("/media", fetcher).crop(5, "wide", { x: 0, y: 10, width: 160, height: 90 })

    const [url, init] = fetcher.mock.calls[0] as [string, RequestInit]
    expect(url).toBe("/media/5/crops/wide")
    expect(init.method).toBe("PUT")
  })

  it("throws validation errors", async () => {
    const fetcher = vi.fn().mockResolvedValue(jsonResponse({ message: "Invalid", errors: { name: ["Required"] } }, 422))

    const error = await createLibraryClient("/media", fetcher)
      .update(1, { name: "" })
      .catch((caught: unknown) => caught)

    expect(error).toBeInstanceOf(LibraryError)
    expect((error as LibraryError).status).toBe(422)
    expect((error as LibraryError).errors.name).toEqual(["Required"])
  })

  it("deletes", async () => {
    const fetcher = vi.fn().mockResolvedValue(new Response(null, { status: 204 }))
    await expect(createLibraryClient("/media", fetcher).destroy(3)).resolves.toBeUndefined()
    expect((fetcher.mock.calls[0] as [string, RequestInit])[1].method).toBe("DELETE")
  })

  it("uploads with progress", async () => {
    const sent: { form?: FormData; url?: string } = {}
    class FakeXhr {
      upload = { onprogress: null as ((event: ProgressEvent) => void) | null }
      status = 201
      statusText = "Created"
      responseText = JSON.stringify({ data: mediaItem({ id: 9 }) })
      withCredentials = false
      onload: (() => void) | null = null
      onerror = null
      onabort = null
      open(_method: string, url: string) {
        sent.url = url
      }
      setRequestHeader() {}
      send(form: FormData) {
        sent.form = form
        this.upload.onprogress?.({ lengthComputable: true, loaded: 5, total: 10 } as ProgressEvent)
        this.onload?.()
      }
      abort() {}
    }
    vi.stubGlobal("XMLHttpRequest", FakeXhr)

    const progress: number[] = []
    const media = await createLibraryClient("/media").upload(new File(["x"], "beach.jpg", { type: "image/jpeg" }), {
      type: "image",
      alt: "ކަނޑު",
      crops: { wide: { x: 0, y: 1, width: 16, height: 9 } },
      onProgress: (value) => progress.push(value),
    })

    expect(media.id).toBe(9)
    expect(sent.url).toBe("/media")
    expect(sent.form?.get("type")).toBe("image")
    expect(sent.form?.get("alt")).toBe("ކަނޑު")
    expect(sent.form?.get("crops[wide][height]")).toBe("9")
    expect(progress).toEqual([0.5, 1])

    vi.unstubAllGlobals()
  })
})

describe("strings", () => {
  it("fills placeholders and falls back to English", () => {
    const t = translator({ selected: ":count ހޮވިފައި" })

    expect(t("selected", { count: 3 })).toBe("3 ހޮވިފައި")
    expect(t("cancel")).toBe("Cancel")
    expect(format(":count of :counter", { count: 1, counter: 2 })).toBe("1 of 2")
    expect(withDefaults(null).choose).toBe("Choose")
  })

  it("formats sizes", () => {
    expect(formatBytes(512)).toBe("512 B")
    expect(formatBytes(1536 * 1024)).toBe("1.5 MB")
  })
})
