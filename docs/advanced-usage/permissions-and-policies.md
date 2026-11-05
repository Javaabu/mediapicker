---
title: Permissions and policies
sidebar_position: 3
---

# Permissions

The package registers the `media` group with javaabu/permissions (labelled "Media"). `php artisan permissions:sync` creates the permissions for every guard in `config('permissions.guards')`; give them to roles as usual.

| Permission | Description |
| --- | --- |
| `view_media` | View the media library |
| `view_others_media` | View media uploaded by others |
| `edit_media` | Upload media and edit your own |
| `edit_others_media` | Edit media uploaded by others |
| `delete_media` | Delete your own media |
| `delete_others_media` | Delete media uploaded by others |

Their descriptions are translated (`lang/dv.json`). Turn registration off with `'register_permissions' => false` and register your own.

# The policy

`Javaabu\Mediapicker\Policies\MediaPolicy` extends javaabu/permissions' `ResourcePolicy` with ownership:

| Ability | Allowed when |
| --- | --- |
| `viewAny` | `view_media` |
| `viewOthers` | `view_others_media` |
| `view` | `view_media`, and `view_others_media` unless the user uploaded it |
| `create` | `edit_media`, and the user is a `MediaOwner` |
| `update` | `edit_others_media`, or `edit_media` and the user uploaded it |
| `delete` | `delete_others_media`, or `delete_media` and the user uploaded it |
| `restore`, `forceDelete`, `viewTrash` | never |

Every ability also needs the media to be in the library of the user's kind of user: an admin is never allowed a user's upload.

The policy is checked against the user of the guard the routes were registered for. `Media::query()->visibleTo($user)` uses `viewOthers` to list what a user may see, and `LibraryMedia` uses it to validate picked ids.

# Overriding

In order of precedence:

```php
// config/permissions.php
'policies' => [Javaabu\Mediapicker\Models\Media::class => App\Policies\MediaPolicy::class],

// or your AuthServiceProvider / AppServiceProvider
Gate::policy(Javaabu\Mediapicker\Models\Media::class, App\Policies\MediaPolicy::class);

// or config/mediapicker.php
'policy' => App\Policies\MediaPolicy::class,
```

Extend the package's policy to change one ability:

```php
class MediaPolicy extends \Javaabu\Mediapicker\Policies\MediaPolicy
{
    public function delete(Model $user, Model $media): bool
    {
        return parent::delete($user, $media) && $media->created_at->isAfter(now()->subDay());
    }
}
```

# Several guards

Register a library per guard; each checks its own guard's permissions and shows its own users' uploads.

```php
Route::middleware('web')->group(function () {
    Route::mediapicker(['guard' => 'web_admin', 'prefix' => 'admin/media', 'name' => 'admin.media']);
    Route::mediapicker(['guard' => 'web', 'prefix' => 'account/media', 'name' => 'account.media']);
});
```
