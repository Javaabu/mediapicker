<?php

namespace Javaabu\Mediapicker\Tests\TestSupport\Policies;

use Illuminate\Database\Eloquent\Model;

class DenyAllPolicy
{
    public function viewAny(Model $user): bool
    {
        return false;
    }

    public function viewOthers(Model $user): bool
    {
        return false;
    }
}
