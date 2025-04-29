<?php

namespace Javaabu\Mediapicker\Http\Controllers;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Javaabu\Helpers\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Javaabu\Helpers\Media\AllowedMimeTypes;
use Javaabu\Helpers\Traits\HasOrderbys;
use Javaabu\Mediapicker\Contracts\MediaOwner;
use Javaabu\Mediapicker\Http\Requests\MediaRequest;
use Javaabu\Mediapicker\Mediapicker;
use Javaabu\Mediapicker\Models\Media;
use Spatie\Image\Image;

class MediaController extends Controller
{
    use HasOrderbys;

    /**
     * Initialize orderbys
     */
    protected static function initOrderbys()
    {
        static::$orderbys = [
            'name'       => __('Name'),
            'created_at' => __('Created At'),
            'id'         => __('ID'),
        ];
    }

    /**
     * @return Media
     */
    protected function resolveMedia(string|int $media_id)
    {
        $model_class = Mediapicker::mediaModel();

        $media = $model_class::findForController($media_id)->firstOrFail();

        return $media;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Mediapicker::mediaModel());

        $title = __('All Media');
        $orderby = $this->getOrderBy($request, 'created_at');
        $order = $this->getOrder($request, 'created_at', $orderby);
        $per_page = $this->getPerPage($request, 24);

        $view = $request->input('view', 'grid');

        $media_class = Mediapicker::mediaModel();

        $media_items = $media_class::userVisible()
                            ->orderBy($orderby, $order);

        $mode = $request->input('mode', 'index');
        $single = $request->input('single') == true;

        $search = null;
        if ($search = $request->input('search')) {
            $media_items->search($search);
            $title = __('Media matching \':search\'', ['search' => $search]);
        }

        $type = $request->input('type');

        if ($type) {
            $media_items->hasFileType($type);
        }

        if ($date_field = $request->input('date_field')) {
            $media_items->dateBetween($date_field, $request->input('date_from'), $request->input('date_to'));
        }

        $media_items = $media_items->with('model')
                                   ->paginate($per_page)
                                   ->appends($request->except('page'));

        $selected = $request->input('selected', []);
        $selected = Arr::wrap($selected);

        if ($single && $selected) {
            $selected = [$selected[0]];
        }

        $view_name = $mode == 'picker' ? 'media.picker.show' : 'media.index';

        return view(Mediapicker::getViewName($view_name), compact(
            'media_items',
            'single',
            'selected',
            'type',
            'mode',
            'title',
            'per_page',
            'search',
            'view'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(MediaRequest $request)
    {
        $this->authorize('create', Mediapicker::mediaModel());

        /** @var MediaOwner $user */
        $user = $request->user();

        $file = $request->file('file');

        $media = $user->addMedia($file);

        if ($name = $request->input('name')) {
            $media->usingName($name);
        }

        $custom_properties = [];

        if ($description = $request->input('description')) {
            $custom_properties['description'] = $description;
        }

        // get dimensions
        try {
            $image = Image::load($file);

            $custom_properties['width'] =  $image->getWidth();
            $custom_properties['height'] =  $image->getHeight();
        } catch (\Exception $e) {
            Log::error('MediapickerError: Could not load image dimensions. Error: ' . $e->getMessage());
        }

        if ($custom_properties) {
            $media->withCustomProperties($custom_properties);
        }

        $media = $media->toMediaCollection($user->getMediapickerCollectionName());

        $edit_url = Mediapicker::newMediaInstance()->url('edit', $media);

        if ($request->expectsJson()) {
            return response()->json([
                'success'   => true,
                'id'        => $media->id,
                'uuid'      => $media->uuid,
                'thumb'     => $media->getUrl('mediapicker-thumb'),
                'large'     => $media->getUrl('mediapicker-large'),
                'file_type' => AllowedMimeTypes::getType($media->mime_type),
                'icon'      => AllowedMimeTypes::getIcon($media->mime_type, Mediapicker::getIconPack()),
                'name'      => $media->name,
                'file_name' => $media->file_name,
                'url'       => $media->getUrl(),
                'edit_url'  => $edit_url,
            ]);
        }

        $this->flashSuccessMessage();

        return redirect()->to($edit_url);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', Mediapicker::mediaModel());

        return view(Mediapicker::getViewName('media.create'));
    }



    /**
     * Display the specified resource.
     */
    public function show(string|int $media_id)
    {
        $media = $this->resolveMedia($media_id);

        $this->authorize('view', $media);

        return view(Mediapicker::getViewName('media.show'), compact('media'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string|int $media_id)
    {
        $media = $this->resolveMedia($media_id);

        $this->authorize('update', $media);

        return view(Mediapicker::getViewName('media.edit'), compact('media'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(MediaRequest $request, string|int $media_id)
    {
        $media = $this->resolveMedia($media_id);

        $this->authorize('update', $media);

        $media->fill($request->only(['name', 'description']));
        $media->save();

        if ($request->expectsJson()) {
            return response()->json($media);
        }

        $this->flashSuccessMessage();

        return redirect()->to($media->url('edit'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string|int $media_id, Request $request)
    {
        $media = $this->resolveMedia($media_id);

        $this->authorize('delete', $media);

        $media->delete();

        if ($request->expectsJson()) {
            return response()->json(true);
        }

        return redirect()->to($media->url('index'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function bulk(Request $request)
    {
        $this->authorize('create', Mediapicker::mediaModel());

        /** @var MediaOwner $user */
        $user = $request->user();

        $rules = [
            'action'  => 'required|in:delete',
            'media'   => 'required|array',
            'media.*' => 'exists:media,id,collection_name,'.$user->getMediapickerCollectionName().',model_type,' . $user->getMorphClass(),
        ];

        if (! $user->canDeleteOthersMedia()) {
            $rules['media.*'] .= ',model_id,' . $user->getKey();
        }

        $this->validate($request, $rules);

        $action = $request->input('action');
        $ids = $request->input('media', []);
        $view = $request->input('view');

        switch ($action) {
            case 'delete':
                $media_model = Mediapicker::mediaModel();

                $media = $media_model::whereIn('id', $ids)
                            ->where('collection_name', $user->getMediapickerCollectionName())
                            ->whereModelType($user->getMorphClass());

                $media->get()
                      ->each(function ($media) {
                          $this->authorize('delete', $media);
                          $media->delete();
                      });
                break;
        }

        $this->flashSuccessMessage();

        $redirect = add_query_arg('view', $view, Mediapicker::newMediaInstance()->url('index'));

        return $this->redirect($request, $redirect);
    }
}
