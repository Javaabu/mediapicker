<?php

namespace Javaabu\Mediapicker\Models;

use Illuminate\Database\Query\Builder;
use Javaabu\Helpers\Media\AllowedMimeTypes;
use Spatie\Image\Image;
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
    protected static array $ignoreChangedAttributes = ['created_at', 'updated_at'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'description',
        'name',
    ];

    /**
     * The attributes that are searchable.
     *
     * @var array
     */
    protected $searchable = [
        'name',
        'description',
    ];

    /**
     * With relations scope
     *
     * @param $query
     * @return
     */
    public function scopeWithRelations($query)
    {
        return $query->with('model');
    }

    /**
     * User visible
     *
     * @param $query
     * @return mixed
     */
    public function scopeUserVisible($query)
    {
        // first try admin
        $admin = auth()->user() instanceof User ?
            auth()->user() :
            auth()->guard('web_admin')->user();

        if ($admin) {
            if ($admin->can('create', static::class)) {
                if ($admin->can('edit_other_users_media')) {
                    // can view all
                    return $query;
                } else {
                    return $query->whereModelType($admin->getMorphClass())
                                 ->whereModelId($admin->id);
                }
            }
        }

        // cant view anything
        return $query->whereId(-1);
    }


    /**
     * Get type attribute
     */
    public function getTypeSlugAttribute(): ?string
    {
        return AllowedMimeTypes::getType($this->mime_type);
    }

    /**
     * Get icon attribute
     */
    public function getIconAttribute()
    {
        $icon = AllowedMimeTypes::getIcon($this->mime_type);

        return 'zmdi zmdi-' . ($icon ?: 'file');
    }

    /**
     * Get web icon attribute
     *
     * @return string
     */
    public function getWebIconAttribute()
    {
        $icon = AllowedMimeTypes::getWebIcon($this->mime_type);

        return 'fa fa-' . ($icon ?: 'file');
    }

    /**
     * Type scope
     */
    public function scopeHasType(Builder $query, $type)
    {
        return $query->whereIn('mime_type', AllowedMimeTypes::getAllowedMimeTypes($type));
    }



    /**
     * Get the width attribute
     */
    public function getWidthAttribute()
    {
        if (! $this->hasCustomProperty('width')) {
            $this->saveDimensions();
        }

        return $this->getCustomProperty('width');
    }

    /**
     * Save the image dimensions
     */
    public function saveDimensions()
    {
        $image = Image::load($this->getUrl());

        $this->setCustomProperty('width', $image->getWidth());
        $this->setCustomProperty('height', $image->getHeight());
        $this->save();
    }

    /**
     * Get the height attribute
     */
    public function getHeightAttribute()
    {
        if (! $this->hasCustomProperty('height')) {
            $this->saveDimensions();
        }

        return $this->getCustomProperty('height');
    }

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
