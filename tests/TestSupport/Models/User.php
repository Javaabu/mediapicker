<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Javaabu\Mediapicker\Concerns\OwnsMedia;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Tests\TestSupport\Factories\UserFactory;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements MediaOwner
{
    use HasFactory;
    use InteractsWithMedia;
    use OwnsMedia;

    protected static function newFactory()
    {
        return new UserFactory();
    }
}
