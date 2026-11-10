<?php

namespace Javaabu\Mediapicker\Policies;

use Illuminate\Database\Eloquent\Model;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Permissions\Policies\ResourcePolicy;

/**
 * Who may use the media library
 *
 * | Ability    | Permission                                                         |
 * | ---------- | ------------------------------------------------------------------ |
 * | viewAny    | view_media                                                         |
 * | viewOthers | view_others_media                                                  |
 * | view       | view_media, and view_others_media unless the user uploaded it       |
 * | create     | edit_media (uploading, which only owners can do)                   |
 * | update     | edit_others_media, or edit_media and the user uploaded it          |
 * | delete     | delete_others_media, or delete_media and the user uploaded it      |
 *
 * Media in another owner type's library (an admin's upload, for a user) is never allowed.
 * Override it with `config('mediapicker.policy')`, `config('permissions.policies')` or
 * `Gate::policy()`.
 */
class MediaPolicy extends ResourcePolicy
{
    protected string $resource = 'media';

    protected bool $owned = true;

    /** @var array<string, string|array<int, string>> */
    protected array $abilities = [
        'viewOthers' => 'view_others_media',
    ];

    public function view(Model $user, Model $media): bool
    {
        if (! $this->inLibrary($user, $media)) {
            return false;
        }

        if (! $this->allows($user, $this->permission('view'))) {
            return false;
        }

        return $this->isOwner($user, $media) || $this->allows($user, $this->permission('view_others'));
    }

    public function create(Model $user): bool
    {
        return $user instanceof MediaOwner && parent::create($user);
    }

    public function update(Model $user, Model $media): bool
    {
        return $this->inLibrary($user, $media) && parent::update($user, $media);
    }

    public function delete(Model $user, Model $media): bool
    {
        return $this->inLibrary($user, $media) && parent::delete($user, $media);
    }

    public function restore(Model $user, Model $media): bool
    {
        return false;
    }

    public function forceDelete(Model $user, Model $media): bool
    {
        return false;
    }

    public function viewTrash(Model $user): bool
    {
        return false;
    }

    /**
     * Whether the media is in the library of the user's kind of owner
     */
    protected function inLibrary(Model $user, Model $media): bool
    {
        if (! $user instanceof MediaOwner) {
            return false;
        }

        return $media->getAttribute('model_type') === $user->getMorphClass()
            && $media->getAttribute('collection_name') === $user->getMediapickerCollectionName();
    }

    protected function isOwner(Model $user, Model $model): bool
    {
        return $model instanceof Media && $model->isOwnedBy($user);
    }
}
