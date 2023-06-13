<?php

namespace App\V1\Controllers;

use App\Category;
use App\File;
use App\Post;
use App\PostCategory;
use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\PostCategoryModel;
use App\V1\Models\PostModel;
use App\V1\Transformers\Post\PostCategoryTransformer;
use App\V1\Transformers\Post\PostTransformer;
use App\V1\Validators\BlogPost\PostCategoryUpsertValidator;
use App\V1\Validators\Post\PostUpsertValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PostController extends BaseController
{
    protected $model;
    protected $postModel;

    public function __construct()
    {
        $this->model     = new  PostCategoryModel();
        $this->postModel = new PostModel();
    }

    public function searchCategory(Request $request)
    {
        $input               = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        if (!empty($input['title'])) {
            $input['title'] = ['like' => "%{$input['title']}%"];
        }
        $result = $this->model->search($input, ['company:code,name'], $request->get('limit', 20));
        return $this->response->paginator($result, new PostCategoryTransformer());
    }

    public function detailCategory($id)
    {
        $result = PostCategory::findOrFail($id);
        return $this->response->item($result, new PostCategoryTransformer());
    }

    public function createCategory(Request $request)
    {
        $input         = $request->all();
        $input['code'] = Str::upper(Str::ascii(Str::remove("-", Str::slug($input['code']))));
        (new PostCategoryUpsertValidator)->validate($input);
        $input['title'] = str_clean_special_characters($input['title']);
        (new PostCategoryUpsertValidator)->validate($input);
        try {
            DB::beginTransaction();
            $order = PostCategory::select('order')->orderBy('order', 'DESC')->first()->order ?? 0;

            $param  = [
                'code'        => $input['code'],
                'title'       => $input['title'],
                'slug'        => Str::slug($input['title']),
                'thumbnail'   => Arr::get($input, 'thumbnail'),
                'description' => Arr::get($input, 'description'),
                'order'       => Arr::get($input, 'order', $order += 1),
                'company_id'  => TM::getCurrentCompanyId(),
                'is_show'     => Arr::get($input, 'is_show', 1),
            ];
            $result = $this->model->create($param);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R001', $result->title)]);
    }

    public function updateCategory($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new PostCategoryUpsertValidator)->validate($input);
        $input['title'] = str_clean_special_characters($input['title']);
        (new PostCategoryUpsertValidator)->validate($input);
        try {
            DB::beginTransaction();
            $result = PostCategory::findOrFail($id);
            $param  = [
                'title'       => $input['title'],
                'slug'        => Str::slug($input['title']),
                'thumbnail'   => Arr::get($input, 'thumbnail', $result->thumbnail),
                'description' => Arr::get($input, 'description', $result->description),
                'order'       => Arr::get($input, 'order', $result->order),
                'is_show'     => !empty($input['is_show']) ? 1 : 0
            ];

            $result->update($param);
            if ($result->is_show == 0) {
                Post::where('category_id', $result->id)->update(['is_show' => 0]);
            }
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->title)]);
    }

    public function deleteCategory($id)
    {
        try {
            DB::beginTransaction();
            $result = PostCategory::findOrFail($id);
            $result->delete();
            Post::where('category_id', $result->id)->delete();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->title)]);
    }

    // ================= Post ==================
    public function searchPost(Request $request)
    {
        $input               = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        $result              = $this->postModel->search($input, [], $request->get('limit', 20));
        return $this->response->paginator($result, new PostTransformer());
    }

    public function detailPost($id)
    {
        $result       = Post::findOrFail($id);
        $result->view += 1;
        $result->save();
        return $this->response->item($result, new PostTransformer());
    }

    public function createPost(Request $request)
    {
        $input = $request->all();
        (new PostUpsertValidator)->validate($input);
        $input['title'] = str_clean_special_characters($input['title']);
        $input['short_description'] = str_clean_special_characters($input['short_description']);
        (new PostUpsertValidator)->validate($input);
        try {
            DB::beginTransaction();
            if (!empty($input['thumbnail_url'])) {
                $param     = [
                    'code'       => $input['thumbnail_url'],
                    'file_name'  => $input['thumbnail_url'],
                    'title'      => $input['thumbnail_url'],
                    'extension'  => "png",
                    'size'       => 2048,
                    'version'    => 1,
                    'is_active'  => 1,
                    'company_id' => TM::getCurrentCompanyId(),
                ];
                $r         = File::create($param);
                $thumbnail = $r->id;

                if (!empty($input['tags'])) {
                    $tagArr = [];
                    $tags   = explode(",", $input['tags']);
                    foreach ($tags as $tag) {
                        $tagArr[] = [
                            'id'   => $tag,
                            'name' => $tag,
                        ];
                    }
                    $dataTags = json_encode($tagArr);
                }
            }

            if (!empty($input['meta_title'])) {
                $result = Post::model()->where('meta_title', $input['meta_title'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_title)));
                }
            }
            if (!empty($input['meta_description'])) {
                $result = Post::model()->where('meta_description', $input['meta_description'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_description)));
                }
            }
            if (!empty($input['meta_keyword'])) {
                $result = Post::model()->where('meta_keyword', $input['meta_keyword'])->first();
                if (!empty($result) && $result->meta_keyword != "[]") {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_keyword)));
                }
            }
            if (!empty($input['meta_robot'])) {
                $result = Post::model()->where('meta_robot', $input['meta_robot'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_robot)));
                }
            }
            if (!empty($input['category_id'])) {
                $postCate = PostCategory::findOrFail($input['category_id']);
            }
            $param  = [
                'title'             => $input['title'],
                'slug'              => Str::slug($input['title']),
                'thumbnail'         => $thumbnail ?? array_get($input, 'thumbnail', null),
                'content'           => array_get($input, 'content', null),
                'short_description' => array_get($input, 'short_description', null),
                'category_id'       => $postCate->id ?? null,
                'category_code'     => $postCate->code ?? null,
                'tags'              => $dataTags ?? array_get($input, 'tags', null),
                'author'            => array_get($input, 'author', null),
                'date'              => date("Y-m-d H:i:s", time()),
                'is_show'           => 0,
                'meta_title'        => array_get($input, 'meta_title', null),
                'meta_description'  => array_get($input, 'meta_description', null),
                'meta_keyword'      => array_get($input, 'meta_keyword', null),
                'meta_robot'        => array_get($input, 'meta_robot', null),
                'company_id'        => TM::getCurrentCompanyId()
            ];
            $result = $this->postModel->create($param);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R001', $result->title)]);
    }

    public function updatePost($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new PostUpsertValidator)->validate($input);
        $input['title'] = str_clean_special_characters($input['title']);
        $input['short_description'] = str_clean_special_characters($input['short_description']);
        (new PostUpsertValidator)->validate($input);
        try {
            //            if (!empty($input['thumbnail_url'])) {
            //                $param     = [
            //                    'code'       => $input['thumbnail_url'],
            //                    'file_name'  => $input['thumbnail_url'],
            //                    'title'      => $input['thumbnail_url'],
            //                    'extension'  => "png",
            //                    'size'       => 2048,
            //                    'version'    => 1,
            //                    'is_active'  => 1,
            //                    'company_id' => TM::getCurrentCompanyId(),
            //                ];
            //                $r = File::create($param);
            //                $thumbnail = $r->id;
            //            }
            $result = Post::findOrFail($id);
            $param  = [
                'title'             => $input['title'],
                'slug'              => Str::slug($input['title']),
                'thumbnail'         => $thumbnail ?? array_get($input, 'thumbnail', $result->thumbnail),
                'content'           => array_get($input, 'content', $result->content),
                'short_description' => array_get($input, 'short_description', $result->short_description),
                'category_id'       => $input['category_id'],
                'tags'              => array_get($input, 'tags', $result->tags),
                'author'            => array_get($input, 'author', $result->author),
                'is_show'           => !empty($input['is_show']) ? 1 : 0,
                'meta_title'        => array_get($input, 'meta_title', $result->meta_title),
                'meta_description'  => array_get($input, 'meta_description', $result->meta_description),
                'meta_keyword'      => array_get($input, 'meta_keyword', $result->meta_keyword),
                'meta_robot'        => array_get($input, 'meta_robot', $result->meta_robot),
            ];
            $result->update($param);
            return response()->json(['status' => Message::get('R002', $result->title)]);
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
    }

    public function deletePost($id)
    {
        try {
            DB::beginTransaction();
            $result = Post::findOrFail($id);
            $result->delete();
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->title)]);
    }


    // ============== Client ================
    public function clientGetListCategory(Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        $input['company_id'] = $company_id;

        $result = $this->model->search($input, [], $request->get('limit', 20));
        return $this->response->paginator($result, new PostCategoryTransformer());
    }

    public function clientGetDetailCategory($id, Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        $result = PostCategory::where(['id' => $id, 'company_id' => $company_id])->first();
        return $this->response->item($result, new PostCategoryTransformer());
    }


    public function clientGetListPost(Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        if (!empty($input['title'])) {
            $input['title'] = ['LIKE' => $input['title']];
        }
        $input['company_id'] = $company_id;
        $result              = $this->postModel->search($input, [], $request->get('limit', 20));
        return $this->response->paginator($result, new PostTransformer());
    }

    public function clientGetDetailPost($id, Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }
        $result = Post::where(['id' => $id, 'company_id' => $company_id])->first();
        return $this->response->item($result, new PostTransformer());
    }
}
