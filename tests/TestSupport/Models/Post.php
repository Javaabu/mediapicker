<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Concerns\InteractsWithAttachments;
use Javaabu\Mediapicker\Contracts\HasAttachments;
use Javaabu\Mediapicker\Tests\TestSupport\Factories\PostFactory;

class Post extends Model implements HasAttachments
{
    use InteractsWithAttachments;
    use HasFactory;

    protected static function newFactory()
    {
        return new PostFactory();
    }
}
