<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Mediapicker\Tests\TestSupport\Factories\UserFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected static function newFactory()
    {
        return new UserFactory();
    }
}
