import { Button, Card, LinkButton } from "@javaabu/forms"
import { StencilForm, type StencilSchema } from "@javaabu/stencil"
import { router } from "@inertiajs/react"
import { useMemo, useState } from "react"

import { translator } from "../../strings"
import type { MediaEditProps } from "../../types"
import { Flash } from "../../react/Flash"
import { Icon } from "../../react/Icon"
import { MediaCropper } from "../../react/MediaCropper"
import { MediaPreview } from "../../react/MediaPreview"

/** mediapicker::Media/Edit: the details form (stencil), crops and the preview */
export default function Edit(props: MediaEditProps) {
  const t = useMemo(() => translator(props.strings), [props.strings])
  const [media, setMedia] = useState(props.media)

  const destroy = () => {
    if (!props.urls.destroy || !window.confirm(t("delete_confirm"))) return
    router.delete(props.urls.destroy)
  }

  return (
    <div className="mp-library flex flex-col gap-6" data-mediapicker>
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h1 className="text-2xl font-semibold">
          <bdi>{props.title}</bdi>
        </h1>
        <div className="flex flex-wrap gap-2">
          <LinkButton url={props.urls.index} color="secondary" size="sm" startIcon="chevron-left">
            {t("back_to_library")}
          </LinkButton>
          {props.urls.show ? (
            <LinkButton url={props.urls.show} color="secondary" size="sm">
              {t("view")}
            </LinkButton>
          ) : null}
          {props.can.delete ? (
            <Button type="button" color="danger" size="sm" onClick={destroy}>
              <Icon name="trash-2" /> {t("delete")}
            </Button>
          ) : null}
        </div>
      </div>

      <Flash />

      <div className="grid gap-6 lg:grid-cols-2">
        <Card title={t("preview")}>
          <MediaPreview media={media} />
        </Card>

        <Card title={t("details")}>
          <StencilForm schema={props.form as StencilSchema} />
        </Card>
      </div>

      {media.type === "image" && props.crop_presets.length && props.can.update ? (
        <Card title={t("crops")} subtitle={t("crop_help")}>
          <MediaCropper
            url={props.urls.index}
            media={media}
            presets={props.crop_presets}
            strings={props.strings}
            onCropped={setMedia}
          />
        </Card>
      ) : null}
    </div>
  )
}
