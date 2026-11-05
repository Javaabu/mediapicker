<?php

namespace Javaabu\Mediapicker\Http\Controllers;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Cropping\Cropper;
use Javaabu\Mediapicker\Http\Requests\StoreMediaRequest;
use Javaabu\Mediapicker\Http\Requests\UpdateMediaRequest;
use Javaabu\Mediapicker\Http\Resources\MediaResource;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Javaabu\Mediapicker\Routing\MediapickerRoutes;
use Javaabu\Mediapicker\Stencil\MediaDetailsForm;
use Javaabu\Mediapicker\Support\CropPresets;
use Javaabu\Mediapicker\Support\FileTypes;
use Javaabu\Mediapicker\Support\Strings;
use Javaabu\Mediapicker\Support\Translations;
use Javaabu\Mediapicker\Views\ViewRenderer;
use Spatie\Image\Image;
use Throwable;

/**
 * The media library: the library pages, uploads, details, crops and bulk deletes
 *
 * Every action answers JSON when the request expects it (the pickers use that), and a Blade
 * or Inertia page otherwise. Override it with `config('mediapicker.media_controller')`, the
 * `controller` option of `Route::mediapicker()`, or by binding a subclass in the container.
 */
class MediaController extends Controller
{
    /** The orders the library can be sorted by */
    public const array ORDERBYS = ['created_at', 'name', 'size', 'id'];

    public function __construct(
        protected ViewRenderer $views,
    ) {}

    public function index(Request $request): View|Responsable|JsonResponse
    {
        $this->authorizeAbility($request, 'viewAny', Mediapicker::mediaModel());

        $filters = $this->filters($request);

        $media = $this->query($request, $filters)
            ->with('model')
            ->paginate($filters['per_page'])
            ->withQueryString();

        if ($request->expectsJson()) {
            return $this->resourceCollection($request, $media)->response();
        }

        return $this->views->render('media.index', [
            'media_items' => $this->views->isInertia() ? $this->resourceCollection($request, $media) : $media,
            'filters' => $filters,
            ...$this->pageData($request),
            'title' => $filters['search']
                ? __('Media matching ":search"', ['search' => $filters['search']])
                : __('Media library'),
        ]);
    }

    public function create(Request $request): View|Responsable
    {
        $this->authorizeAbility($request, 'create', Mediapicker::mediaModel());

        return $this->views->render('media.create', [
            ...$this->pageData($request),
            'title' => __('Upload media'),
        ]);
    }

    public function store(StoreMediaRequest $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAbility($request, 'create', Mediapicker::mediaModel());

        /** @var MediaOwner $user */
        $user = $request->user();

        $media = $this->addToLibrary($user, $request->file('file'), $request);

        if ($request->expectsJson()) {
            return $this->resource($request, $media)->response()->setStatusCode(201);
        }

        return redirect()
            ->to($this->url($request, 'edit', $media))
            ->with('success', __('The file was uploaded.'));
    }

    public function show(Request $request, string $media): View|Responsable|JsonResponse
    {
        $media = $this->findMedia($request, $media);

        $this->authorizeAbility($request, 'view', $media);

        if ($request->expectsJson()) {
            return $this->resource($request, $media)->response();
        }

        return $this->views->render('media.show', [
            'media' => $this->views->isInertia() ? $this->resource($request, $media)->resolve($request) : $media,
            ...$this->pageData($request, $media),
            'title' => $media->name,
        ]);
    }

    public function edit(Request $request, string $media): View|Responsable
    {
        $media = $this->findMedia($request, $media);

        $this->authorizeAbility($request, 'update', $media);

        return $this->views->render('media.edit', [
            'media' => $this->views->isInertia() ? $this->resource($request, $media)->resolve($request) : $media,
            'form' => $this->detailsForm($request, $media),
            ...$this->pageData($request, $media),
            'title' => __('Edit :name', ['name' => $media->name]),
        ]);
    }

    public function update(UpdateMediaRequest $request, string $media): JsonResponse|RedirectResponse
    {
        $media = $this->findMedia($request, $media);

        $this->authorizeAbility($request, 'update', $media);

        $media->fill($request->validated());
        $media->save();

        if ($request->expectsJson()) {
            return $this->resource($request, $media->refresh())->response();
        }

        return redirect()
            ->to($this->url($request, 'edit', $media))
            ->with('success', __('The details were saved.'));
    }

    public function destroy(Request $request, string $media): JsonResponse|RedirectResponse
    {
        $media = $this->findMedia($request, $media);

        $this->authorizeAbility($request, 'delete', $media);

        $media->delete();

        if ($request->expectsJson()) {
            return response()->json(null, 204);
        }

        return redirect()
            ->to($this->url($request, 'index'))
            ->with('success', __('The file was deleted.'));
    }

