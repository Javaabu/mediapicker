import { fireEvent, render, screen } from "@testing-library/react"
import { beforeEach, describe, expect, it, vi } from "vitest"

import Create from "../../resources/js/Pages/Media/Create"
import Edit from "../../resources/js/Pages/Media/Edit"
import Index from "../../resources/js/Pages/Media/Index"
import Show from "../../resources/js/Pages/Media/Show"
import { withDefaults } from "../../resources/js/strings"
import type { LibraryPageProps, MediaIndexProps } from "../../resources/js/types"
import { mediaItem, page } from "./support/fixtures"

const inertia = vi.hoisted(() => ({
  props: {} as Record<string, unknown>,
  get: vi.fn(),
  put: vi.fn(),
  delete: vi.fn(),
}))

vi.mock("@inertiajs/react", () => ({
  usePage: () => ({ props: inertia.props }),
  router: { get: inertia.get, put: inertia.put, delete: inertia.delete },
  Link: ({ href, children, ...props }: { href: string; children: React.ReactNode }) => (
    <a href={href} {...props}>
      {children}
    </a>
  ),
}))

vi.mock("@javaabu/stencil", () => ({
  StencilForm: () => <form data-testid="details-form" />,
}))

beforeEach(() => {
  inertia.props = {}
  vi.clearAllMocks()
})

function base(overrides: Partial<LibraryPageProps> = {}): LibraryPageProps {
  return {
    title: "Media library",
    can: { create: true, view_others: true, update: true, delete: true },
    urls: {
      index: "/media",
      create: "/media/create",
      store: "/media",
      bulk: "/media",
      show: "/media/1",
      edit: "/media/1/edit",
      update: "/media/1",
      destroy: "/media/1",
    },
    file_types: [
      { value: "image", label: "Image", icon: "image" },
      { value: "document", label: "Document", icon: "file-text" },
    ],
    upload: { accept: "image/jpeg", max_size: 10240 },
    crop_presets: [{ name: "wide", label: "Wide (16:9)", aspect: 16 / 9 }],
    translatable: false,
    strings: withDefaults(),
    ...overrides,
  }
}

function indexProps(): MediaIndexProps {
  return {
    ...base(),
    media_items: page([mediaItem({ id: 1 }), mediaItem({ id: 2, can: { update: false, delete: false } })], 1, 3),
    filters: {
      search: null,
      type: [],
      owner: null,
      date_from: null,
      date_to: null,
      orderby: "created_at",
      order: "desc",
      per_page: 24,
      view: "grid",
    },
  }
}

describe("Media/Index", () => {
  it("shows the library with upload and filters", () => {
    inertia.props = { flash: { success: "ފައިލު ފޮހެވިއްޖެ." } }
    render(<Index {...indexProps()} />)

    expect(screen.getByRole("heading", { name: "Media library" })).toBeInTheDocument()
    expect(screen.getByText("ފައިލު ފޮހެވިއްޖެ.")).toBeInTheDocument()
    expect(screen.getByText("ބީޗް 1")).toBeInTheDocument()
    expect(screen.getByRole("link", { name: /Upload media/ })).toHaveAttribute("href", "/media/create")
    expect(screen.getByRole("link", { name: "Edit: ބީޗް 1" })).toHaveAttribute("href", "/media/1/edit")
    expect(screen.getByRole("link", { name: "View: ބީޗް 2" })).toHaveAttribute("href", "/media/2")
  })

  it("filters by type through the query string", () => {
    render(<Index {...indexProps()} />)

    fireEvent.change(screen.getByRole("combobox", { name: "Type" }), { target: { value: "document" } })

    expect(inertia.get).toHaveBeenCalledWith(
      "/media",
      expect.objectContaining({ type: ["document"] }),
      expect.anything(),
    )
  })

  it("bulk deletes the selected media the user may delete", () => {
    vi.spyOn(window, "confirm").mockReturnValue(true)
    render(<Index {...indexProps()} />)

    fireEvent.click(screen.getByRole("button", { name: /ބީޗް 1/, pressed: false }))
    fireEvent.click(screen.getByRole("button", { name: /Delete selected/ }))

    expect(inertia.put).toHaveBeenCalledWith("/media", { action: "delete", media: [1] }, expect.anything())
  })
})

describe("Media/Show, Edit and Create", () => {
  it("shows a media's details", () => {
    render(<Show {...base({ title: "ބީޗް 1" })} media={mediaItem({ caption: "ފޮޓޯ: އަޙްމަދު" })} />)

    expect(screen.getByText("ފޮޓޯ: އަޙްމަދު")).toBeInTheDocument()
    expect(screen.getByText("340 × 280")).toBeInTheDocument()
    expect(screen.getByRole("textbox", { name: "URL" })).toHaveValue("/storage/1/beach.jpg")
  })

  it("edits a media with the stencil form and the cropper", () => {
    const { container } = render(<Edit {...base({ title: "Edit ބީޗް 1" })} media={mediaItem()} form={{}} />)

    expect(screen.getByTestId("details-form")).toBeInTheDocument()
    expect(container.querySelector(".mp-cropper__presets")).not.toBeNull()
    expect(screen.getByRole("button", { name: "Wide (16:9)" })).toHaveAttribute("aria-pressed", "true")
  })

  it("has no cropper for documents", () => {
    const { container } = render(
      <Edit {...base()} media={mediaItem({ type: "document", mime_type: "application/pdf" })} form={{}} />,
    )

    expect(container.querySelector(".mp-cropper")).toBeNull()
  })

  it("uploads files", () => {
    render(<Create {...base({ title: "Upload media" })} />)

    expect(screen.getByText("Drop files here or choose them")).toBeInTheDocument()
    expect(screen.getByText("Up to 10 MB each")).toBeInTheDocument()
  })
})
