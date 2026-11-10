import { render } from "@testing-library/react"
import { fireEvent, screen, waitFor, within } from "@testing-library/dom"
import { createBlockEditor, getBlock, type EditorConfig } from "@javaabu/block-editor"
import { afterEach, describe, expect, it, vi } from "vitest"

import {
  file,
  gallery,
  GalleryRender,
  image,
  ImageRender,
  mediaVideo,
  VideoRender,
  FileRender,
} from "../../resources/js/blocks"
import config from "./fixtures/editor-config.json"
import { mediaItem, page } from "./support/fixtures"

afterEach(() => {
  document.body.innerHTML = ""
  vi.unstubAllGlobals()
})

describe("registration", () => {
  it("registers the media blocks, replacing the core image", () => {
    expect(getBlock("image")).toBe(image)
    expect(getBlock("gallery")).toBe(gallery)
    expect(getBlock("file")).toBe(file)
    expect(getBlock("mediaVideo")).toBe(mediaVideo)
  })
})

describe("React renderers", () => {
  it("render the same markup as the Blade views", () => {
    const { container } = render(
      <>
        <ImageRender
          node={{ type: "image" }}
          attrs={{ src: "/a.jpg", alt: "ކަނޑު", caption: "ހުޅުމާލެ", width: 340, height: 280 }}
        />
        <GalleryRender
          node={{ type: "gallery" }}
          attrs={{ columns: 2, items: [{ id: 1, src: "/a.jpg", thumb: "/a-thumb.jpg", alt: "A" }] }}
        />
        <FileRender node={{ type: "file" }} attrs={{ href: "/report.pdf", label: "ރިޕޯޓް", size: "2 KB" }} />
        <VideoRender node={{ type: "mediaVideo" }} attrs={{ src: "/clip.mp4", autoplay: true }} />
      </>,
    )

    expect(container.querySelector("figure.mp-block-image img")).toHaveAttribute("width", "340")
    expect(container.querySelector("figure.mp-block-image figcaption")).toHaveTextContent("ހުޅުމާލެ")
    expect(container.querySelector(".mp-block-gallery")).toHaveStyle("--mp-gallery-columns: 2")
    expect(container.querySelector(".mp-block-gallery a")).toHaveAttribute("href", "/a.jpg")
    expect(container.querySelector(".mp-block-file a")).toHaveTextContent("ރިޕޯޓް")
    const video = container.querySelector("video") as HTMLVideoElement
    expect(video.autoplay).toBe(true)
    expect(video.muted).toBe(true)
  })
})

describe("in the editor", () => {
  it("previews a gallery and picks again from the library", async () => {
    const fetch = vi.fn().mockResolvedValue(
      new Response(JSON.stringify(page([mediaItem({ id: 5 }), mediaItem({ id: 6 })])), {
        headers: { "Content-Type": "application/json" },
      }),
    )
    vi.stubGlobal("fetch", fetch)

    const element = document.createElement("div")
    document.body.append(element)
    let value: unknown = null

    const instance = createBlockEditor(element, {
      config: config as unknown as EditorConfig,
      content: {
        type: "doc",
        content: [
          {
            type: "gallery",
            attrs: { columns: 3, items: [{ id: 1, src: "/a.jpg", thumb: "/a-thumb.jpg", alt: "A" }] },
          },
        ],
      },
      onChange: (doc) => (value = doc),
    })

    const preview = await waitFor(() => element.querySelector(".mp-block-gallery") as HTMLElement)
    expect(preview.querySelector("img")).toHaveAttribute("src", "/a-thumb.jpg")

    fireEvent.click(within(preview).getByRole("button", { name: /Change/ }))

    const dialog = await screen.findByRole("dialog", { name: "Choose images" })
    expect(fetch.mock.calls[0]?.[0]).toContain("http://localhost/media?")

    fireEvent.click(await within(dialog).findByTitle("ބީޗް 6"))
    fireEvent.click(within(dialog).getByRole("button", { name: "Choose" }))

    await waitFor(() => expect(JSON.stringify(value)).toContain('"id":6'))
    const items = (value as { content: Array<{ attrs: { items: Array<{ id: number; src: string }> } }> }).content[0]
      ?.attrs.items
    expect(items?.map((item) => [item.id, item.src])).toEqual([
      [1, "/a.jpg"],
      [6, "/storage/6/beach.jpg"],
    ])

    instance.destroy()
  })
})