    /**
     * Save the crop of an image for a preset, and regenerate the conversions that use it
     */
    public function crop(Request $request, string $media, string $preset): JsonResponse|RedirectResponse
    {
        $media = $this->findMedia($request, $media);

        $this->authorizeAbility($request, 'update', $media);

        abort_unless(CropPresets::exists($preset), 404);
        abort_unless($media->isImage(), 422, __('Only images can be cropped.'));

        $crop = $request->validate(CropPresets::cropRules());

        app(Cropper::class)->crop($media, $preset, $crop);

        if ($request->expectsJson()) {
            return $this->resource($request, $media->refresh())->response();
        }

        return redirect()
            ->to($this->url($request, 'edit', $media))
            ->with('success', __('The crop was saved.'));
    }

    /**
     * The form for a media's details, defined once with javaabu/stencil
     */
    protected function detailsForm(Request $request, Media $media): MediaDetailsForm
    {
        return MediaDetailsForm::make()
            ->model($media)
            ->action($this->url($request, 'update', $media))
            ->method('PATCH');
    }

    /**
     * Bulk actions on the library: `action=delete` with `media[]`
     */
    public function bulk(Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeAbility($request, 'viewAny', Mediapicker::mediaModel());

        $validated = $request->validate([
            'action' => ['required', Rule::in(['delete'])],
            'media' => ['required', 'array', 'max:500'],
            'media.*' => ['integer'],
        ]);

        $media_items = $this->query($request)
            ->whereKey($validated['media'])
            ->get();

        abort_if($media_items->count() !== count(array_unique($validated['media'])), 422, __('Some of the selected files can\'t be found.'));

        $media_items->each(fn (Media $media) => $this->authorizeAbility($request, 'delete', $media));

        $media_items->each(fn (Media $media) => $media->delete());

        if ($request->expectsJson()) {
            return response()->json(['deleted' => $media_items->modelKeys()]);
        }

        return redirect()
            ->to($this->url($request, 'index', parameters: Arr::only($request->query(), ['view'])))
            ->with('success', trans_choice('{1} :count file was deleted.|[2,*] :count files were deleted.', $media_items->count()));
    }

    /**
     * Add an uploaded file to the user's library, with its details, size and crops
     */
    protected function addToLibrary(MediaOwner $user, UploadedFile $file, StoreMediaRequest $request): Media
    {
        $adder = $user->addMedia($file);

        $name = $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $adder->usingName(Str::limit($name, 255, ''));

        $custom_properties = array_filter(
            Arr::only($request->validated(), ['description', 'alt', 'caption']),
            fn ($value) => filled($value),
        );

        if (FileTypes::typeOf($file->getMimeType()) === 'image') {
            $custom_properties = [...$custom_properties, ...$this->dimensions($file)];
        }

        if ($crops = $request->validated('crops')) {
            $custom_properties['crops'] = array_map(fn (array $crop) => CropPresets::normalize($crop), $crops);
        }

        if ($custom_properties) {
            $adder->withCustomProperties($custom_properties);
        }

        /** @var Media $media */
        $media = $adder->toMediaCollection($user->getMediapickerCollectionName());

        if (Translations::supports($media)) {
            $media->fill(Arr::except($request->validated(), ['file', 'type', 'crops', 'name', 'description', 'alt', 'caption']));
            $media->save();
        }

        return $media;
    }

