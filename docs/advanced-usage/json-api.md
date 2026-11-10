---
title: JSON API
sidebar_position: 2
---

Every library route answers JSON when the request expects it (`Accept: application/json`). The pickers, the editor blocks and the React pages use it; your own front end can too. Requests are authorized like the pages, against the guard of the routes.

| Request | Response |
| --- | --- |
| `GET /media?search=&type[]=&owner=mine&orderby=&order=&per_page=&page=` | A paginated `MediaResource` collection (`data`, `links`, `meta`) |
| `POST /media` (multipart: `file`, `name`, `description`, `alt`, `caption`, `type`, `crops[preset][x…]`) | `201` with the new media |
| `GET /media/{id}` | The media |
| `PATCH /media/{id}` (`name`, `description`, `alt`, `caption`) | The media |
| `PUT /media/{id}/crops/{preset}` (`x`, `y`, `width`, `height`, `rotation`) | The media |
| `DELETE /media/{id}` | `204` |
| `PUT /media` (`action=delete`, `media[]`) | `{"deleted": [ids]}` |

`type` on an upload limits what may be uploaded (one type or `type[]`). With javaabu/translatable, details are sent per locale: `name_en`, `name_dv`, ….

# MediaResource

```json
{
  "id": 12,
  "uuid": "…",
  "name": "ހުޅުމާލޭގެ ބީޗް",
  "file_name": "beach.jpg",
  "mime_type": "image/jpeg",
  "type": "image",
  "icon": "image",
  "size": 245760,
  "human_size": "240 KB",
  "width": 1600,
  "height": 1067,
  "description": null,
  "alt": "ބީޗް",
  "caption": null,
  "url": "https://example.com/storage/12/beach.jpg",
  "thumb": "https://example.com/storage/12/conversions/beach-mediapicker-thumb.jpg",
  "preview": "https://example.com/storage/12/conversions/beach-mediapicker-large.jpg",
  "crops": {"wide": {"x": 0, "y": 83, "width": 1600, "height": 900, "rotation": 0}},
  "translations": null,
  "owner": {"id": 3, "name": "Ahmed"},
  "created_at": "2026-11-05T10:00:00+05:00",
  "updated_at": "2026-11-05T10:00:00+05:00",
  "can": {"update": true, "delete": true},
  "links": {"show": "…/media/12", "edit": "…/media/12/edit", "update": "…/media/12", "destroy": "…/media/12"}
}
```

`thumb` and `preview` fall back to the original for images until the library conversions are made; they're null for other files. `translations` has the values per locale (`name_en`, `name_dv`, …) when translatable is on. `MediaResource::make($media)->forLibrary('admin.media')` links to another library.

# From JavaScript

```ts
import { createLibraryClient, LibraryError } from "@javaabu/mediapicker"

const library = createLibraryClient("/admin/media")

const page = await library.list({ search: "ބީޗް", type: "image", page: 1 })
const media = await library.upload(file, { alt: "ބީޗް", onProgress: (fraction) => console.log(fraction) })
await library.update(media.id, { caption: "ފޮޓޯ: އަޙްމަދު" })
await library.crop(media.id, "wide", { x: 0, y: 83, width: 1600, height: 900 })
await library.destroy(media.id)
```

It sends the XSRF cookie (or the `csrf-token` meta tag), and throws `LibraryError` with Laravel's validation `errors`.
