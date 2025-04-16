<?php

namespace Javaabu\Mediapicker\Http\Controllers;

use App\Http\Controllers\Admin\Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Helpers\Media\Media;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use App\Http\Requests\MediaRequest;
use Illuminate\Support\Facades\Log;
use Javaabu\Helpers\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\View\Factory;
use Javaabu\Helpers\Traits\HasOrderbys;
use Javaabu\Helpers\Exceptions\AppException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Auth\Access\AuthorizationException;
use Spatie\MediaLibrary\MediaCollections\FileAdder;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileDoesNotExist;

class MediaController extends Controller
{
    use HasOrderbys;

    /**
     * Create a new  controller instance.
     *
     * @param void
     * @return void
     */
    public function __construct()
    {
        $this->authorizeResource(Media::class);
    }

    /**
     * Initialize orderbys
     */
    protected static function initOrderbys()
    {
        static::$orderbys = [
            'name'       => _d('Name'),
            'created_at' => _d('Created At'),
            'id'         => _d('ID'),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Application|Factory|View
     */
    public function index(Request $request)
    {
        $title = _d('All Media');
        $orderby = $this->getOrderBy($request, 'created_at');
        $order = $this->getOrder($request, 'created_at', $orderby);
        $per_page = $this->getPerPage($request, 24);

        $view = $request->input('view', 'grid');

        $media_items = Media::userVisible()
                            ->orderBy($orderby, $order);

        $search = null;
        if ($search = $request->input('search')) {
            $media_items->search($search);
            $title = _d('Media matching \':search\'', ['search' => $search]);
        }

        if ($type = $request->input('type')) {
            $media_items->hasType($type);
        }

        if ($request->filled('stock')) {
            $media_items->whereIsStock($request->input('stock') == true);
        }

        $media_items = $media_items->withRelations()
                                   ->paginate($per_page)
                                   ->appends($request->except('page'));

        return view('admin.media.index', compact(
            'media_items',
            'title',
            'per_page',
            'search',
            'view'
        ));
    }

    /**
     * Display a listing of the resources.
     *
     * @param Request $request
     * @return Application|Factory|View
     * @throws AuthorizationException
     */
    public function picker(Request $request)
    {
        $this->authorize('viewAny', Media::class);

        $title = _d('All Media');
        $per_page = 24;

        $single = $request->input('single') == true;

        $media_items = Media::userVisible()
                            ->where('model_type', 'user')
                            ->latest('created_at');

        $search = null;
        if ($search = $request->input('search')) {
            $media_items->search($search);
            $title = _d('Media matching \':search\'', ['search' => $search]);
        }

        $type = $request->input('type');
        if ($type) {
            $media_items->hasType($type);
        }

        $selected = $request->input('selected', []);
        $selected = Arr::wrap($selected);

        if ($single && $selected) {
            $selected = [$selected[0]];
        }

        $media_items = $media_items->withRelations()
                                   ->paginate($per_page)
                                   ->appends($request->except('page'));

        return view('admin.media.picker.show', compact(
            'selected',
            'single',
            'type',
            'media_items',
            'title',
            'per_page',
            'search'
        ));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Application|Factory|View|Response
     */
    public function create()
    {
        return view('admin.media.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param MediaRequest $request
     * @return JsonResponse|RedirectResponse
     * @throws FileDoesNotExist
     * @throws FileIsTooBig
     */
    public function store($locale, MediaRequest $request)
    {
        $user = $request->user();

        // add file
        $media = null;
        if ($file = $request->file('file')) {
            /** @var $media FileAdder */
            $media = $user->addMedia($file);

            // set name
            if ($name = $request->input('name')) {
                $media->usingName($name);
            }

            // for testing
            if (app()->runningUnitTests()) {
                $media->preservingOriginal();
            }

            //
            $media = $media->withResponsiveImages()
                           ->usingFileName(Str::slug(Str::random(8)) . '.' . $file->guessExtension())
                           ->toMediaCollection('media_library');

            // set description
            if ($request->anyFilled(['description'])) {
                $media->description = $request->input('description');
            }

            /*// sync tags
            if ($tags = $request->input('tags', [])) {
                $media->syncTags($tags);
            }*/
        }

        // check if it was added
        if (! $media) {
            throw new AppException(500, 'FileNotSaved', 'File could not be saved.');
        }

        if (expects_json($request)) {
            return response()->json([
                'success'   => true,
                'id'        => $media->id,
                'preview'   => $media->getUrl('preview'),
                'thumb'     => $media->getUrl('thumb'),
                'large'     => $media->getUrl('large'),
                'type_slug' => $media->type_slug,
                'icon'      => $media->icon,
                'name'      => $media->name,
                'file_name' => $media->file_name,
                'location'  => $media->getUrl(),
                'edit_url'  => $media->admin_url,
                //'tags' => $media->tagWords->pluck('name', 'id'),
            ]);
        }

        $this->flashSuccessMessage();

        return redirect()->to($media->url('edit'));
    }

    /**
     * Display the specified resource.
     *
     * @param Media $media
     * @return Response
     */
    public function show($locale, Media $media)
    {
        return redirect()->to($media->url('edit'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Media $media
     * @return Response
     */
    public function edit($locale, Media $media)
    {
        $media->dontShowTranslationFallbacks();
        return view('admin.media.edit', compact('media'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param MediaRequest $request
     * @param Media $media
     * @return JsonResponse|RedirectResponse
     */
    public function update(MediaRequest $request, $locale, Media $media)
    {
        // If this is not a translation, set lang
        if ((! $request->input('is_translation')) && $request->input('lang')) {
            $media->lang = $request->input('lang');
            app()->setLocale($media->lang->value);
        }

        if ($request->input('translation')) {
            $media->translations = $request->only($media->getTranslatables());
            $media->hide_translation = $request->input('hide_translation', false);
            $media->save();
        } else {
            $media->fill($request->only(['name', 'description']));
            $media->save();

            /*if ($request->input('sync_tags')) {
                $tags = $request->input('tags', []);
                $media->syncTags($tags);
            }*/

        }

        if (expects_json($request)) {
            return response()->json($media);
        }

        $this->flashSuccessMessage();

        if ($request->input('translation')) {
            return redirect()->back();
        }

        return redirect()->to($media->url('edit'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Media $media
     * @param Request $request
     * @return Response
     * @throws Exception
     */
    public function destroy($locale, Media $media, Request $request)
    {
        if (! $media->delete()) {
            if ($request->expectsJson()) {
                return response()->json(false, 500);
            }
            abort(500);
        }

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        return redirect()->action('Admin\\MediaController@index');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @return Response
     * @internal param Inquiry $inquiry
     */
    public function bulk($locale, Request $request)
    {
        $this->authorize('create', Media::class);

        $this->validate($request, [
            'action'  => 'required|in:delete',
            'media'   => 'required|array',
            'media.*' => 'exists:media,id',
        ]);

        $action = $request->input('action');
        $ids = $request->input('media', []);
        $user = $request->user();
        $view = $request->input('view');

        switch ($action) {
            case 'delete':
                //make sure allowed to delete
                $this->authorize('delete_media');

                $media = Media::whereIn('id', $ids);

                // filter to user's media
                if (! $user->can('delete_other_users_media')) {
                    $media->whereModelType($user->getMorphClass())
                          ->whereModelId($user->id);
                }

                $media->get()
                      ->each(function (Media $media) {
                          $media->delete();
                      });
                break;
        }

        $this->flashSuccessMessage();

        $redirect = add_query_arg('view', $view, translate_route('admin.media.index'));

        return $this->redirect($request, $redirect);
    }
}
