<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Mediapicker\Concerns\OwnsMedia;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Tests\TestSupport\Factories\UserFactory;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class User extends Authenticatable implements MediaOwner
{
    use HasFactory;
    use InteractsWithMedia;
    use OwnsMedia;

    protected static function newFactory()
    {
        return new UserFactory();
    }

    public function registerMediaConversions(?Media $media = null): void {
        $this->registerMediapickerConversions($media);
    }

    public function registerMediaCollections(): void {
        $this->registerMediapickerCollections();
    }
}
