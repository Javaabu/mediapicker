<?php

namespace Javaabu\Mediapicker\Models\Translatable;

use Javaabu\Mediapicker\Models\Media as BaseMedia;
use Javaabu\Translatable\Contracts\JsonTranslatable;
use Javaabu\Translatable\JsonTranslatable\IsJsonTranslatable;

/**
 * Library media whose name, description, alt text and caption can be translated with
 * javaabu/translatable. Used when translatable is on and `mediapicker.media_model` isn't
 * set. PHP only loads it when it's referenced, so it's safe without translatable.
 */
class Media extends BaseMedia implements JsonTranslatable
{
    use IsJsonTranslatable;

    /** @var array<int, string> */
    protected array $translatable = ['name', 'description', 'alt', 'caption'];

    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'description',
        'alt',
        'caption',
        'lang',
    ];

    /**
     * Keep morph types pointing at the base model
     */
    public function getMorphClass(): string
    {
        return BaseMedia::class;
    }
}
