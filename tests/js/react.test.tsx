import { fireEvent, render, screen, waitFor, within } from "@testing-library/react"
import { useState } from "react"
import { describe, expect, it } from "vitest"

import { MediaPicker, MediaPickerField, MediapickerProvider, Picture } from "../../resources/js"
import { withDefaults } from "../../resources/js/strings"
import type { MediaItem } from "../../resources/js/types"
import { fakeClient, mediaItem } from "./support/fixtures"

function Controlled({
  multiple = false,
  initial = null,
}: {
  multiple?: boolean
  initial?: MediaItem | MediaItem[] | null
}) {
  const { client } = fakeClient([mediaItem({ id: 1 }), mediaItem({ id: 2 })])
  const [value, setValue] = useState<MediaItem | MediaItem[] | null>(initial)

  return (
    <form data-testid="form">
      <MediaPicker
        name={multiple ? "gallery[]" : "featured"}
        multiple={multiple}
        value={value}
        onChange={setValue}
        url="/media"
        client={client}
        type="image"
      />
    </form>
  )
}

const submitted = () => Array.from(new FormData(screen.getByTestId("form") as HTMLFormElement).entries())

describe("MediaPicker", () => {
  it("chooses media in the library dialog", async () => {
    const { container } = render(<Controlled />)

    expect(container.querySelector(".mp-field__empty")).toHaveTextContent("Nothing chosen")
    expect(submitted()).toEqual([["featured", ""]])

    fireEvent.click(screen.getByRole("button", { name: /Choose/ }))

    const dialog = await screen.findByRole("dialog")
    fireEvent.click(await within(dialog).findByRole("button", { name: /ބީޗް 2/ }))
    fireEvent.click(within(dialog).getByRole("button", { name: "Choose" }))

    await waitFor(() => expect(submitted()).toEqual([["featured", "2"]]))
    expect(container.querySelector(".mp-field__item .mp-name")).toHaveTextContent("ބީޗް 2")
  })

  it("reorders and removes several", () => {
    render(<Controlled multiple initial={[mediaItem({ id: 1 }), mediaItem({ id: 2 })]} />)

    fireEvent.click(screen.getByRole("button", { name: "Move up: ބީޗް 2" }))
    expect(submitted().map(([, id]) => id)).toEqual(["2", "1"])

    fireEvent.click(screen.getByRole("button", { name: "Remove: ބީޗް 2" }))
    expect(submitted()).toEqual([["gallery[]", "1"]])
  })
})

describe("MediaPickerField", () => {
  it("takes the library from the provider and shows a label", () => {
    const config = {
      url: "/admin/media",
      types: [],
      accept: "image/*",
      max_size: 1024,
      crop_presets: [],
      strings: withDefaults({ choose: "ހޮވާ" }),
    }

    render(
      <MediapickerProvider config={config}>
        <MediaPickerField name="featured" label="ފޮޓޯ" value={null} onChange={() => {}} required />
      </MediapickerProvider>,
    )

    expect(screen.getByText("ފޮޓޯ")).toBeInTheDocument()
    expect(screen.getByRole("button", { name: /ހޮވާ/ })).toBeInTheDocument()
  })
})

describe("Picture", () => {
  it("renders sources and the image", () => {
    const { container } = render(
      <Picture
        image={{
          src: "/card.jpg",
          srcset: "/card.jpg 800w",
          sizes: "100vw",
          sources: [{ type: "image/webp", srcset: "/card.webp 800w" }],
          width: 800,
          height: 450,
          alt: "ކަނޑު",
        }}
        className="rounded"
      />,
    )

    expect(container.querySelector('source[type="image/webp"]')?.getAttribute("srcset")).toBe("/card.webp 800w")
    const img = screen.getByAltText("ކަނޑު")
    expect(img).toHaveAttribute("width", "800")
    expect(img).toHaveAttribute("sizes", "100vw")
    expect(img).toHaveClass("rounded")
  })

  it("renders nothing without an image", () => {
    const { container } = render(<Picture image={null} />)
    expect(container.innerHTML).toBe("")
  })
})
