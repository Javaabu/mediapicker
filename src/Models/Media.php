<?php

namespace Javaabu\Mediapicker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\FileTypes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'description',
    ];

    protected function setDescriptionAttribute(?string $value = null)
    {
        $this->setCustomProperty('description', $value);
    }

    protected function getDescriptionAttribute(): ?string
    {
        return $this->getCustomProperty('description');
    }

    protected function setWidthAttribute(?int $value = null)
    {
        $this->setCustomProperty('width', $value);
    }

    protected function getWidthAttribute(): ?int
    {
        return $this->getCustomProperty('width');
    }

    protected function setHeightAttribute(?int $value = null)
    {
        $this->setCustomProperty('height', $value);
    }

    protected function getHeightAttribute(): ?int
    {
        return $this->getCustomProperty('height');
    }

    protected function getFileTypeAttribute(): ?string
    {
        return FileTypes::typeOf($this->mime_type);
    }

    public function getIcon(): string
    {
        return FileTypes::icon($this->mime_type);
    }

    public function scopeHasFileType(Builder $query, array|string $type): void
    {
        $query->whereIn('mime_type', FileTypes::mimeTypes($type));
    }

    public function scopeSearch($query, $search): mixed
    {
        return $query->where('name', 'like', '%' . $search . '%')
            ->orWhere('custom_properties->description', 'like', '%' . $search . '%');
    }

    public function scopeFindForController(Builder $query, int|string $media_id): void
    {
        $user = auth()->user();

        if ($user instanceof MediaOwner) {
            $query->whereCollectionName($user->getMediapickerCollectionName())
                ->whereModelType($user->getMorphClass());

            return;
        }

        // cant view anything
        $query->where($this->getKeyName(), -1);
    }

    public function scopeUserVisible(Builder $query): void
    {
        $user = auth()->user();

        if ($user instanceof MediaOwner && $user->canViewAnyMedia()) {
            $query->whereCollectionName($user->getMediapickerCollectionName())
                ->whereModelType($user->getMorphClass());

            if (! $user->canViewOthersMedia()) {
                // can view only own
                $query->whereModelId($user->getKey());
            }

            return;
        }

        // cant view anything
        $query->where($this->getKeyName(), -1);
    }

    public function getShortNameAttribute()
    {
        return Str::limit($this->name, 15);
    }

    public function url(string $action, ?BaseMedia $media = null): string
    {
        $media = $media ?: $this;
        $controller = Mediapicker::mediaController();
        $parameters = in_array($action, ['show', 'edit', 'update', 'destroy']) ? $media : [];

        return action([$controller, $action], $parameters);
    }

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return $this->url('show');
    }
}
