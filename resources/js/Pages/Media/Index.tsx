import { Button, LinkButton } from "@javaabu/forms"
import { MediaLister, type MediaItem as ListerItem } from "@javaabu/onu"
import { Link, router } from "@inertiajs/react"
import { useMemo, useRef, useState } from "react"

import { translator } from "../../strings"
import type { LibraryFilters, MediaIndexProps } from "../../types"
import { Flash } from "../../react/Flash"
import { Icon } from "../../react/Icon"

/** mediapicker::Media/Index: the library, with filters, uploads and bulk delete */
export default function Index(props: MediaIndexProps) {
  const { media_items: media, filters, urls, can } = props
  const t = useMemo(() => translator(props.strings), [props.strings])
  const [selected, setSelected] = useState<Array<string | number>>([])
  const [search, setSearch] = useState(filters.search ?? "")
  const timer = useRef<ReturnType<typeof setTimeout>>(undefined)

  const visit = (changes: Partial<LibraryFilters> & { page?: number }) => {
    const query = { ...filters, ...changes }
    router.get(
      urls.index,
      {
        search: query.search || undefined,
        type: query.type.length ? query.type : undefined,
        owner: query.owner || undefined,
        orderby: query.orderby,
        order: query.order,
        view: query.view,
        page: changes.page,
      },
      { preserveState: true, preserveScroll: true, replace: true },
    )
  }

  const items: ListerItem[] = media.data.map((item) => ({
    id: item.id,
    name: item.name,
    url: item.url,
    thumbnailUrl: item.thumb ?? undefined,
    alt: item.alt ?? "",
    type: item.mime_type,
    size: item.size,
  }))

  const byId = new Map(media.data.map((item) => [item.id, item]))
  const deletable = selected.filter((id) => byId.get(Number(id))?.can.delete)

  const bulkDelete = () => {
    if (!deletable.length || !window.confirm(t("delete_many_confirm"))) return
    router.put(urls.bulk, { action: "delete", media: deletable }, { preserveScroll: true, onSuccess: () => setSelected([]) })
  }

  const sort = `${filters.orderby}:${filters.order}`

  const toolbar = (
    <>
      {props.file_types.length > 1 ? (
        <select
          className="mp-input"
          aria-label={t("type")}
          value={filters.type[0] ?? ""}
          onChange={(event) => visit({ type: event.target.value ? [event.target.value] : [], page: 1 })}
        >
          <option value="">{t("all_types")}</option>
          {props.file_types.map((type) => (
            <option key={type.value} value={type.value}>
              {type.label}
            </option>
          ))}
        </select>
      ) : null}
      {can.view_others ? (
        <select
          className="mp-input"
          aria-label={t("uploaded_by")}
          value={filters.owner ?? ""}
          onChange={(event) => visit({ owner: event.target.value === "mine" ? "mine" : null, page: 1 })}
        >
          <option value="">{t("anyone")}</option>
          <option value="mine">{t("only_mine")}</option>
        </select>
      ) : null}
      <select
        className="mp-input"
        aria-label={t("sort_by")}
        value={sort}
        onChange={(event) => {
          const [orderby, order] = event.target.value.split(":") as [LibraryFilters["orderby"], LibraryFilters["order"]]
          visit({ orderby, order, page: 1 })
        }}
      >
        <option value="created_at:desc">{t("newest")}</option>
        <option value="created_at:asc">{t("oldest")}</option>
        <option value="name:asc">{t("name_a_z")}</option>
        <option value="size:desc">{t("largest")}</option>
      </select>
      {deletable.length ? (
        <Button type="button" color="danger" onClick={bulkDelete}>
          <Icon name="trash-2" /> {t("delete_selected")} ({deletable.length})
        </Button>
      ) : null}
    </>
  )

  return (
    <div className="mp-library flex flex-col gap-6" data-mediapicker>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">
          <bdi>{props.title}</bdi>
        </h1>
        {can.create ? (
          <LinkButton url={urls.create}>
            <Icon name="upload" /> {t("upload_media")}
          </LinkButton>
        ) : null}
      </div>

      <Flash />

      <MediaLister
        items={items}
        view={filters.view}
        onViewChange={(view) => visit({ view })}
        search={search}
        onSearchChange={(value) => {
          setSearch(value)
          clearTimeout(timer.current)
          timer.current = setTimeout(() => visit({ search: value, page: 1 }), 300)
        }}
        searchThaana
        selectionMode="multiple"
        selected={selected}
        onSelectedChange={setSelected}
        renderActions={(item) => {
          const full = byId.get(Number(item.id))
          if (!full?.links) return null
          return (
            <Link
              href={full.can.update ? full.links.edit : full.links.show}
              className="mp-icon-button"
              aria-label={`${full.can.update ? t("edit") : t("view")}: ${full.name}`}
            >
              <Icon name={full.can.update ? "pencil" : "external-link"} />
            </Link>
          )
        }}
        page={media.meta.current_page}
        pageCount={media.meta.last_page}
        onPageChange={(page) => visit({ page })}
        emptyMessage={t(filters.search || filters.type.length || filters.owner ? "no_matching_media" : "no_media")}
        toolbar={toolbar}
        label={t("media_library")}
      />
    </div>
  )
}
