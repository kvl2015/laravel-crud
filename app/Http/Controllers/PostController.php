<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\App;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Events\BreadDataAdded;
use TCG\Voyager\Events\BreadDataDeleted;
use TCG\Voyager\Events\BreadDataRestored;
use TCG\Voyager\Events\BreadDataUpdated;
use TCG\Voyager\Events\BreadImagesDeleted;
use TCG\Voyager\Facades\Voyager;


class PostController  extends \TCG\Voyager\Http\Controllers\VoyagerBaseController
{
    public function index(Request $request) {
        $data = [
            'categories' => \TCG\Voyager\Models\Category::all(),
            'posts'  => \TCG\Voyager\Models\Post::orderBy('created_at', 'DESC')->get(),
        ];

        return view('post.index', compact('data'));
    }


    public function category(Request $request, $id) {
        $category = \TCG\Voyager\Models\Category::where('id', $id)->first();
        if (!$category) {
            abort(404);
        }
        $data = [
            'categories' => \TCG\Voyager\Models\Category::all(),
            'posts'  => \TCG\Voyager\Models\Post::where('category_id', $id)->orderBy('created_at', 'DESC')->get(),
            'category' => $category
        ];

        return view('post.index', compact('data'));
    }


    public function list(Request $request) {
        $user = Auth::user();
        if (!$user) {
            redirect('/');
        }
        $data = [
            'posts'  => \TCG\Voyager\Models\Post::where('author_id', $user->id)->orderBy('created_at', 'DESC')->get(),
            'categories' => \TCG\Voyager\Models\Category::all()
        ];

        return view('post.list', compact('data'));
    }


    public function show(Request $request, $slug) {
        $post = \TCG\Voyager\Models\Post::where('slug', $slug)->first();
        if (!$post) {
            abort(404);
        }

        $data = [
            'categories' => \TCG\Voyager\Models\Category::all(),
            'post'  => $post,
        ];

        return view('post.show', compact('data'));
    }


    public function edit(Request $request, $id) {
        $user = Auth::user();
        $slug = 'posts';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        if (strlen($dataType->model_name) != 0) {
            $model = app($dataType->model_name);

            // Use withTrashed() if model uses SoftDeletes and if toggle is selected
            if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $model = $model->withTrashed();
            }
            if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
                $model = $model->{$dataType->scope}();
            }
            $dataTypeContent = call_user_func([$model, 'findOrFail'], $id);
        } else {
            // If Model doest exist, get data from table name
            $dataTypeContent = DB::table($dataType->name)->where('id', $id)->first();
        }
        
        // Check belongs to
        if ($dataTypeContent->author_id != $user->id) {
            return redirect()->route("posts.list");
        }

        foreach ($dataType->editRows as $key => $row) {
            $dataType->editRows[$key]['col_width'] = isset($row->details->width) ? $row->details->width : 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'edit');

        // Check permission
        $this->authorize('edit', $dataTypeContent);

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'edit', $isModelTranslatable);

        return view('post.edit', compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }

    
    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $slug = 'posts';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Compatibility with Model binding.
        $id = $id instanceof \Illuminate\Database\Eloquent\Model ? $id->{$id->getKeyName()} : $id;

        $model = app($dataType->model_name);
        if ($dataType->scope && $dataType->scope != '' && method_exists($model, 'scope'.ucfirst($dataType->scope))) {
            $model = $model->{$dataType->scope}();
        }
        if ($model && in_array(SoftDeletes::class, class_uses_recursive($model))) {
            $data = $model->withTrashed()->findOrFail($id);
        } else {
            $data = $model->findOrFail($id);
        }

        // Check belongs to
        if ($data->author_id != $user->id) {
            return redirect()->route("posts.list");
        }

        // Check permission
        $this->authorize('edit', $data);

        $val = $this->validateBread($request->all(), $dataType->editRows, $dataType->name, $id)->validate();

        $this->insertUpdateData($request, $slug, $dataType->editRows, $data);

        event(new BreadDataUpdated($dataType, $data));

        if (auth()->user()->can('browse', app($dataType->model_name))) {
            $redirect = redirect()->route("posts.list");
        } else {
            $redirect = redirect()->back();
        }

        return $redirect->with([
            'message'    => "Data update successfully",
            'alert-type' => 'success',
        ]);
    }    


    public function create(Request $request)
    {
        $slug = 'posts';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        $dataTypeContent = (strlen($dataType->model_name) != 0)
                            ? new $dataType->model_name()
                            : false;

        foreach ($dataType->addRows as $key => $row) {
            $dataType->addRows[$key]['col_width'] = $row->details->width ?? 100;
        }

        // If a column has a relationship associated with it, we do not want to show that field
        $this->removeRelationshipField($dataType, 'add');

        // Check if BREAD is Translatable
        $isModelTranslatable = is_bread_translatable($dataTypeContent);

        // Eagerload Relations
        $this->eagerLoadRelations($dataTypeContent, $dataType, 'add', $isModelTranslatable);

        $view = "post.edit";

        return Voyager::view($view, compact('dataType', 'dataTypeContent', 'isModelTranslatable'));
    }


    public function store(Request $request)
    {
        $slug = 'posts';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Check permission
        $this->authorize('add', app($dataType->model_name));

        // Validate fields with ajax
        $val = $this->validateBread($request->all(), $dataType->addRows)->validate();
        $data = $this->insertUpdateData($request, $slug, $dataType->addRows, new $dataType->model_name());

        event(new BreadDataAdded($dataType, $data));

        if (!$request->has('_tagging')) {
            if (auth()->user()->can('browse', $data)) {
                $redirect = redirect()->route("posts.list");
            } else {
                $redirect = redirect()->back();
            }

            return $redirect->with([
                'message'    => "Post created successfully",
                'alert-type' => 'success',
            ]);
        } else {
            return response()->json(['success' => true, 'data' => $data]);
        }
    }


    public function delete(Request $request, $id) {
        $user = Auth::user();
        
        $slug = 'posts';

        $dataType = Voyager::model('DataType')->where('slug', '=', $slug)->first();

        // Init array of IDs
        $ids[] = $id;
        foreach ($ids as $id) {
            $data = call_user_func([$dataType->model_name, 'findOrFail'], $id);

            // Check permission
            $this->authorize('delete', $data);

            if ($data->author_id != $user->id) {
                return redirect()->route("posts.list");
            }

            $model = app($dataType->model_name);
            if (!($model && in_array(SoftDeletes::class, class_uses_recursive($model)))) {
                $this->cleanup($dataType, $data);
            }
        }

        $displayName = count($ids) > 1 ? $dataType->getTranslatedAttribute('display_name_plural') : $dataType->getTranslatedAttribute('display_name_singular');

        $res = $data->destroy($ids);
        $data = $res
            ? [
                'message'    => __('voyager::generic.successfully_deleted')." {$displayName}",
                'alert-type' => 'success',
            ]
            : [
                'message'    => __('voyager::generic.error_deleting')." {$displayName}",
                'alert-type' => 'error',
            ];

        if ($res) {
            event(new BreadDataDeleted($dataType, $data));
        }

        return redirect()->route("posts.list")->with($data);
    }

    
}
