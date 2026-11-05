<?php

namespace Javaabu\Mediapicker\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Support\FileTypes;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;

/**
 * A file in an owner's media library
 *
 * Name is a column; description, alt text, caption, width, height and crops are kept in
 * spatie's custom properties.
 *
 * @property ?string $description
 * @property ?string $alt
 * @property ?string $caption
 * @property ?int $width
 * @property ?int $height
 * @property-read ?string $file_type
 */
class Media extends BaseMedia
{
    /** @var array<int, string> */
    protected $fillable = [
        'name',
        'description',
        'alt',
        'caption',
    ];

    protected function setDescriptionAttribute(?string $value = null): void
    {
        $this->setCustomProperty('description', $value);
    }

    protected function getDescriptionAttribute(): ?string
    {
        return $this->getCustomProperty('description');
    }

    protected function setAltAttribute(?string $value = null): void
    {
        $this->setCustomProperty('alt', $value);
    }

    protected function getAltAttribute(): ?string
    {
        return $this->getCustomProperty('alt');
    }

    protected function setCaptionAttribute(?string $value = null): void
    {
        $this->setCustomProperty('caption', $value);
    }

    protected function getCaptionAttribute(): ?string
    {
        return $this->getCustomProperty('caption');
    }

    protected function setWidthAttribute(?int $value = null): void
    {
        $this->setCustomProperty('width', $value);
    }

    protected function getWidthAttribute(): ?int
    {
        return $this->getCustomProperty('width');
    }

    protected function setHeightAttribute(?int $value = null): void
    {
        $this->setCustomProperty('height', $value);
    }

    protected function getHeightAttribute(): ?int
    {
        return $this->getCustomProperty('height');
    }

    protected function getFileTypeAttribute(): ?string
    {
        return FileTypes::typeOf($this->mime_type);
    }

    protected function getShortNameAttribute(): string
    {
        return Str::limit($this->name, 15);
    }

    /**
     * A Lucide icon name for the file type
     */
    public function getIcon(): string
    {
        return FileTypes::icon($this->mime_type);
    }

    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    /**
     * The alt text, falling back to the name for images without one
     */
    public function altText(): string
    {
        return $this->alt ?: (string) $this->name;
    }

    /**
     * Whether the user uploaded this media
     */
    public function isOwnedBy(?Model $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->model_type === $user->getMorphClass()
            && (string) $this->model_id === (string) $user->getKey();
    }

    /**
     * @param  string|array<int, string>  $type
     */
    public function scopeHasFileType(Builder $query, array|string $type): void
    {
        $query->whereIn('mime_type', FileTypes::mimeTypes($type));
    }

    public function scopeSearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $query) use ($search) {
            $query->where('name', 'like', "%{$search}%")
                ->orWhere('file_name', 'like', "%{$search}%")
                ->orWhere('custom_properties->description', 'like', "%{$search}%")
                ->orWhere('custom_properties->alt', 'like', "%{$search}%");
        });
    }

    /**
     * The library media of the user's kind of owner (the user's own library collection and
     * morph type), whoever uploaded it
     */
    public function scopeInLibraryOf(Builder $query, ?Model $user): void
    {
        if (! $user instanceof MediaOwner) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where('collection_name', $user->getMediapickerCollectionName())
            ->where('model_type', $user->getMorphClass());
    }

    /**
     * The library media the user may see: the library of the user's kind of owner, limited to
     * the user's own uploads unless the policy's `viewOthers` allows more
     */
    public function scopeVisibleTo(Builder $query, ?Model $user): void
    {
        $query->inLibraryOf($user);

        if (! $user) {
            return;
        }

        if ($user->can('viewOthers', static::class)) {
            return;
        }

        $query->where('model_id', $user->getKey());
    }

    /**
     * Only the user's own uploads
     */
    public function scopeOwnedBy(Builder $query, Model $user): void
    {
        $query->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey());
    }

    /**
     * A URL of the library pages for this media: `index`, `create`, `show`, `edit`, `update`,
     * `destroy`. Uses the routes of the current request's library, else the default library.
     */
    public function url(string $action, ?BaseMedia $media = null): string
    {
        return Mediapicker::url($action, $media ?: $this);
    }

    protected function getAdminUrlAttribute(): string
    {
        return $this->url('show');
    }
}
