<?php

namespace Javaabu\Mediapicker\Models;

use Illuminate\Database\Eloquent\Builder;
use Javaabu\Helpers\Media\AllowedMimeTypes;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Illuminate\Support\Str;
use Javaabu\Helpers\AdminModel\AdminModel;
use Javaabu\Helpers\AdminModel\IsAdminModel;
use Javaabu\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

class Media extends BaseMedia implements AdminModel
{
    use LogsActivity;
    use IsAdminModel;

    /**
     * The attributes that would be logged
     *
     * @var array
     */
    protected static array $logAttributes = ['*'];

    /**
     * Changes to these attributes only will not trigger a log
     *
     * @var array
     */
    protected static array $ignoreChangedAttributes = [
        'created_at',
        'updated_at'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
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
        return AllowedMimeTypes::getType($this->mime_type);
    }

    public function getIcon(string $icon_pack = '', bool $with_prefix = true): string
    {
        return AllowedMimeTypes::getIcon($this->mime_type, $icon_pack, $with_prefix);
    }

    public function scopeHasFileType(Builder $query, array|string $type): void
    {
        $query->whereIn('mime_type', AllowedMimeTypes::getAllowedMimeTypes($type));
    }

    public function scopeSearch($query, $search): mixed
    {
        return $query->where('name', 'like', '%'.$search.'%')
             ->orWhere('custom_properties->description', 'like', '%'.$search.'%');
    }

    public function scopeUserVisible(Builder $query): void
    {
        $user = auth()->user();

        if ($user instanceof MediaOwner && $user->canViewAnyMedia()) {
            $query->whereCollectionName($user->getMediapickerCollectionName());

            if (! $user->canViewOthersMedia()) {
                // can view only own
                $query->whereModelType($user->getMorphClass())
                      ->whereModelId($user->getKey());
            }

            return;
        }

        // cant view anything
        $query->where($this->getKeyName(), -1);
    }

    /**
     * Save the image dimensions
     */
    /*public function saveDimensions()
    {
        $image = Image::load($this->getUrl());

        $this->setCustomProperty('width', $image->getWidth());
        $this->setCustomProperty('height', $image->getHeight());
        $this->save();
    }*/



    public function getShortNameAttribute()
    {
        return Str::limit($this->name, 15);
    }

    /**
     * Get the admin url attribute
     */
    public function getAdminUrlAttribute(): string
    {
        return route('admin.media.edit', $this);
    }
}
