<?php

namespace Javaabu\Mediapicker\Conversions;

use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Javaabu\Mediapicker\Contracts\Attachment;
use Spatie\MediaLibrary\Conversions\Conversion;
use Spatie\MediaLibrary\Conversions\ConversionCollection;

/**
 * @template TKey of array-key
 * @template TValue of Conversion
 *
 * @extends ConversionCollection<TKey, TValue>
 */
class AttachmentConversionCollection extends ConversionCollection
{
    public static function createForAttachment(Attachment $attachment): self
    {
        return (new static)->setAttachment($attachment);
    }

    /**
     * @return $this
     */
    public function setAttachment(Attachment $attachment): self
    {
        $media = $attachment->media;

        $this->media = $media;

        $this->items = [];

        $this->addAttachmentConversionsFromRelatedModel($attachment);

        return $this;
    }

    protected function addAttachmentConversionsFromRelatedModel(Attachment $attachment): void
    {
        $media = $attachment->media;
        $modelName = Arr::get(Relation::morphMap(), $attachment->model_type, $attachment->model_type);

        if (! class_exists($modelName)) {
            return;
        }

        /** @var \Javaabu\Mediapicker\Contracts\HasAttachments $model */
        $model = new $modelName;

        /*
         * In some cases the user might want to get the actual model
         * instance so conversion parameters can depend on model
         * properties. This will causes extra queries.
         */
        if ($model->registerAttachmentConversionsUsingModelInstance && $attachment->model) {
            $model = $attachment->model;

            $model->attachmentConversions = [];
        }

        $model->registerAllAttachmentConversions($media);

        $this->items = $model->attachmentConversions;
    }
}
