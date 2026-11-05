import { afterEach, describe, expect, it } from "vitest"
import { fireEvent, screen, waitFor, within } from "@testing-library/dom"

import { openMediaPicker } from "../../resources/js/picker/dialog"
import { mountPickerFields, pickerField, type PickerFieldConfig } from "../../resources/js/picker/field"
import { fakeClient, mediaItem } from "./support/fixtures"

afterEach(() => {
  document.body.innerHTML = ""
})

describe("openMediaPicker", () => {
  it("lists the library and resolves with the chosen media", async () => {
    const { client } = fakeClient([mediaItem({ id: 1 }), mediaItem({ id: 2 })])
    const result = openMediaPicker({ url: "/media", client, type: "image", strings: { choose: "ހޮވާ" } })

    const dialog = await screen.findByRole("dialog", { name: "Choose an image" })
    await within(dialog).findByText("ބީޗް 2")

    const choose = within(dialog).getByRole("button", { name: "ހޮވާ" })
    expect(choose).toBeDisabled()

    fireEvent.click(within(dialog).getByTitle("ބީޗް 2"))
    expect(within(dialog).getByTitle("ބީޗް 2")).toHaveAttribute("aria-pressed", "true")
    expect(within(dialog).getByRole("status")).toHaveTextContent("1 selected")

    fireEvent.click(choose)

    await expect(result).resolves.toEqual([expect.objectContaining({ id: 2 })])
    expect(screen.queryByRole("dialog")).toBeNull()
  })

  it("selects one at a time unless multiple", async () => {
    const { client } = fakeClient([mediaItem({ id: 1 }), mediaItem({ id: 2 })])
    const result = openMediaPicker({ url: "/media", client, multiple: true, selected: [mediaItem({ id: 1 })] })

    const dialog = await screen.findByRole("dialog", { name: "Choose media" })
    await within(dialog).findByText("ބީޗް 2")

    expect(within(dialog).getByTitle("ބީޗް 1")).toHaveAttribute("aria-pressed", "true")
    fireEvent.click(within(dialog).getByTitle("ބީޗް 2"))
    fireEvent.click(within(dialog).getByRole("button", { name: "Choose" }))

    expect((await result)?.map((item) => item.id)).toEqual([1, 2])
  })

  it("resolves null when cancelled", async () => {
    const { client } = fakeClient([mediaItem()])
    const result = openMediaPicker({ url: "/media", client })

    fireEvent.click(await screen.findByRole("button", { name: "Cancel" }))

    await expect(result).resolves.toBeNull()
  })

  it("searches the library", async () => {
    const { client, calls } = fakeClient([mediaItem({ id: 1, name: "ހުޅުމާލެ" }), mediaItem({ id: 2, name: "Logo" })])
    void openMediaPicker({ url: "/media", client })

    const search = await screen.findByRole("searchbox", { name: "Search media" })
    fireEvent.input(search, { target: { value: "ހުޅު" } })

    await waitFor(() => expect(screen.queryByText("Logo")).toBeNull())
    expect(screen.getByText("ހުޅުމާލެ")).toBeInTheDocument()
    expect(calls.list.at(-1)).toMatchObject({ search: "ހުޅު", page: 1 })
  })

  it("uploads and selects the new file", async () => {
    const { client, calls } = fakeClient([])
    const result = openMediaPicker({ url: "/media", client, type: "image" })

    const dialog = await screen.findByRole("dialog")
    await within(dialog).findByText("No media yet")

    const input = dialog.querySelector<HTMLInputElement>('input[type="file"]') as HTMLInputElement
    Object.defineProperty(input, "files", { value: [new File(["x"], "ބީޗް.jpg", { type: "image/jpeg" })] })
    fireEvent.change(input)

    await within(dialog).findByText("ބީޗް")
    expect(calls.upload).toHaveLength(1)

    fireEvent.click(within(dialog).getByRole("button", { name: "Choose" }))
    expect((await result)?.[0]?.name).toBe("ބީޗް")
  })

  it("loads more pages", async () => {
    const { client, calls } = fakeClient([mediaItem()], { last: 2 })
    void openMediaPicker({ url: "/media", client })

    fireEvent.click(await screen.findByRole("button", { name: "Load more" }))

    await waitFor(() => expect(calls.list.at(-1)).toMatchObject({ page: 2 }))
  })
})

describe("the Blade picker field", () => {
  function field(config: Partial<PickerFieldConfig>) {
    const full: PickerFieldConfig = {
      name: "gallery[]",
      multiple: true,
      type: ["image"],
      url: "/media",
      value: [],
      ...config,
    }
    document.body.innerHTML = `
      <form>
        <div class="mp-field" data-mediapicker-field='${JSON.stringify(full).replace(/'/g, "&#39;")}'>
          <ul class="mp-field__items"></ul>
          <p class="mp-field__empty">Nothing chosen</p>
          <div class="mp-field__inputs"></div>
          <button type="button" data-mp-choose>Choose</button>
          <button type="button" data-mp-clear>Remove</button>
        </div>
      </form>`
    mountPickerFields()
    return document.querySelector<HTMLElement>("[data-mediapicker-field]") as HTMLElement
  }

  const ids = () => Array.from(new FormData(document.querySelector("form") as HTMLFormElement).entries())

  it("submits the ids of the chosen media in order", () => {
    field({ value: [mediaItem({ id: 4 }), mediaItem({ id: 7 })] })

    expect(ids()).toEqual([
      ["gallery[]", "4"],
      ["gallery[]", "7"],
    ])
    expect(screen.getByText("Nothing chosen")).not.toBeVisible()
  })

  it("reorders and removes", () => {
    const element = field({ value: [mediaItem({ id: 4 }), mediaItem({ id: 7 })] })
    let changes = 0
    element.addEventListener("change", () => changes++)

    fireEvent.click(screen.getByRole("button", { name: "Move down: ބީޗް 4" }))
    expect(ids().map(([, id]) => id)).toEqual(["7", "4"])

    fireEvent.click(screen.getByRole("button", { name: "Remove: ބީޗް 7" }))
    expect(ids().map(([, id]) => id)).toEqual(["4"])
    expect(changes).toBe(2)
  })

  it("still submits the field when cleared", () => {
    field({ value: [mediaItem({ id: 4 })] })

    fireEvent.click(screen.getByRole("button", { name: "Remove" }))

    expect(ids()).toEqual([["gallery", ""]])
  })

  it("keeps one item for a single field", () => {
    const element = field({ name: "featured", multiple: false })

    pickerField(element)?.set([mediaItem({ id: 1 }), mediaItem({ id: 2 })])

    expect(ids()).toEqual([["featured", "1"]])
    expect(screen.queryByRole("button", { name: /Move up/ })).toBeNull()
  })
})
