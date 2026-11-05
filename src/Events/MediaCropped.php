<?php

namespace Javaabu\Mediapicker\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Javaabu\Mediapicker\Models\Media;

/**
 * A media's crop for a preset was saved
 */
class MediaCropped
{
    use Dispatchable;

    /**
     * @param  array{x: int, y: int, width: int, height: int, rotation: int}  $crop
     */
    public function __construct(
        public Media $media,
        public string $preset,
        public array $crop,
    ) {}
}
