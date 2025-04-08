<?php

namespace Javaabu\Mediapicker\AttachmentCollections;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Javaabu\Mediapicker\Contracts\Attachment;
use Javaabu\Mediapicker\Contracts\HasAttachments;

class AttachmentRepository
{
    public function __construct(
        protected Attachment $model
    ) {}

    /**
     * Get all media in the collection.
     */
    public function getCollection(
        HasAttachments $model,
        string $collectionName,
        array|callable $filter = []
    ): Collection {
        return $this->applyFilterToAttachmentCollection($model->loadAttachments($collectionName), $filter);
    }

    /**
     * Apply given filters on media.
     */
    protected function applyFilterToAttachmentCollection(
        Collection $media,
        array|callable $filter
    ): Collection {
        if (is_array($filter)) {
            $filter = $this->getDefaultFilterFunction($filter);
        }

        return $media->filter($filter);
    }

    public function all(): LazyCollection
    {
        return $this->query()->cursor();
    }

    public function allIds(): Collection
    {
        return $this->query()->pluck($this->model->getKeyName());
    }

    public function getByModelType(string $modelType): LazyCollection
    {
        return $this->query()->where('model_type', $modelType)->cursor();
    }

    public function getByIds(array $ids): LazyCollection
    {
        return $this->query()->whereIn($this->model->getKeyName(), $ids)->cursor();
    }

    public function getByIdGreaterThan(int $startingFromId, bool $excludeStartingId = false, string $modelType = ''): LazyCollection
    {
        return $this->query()
            ->where($this->model->getKeyName(), $excludeStartingId ? '>' : '>=', $startingFromId)
            ->when($modelType !== '', fn (Builder $q) => $q->where('model_type', $modelType))
            ->cursor();
    }

    public function getByModelTypeAndCollectionName(string $modelType, string $collectionName): LazyCollection
    {
        return $this->query()
            ->where('model_type', $modelType)
            ->where('collection_name', $collectionName)
            ->cursor();
    }

    public function getByCollectionName(string $collectionName): LazyCollection
    {
        return $this->query()
            ->where('collection_name', $collectionName)
            ->cursor();
    }

    public function getOrphans(): LazyCollection
    {
        return $this->orphansQuery()
            ->cursor();
    }

    public function getOrphansByCollectionName(string $collectionName): LazyCollection
    {
        return $this->orphansQuery()
            ->where('collection_name', $collectionName)
            ->cursor();
    }

    protected function query(): Builder
    {
        return $this->model->newQuery();
    }

    protected function orphansQuery(): Builder
    {
        return $this->query()->where(fn (Builder $query) => $query->whereDoesntHave(
            'model',
            fn (Builder $q) => $q->hasMacro('withTrashed') ? $q->withTrashed() : $q,
        ));
    }

    protected function getDefaultFilterFunction(array $filters): Closure
    {
        return function (Attachment $attachment) use ($filters) {
            $media = $attachment->media;

            foreach ($filters as $property => $value) {
                if (! Arr::has($media->custom_properties, $property)) {
                    return false;
                }

                if (Arr::get($media->custom_properties, $property) !== $value) {
                    return false;
                }
            }

            return true;
        };
    }
}