    /**
     * @return array{width?: int, height?: int}
     */
    protected function dimensions(UploadedFile $file): array
    {
        try {
            $image = Image::load($file->getRealPath());

            return ['width' => $image->getWidth(), 'height' => $image->getHeight()];
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * The library media the user may see, filtered
     *
     * @param  array<string, mixed>|null  $filters
     */
    protected function query(Request $request, ?array $filters = null): Builder
    {
        $media_class = Mediapicker::mediaModel();

        $query = $media_class::query()->visibleTo($request->user());

        if ($filters === null) {
            return $query;
        }

        $query->search($filters['search']);

        if ($filters['type']) {
            $query->hasFileType($filters['type']);
        }

        if ($filters['owner'] === 'mine' && $request->user()) {
            $query->ownedBy($request->user());
        }

        if ($filters['date_from']) {
            $query->where('created_at', '>=', $filters['date_from'] . ' 00:00:00');
        }

        if ($filters['date_to']) {
            $query->where('created_at', '<=', $filters['date_to'] . ' 23:59:59');
        }

        return $query->orderBy($filters['orderby'], $filters['order'])
            ->orderBy('id', $filters['order']);
    }

    /**
     * The filters of the library from the query string
     *
     * @return array{
     *     search: ?string,
     *     type: array<int, string>,
     *     owner: ?string,
     *     date_from: ?string,
     *     date_to: ?string,
     *     orderby: string,
     *     order: string,
     *     per_page: int,
     *     view: string
     * }
     */
    protected function filters(Request $request): array
    {
        $types = array_values(array_filter(
            Arr::wrap($request->query('type')),
            fn ($type) => is_string($type) && FileTypes::exists($type),
        ));

        $date = fn (string $key) => is_string($request->query($key)) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $request->query($key))
            ? $request->query($key)
            : null;

        [$sort_by, $sort_order] = array_pad(explode(':', (string) $request->query('sort', '')), 2, null);

        $orderby = $request->query('orderby', $sort_by);
        $orderby = in_array($orderby, self::ORDERBYS, true) ? $orderby : 'created_at';
        $order = $request->query('order', $sort_order);

        return [
            'search' => is_string($request->query('search')) ? trim($request->query('search')) : null,
            'type' => $types,
            'owner' => $request->query('owner') === 'mine' ? 'mine' : null,
            'date_from' => $date('date_from'),
            'date_to' => $date('date_to'),
            'orderby' => $orderby,
            'order' => $order === 'asc' ? 'asc' : 'desc',
            'per_page' => min(max($request->integer('per_page', config('mediapicker.per_page', 24)), 1), 100),
            'view' => $request->query('view') === 'list' ? 'list' : 'grid',
        ];
    }

    /**
     * What every page gets: permissions, URLs, file types, crop presets and strings
     *
     * @return array<string, mixed>
     */
    protected function pageData(Request $request, ?Media $media = null): array
    {
        $media_class = Mediapicker::mediaModel();
        $user = $request->user();

        return [
            'can' => [
                'create' => (bool) $user?->can('create', $media_class),
                'view_others' => (bool) $user?->can('viewOthers', $media_class),
                'update' => $media ? (bool) $user?->can('update', $media) : null,
                'delete' => $media ? (bool) $user?->can('delete', $media) : null,
            ],
            'urls' => $this->urls($request, $media),
            'file_types' => $this->fileTypeOptions(),
            'upload' => [
                'accept' => implode(',', FileTypes::mimeTypes()),
                'max_size' => FileTypes::maxSize(),
            ],
            'crop_presets' => CropPresets::forUi(),
            'translatable' => Translations::enabled(),
            'strings' => $this->views->isInertia() ? Strings::all() : null,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, icon: string}>
     */
    protected function fileTypeOptions(): array
    {
        return array_map(fn (string $type) => [
            'value' => $type,
            'label' => __(Str::headline($type)),
            'icon' => FileTypes::all()[$type]['icon'] ?? 'file',
        ], FileTypes::types());
    }

    /**
     * @return array<string, string>
     */
    protected function urls(Request $request, ?Media $media = null): array
    {
        $urls = [
            'index' => $this->url($request, 'index'),
            'create' => $this->url($request, 'create'),
            'store' => $this->url($request, 'store'),
            'bulk' => $this->url($request, 'bulk'),
        ];

        if ($media) {
            $urls = [
                ...$urls,
                'show' => $this->url($request, 'show', $media),
                'edit' => $this->url($request, 'edit', $media),
                'update' => $this->url($request, 'update', $media),
                'destroy' => $this->url($request, 'destroy', $media),
                'crop' => str_replace('__preset__', '{preset}', $this->url($request, 'crop', $media, ['preset' => '__preset__'])),
            ];
        }

        return $urls;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    protected function url(Request $request, string $action, ?Media $media = null, array $parameters = []): string
    {
        return Mediapicker::url($action, $media, $parameters, MediapickerRoutes::currentName($request));
    }

    protected function findMedia(Request $request, string $id): Media
    {
        $media_class = Mediapicker::mediaModel();

        /** @var Media $media */
        $media = $media_class::query()
            ->inLibraryOf($request->user())
            ->whereKey($id)
            ->firstOrFail();

        return $media;
    }

    protected function resource(Request $request, Media $media): MediaResource
    {
        return (new MediaResource($media))->forLibrary(MediapickerRoutes::currentName($request));
    }

    protected function resourceCollection(Request $request, LengthAwarePaginator $media): AnonymousResourceCollection
    {
        $route_name = MediapickerRoutes::currentName($request);

        $collection = MediaResource::collection($media);

        $collection->collection->each(fn (MediaResource $resource) => $resource->forLibrary($route_name));

        return $collection;
    }

    /**
     * Authorize against the user of the guard the routes were registered for
     */
    protected function authorizeAbility(Request $request, string $ability, mixed $arguments): void
    {
        $user = $request->user(MediapickerRoutes::guard($request));

        Gate::forUser($user)->authorize($ability, $arguments);
    }
}
