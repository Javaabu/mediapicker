<?php

namespace Javaabu\Mediapicker\Policies;

use Javaabu\Activitylog\Models\Activity;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(MediaOwner $user): bool
    {
        return $user->canViewAnyMedia();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(MediaOwner $user, Media $media): bool
    {
        return $user->canViewMedia($media);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(MediaOwner $user): bool
    {
        return $user->canCreateMedia();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(MediaOwner $user, Media $media): bool
    {
        return $user->canEditMedia($media);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(MediaOwner $user, Media $media): bool
    {
        return $user->canDeleteMedia($media);
    }

    /**
     * Determine whether the user can view the model logs.
     */
    public function viewLogs(MediaOwner $user, Media $media): bool
    {
        return $user->can('viewAny', Activity::class) && $this->update($user, $media);
    }
}
