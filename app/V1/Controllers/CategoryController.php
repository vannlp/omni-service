<?php
/**
 * User: Ho Sy Dai
 * Date: 9/20/2018
 * Time: 2:00 PM
 */

namespace App\V1\Controllers;

use App\Category;
use App\CategoryStore;
use App\Product;
use App\PromotionProgram;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Log;
use App\TM;
use App\UserGroup;
use App\V1\Models\CategoryModel;
use App\Exports\CategoryExport;
use Maatwebsite\Excel\Facades\Excel;
use App\V1\Models\FileModel;
use App\V1\Transformers\Category\CategoryHierarchyTransformer;
use App\V1\Transformers\Category\CategoryProductTopSaleTransformer;
use App\V1\Transformers\Category\CategoryTransformer;
use App\V1\Validators\CategoryCreateValidator;
use App\V1\Validators\CategoryUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Supports\Message;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\File;

/**
 * Class CategoryController
 * @package App\V1\CMS\Controllers
 */
class CategoryController extends BaseController
{

    protected $model;
    protected $fileModel;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model     = new CategoryModel();
        $this->fileModel = new \App\File();
    }

    /**
     * @return array
     */
    public function index()
    {
        return ['status' => '0k'];
    }

    /**
     * @param Request $request
     * @param CategoryTransformer $categoryTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, CategoryTransformer $categoryTransformer)
    {
        $input             = $request->all();
        $input['store_id'] = !empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId();
        $limit             = array_get($input, 'limit', 20);
        $categories        = $this->model->search($input, ['parent', 'area:id,name'], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($categories, $categoryTransformer);
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    public function view($id, CategoryTransformer $categoryTransformer)
    {
        $categories = Category::find($id);
        if (empty($categories)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($categories, $categoryTransformer);
    }

    public function detail($id, Request $request)
    {
        $input             = $request->all();
        $input['store_id'] = TM::getCurrentStoreId();
        $categories        = Category::find($id);
        if (empty($categories)) {
            return ['data' => []];
        }
        $categories = $this->model->show($input, $id);
        $result     = [];
        foreach ($categories as $data) {
            $data['data']      = $data;
            $file              = \App\File::find($data['image_id']);
            $url               = !empty($file->code) ? env('GET_FILE_URL') . $file->code : null;
            $data['image_url'] = $url;
            $result            = $data;
        }
        $categoryStore           = CategoryStore::model()->where('category_id', $id)
            ->get()->toArray();
        $result['store_details'] = $categoryStore;
        Log::view($this->model->getTable());
        return response()->json(["data" => $result]);
    }

    public function hierarchy(Request $request, CategoryHierarchyTransformer $categoryHierarchyTransformer)
    {
        $input      = $request->all();
        $limit      = array_get($input, 'limit', 20);
        $categories = $this->model->hierarchy($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($categories, $categoryHierarchyTransformer);
    }

    public function create(Request $request, CategoryCreateValidator $categoryCreateValidator)
    {
        $input         = $request->all();
        $input['type'] = !empty($input['type']) ? $input['type'] : 'PRODUCT';

        $categoryCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if (!empty($input['description'])) {
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $categoryCreateValidator->validate($input);
        if (!empty($input['meta_title'])) {
            $result = Category::model()->where('meta_title', $input['meta_title'])->first();
            if (!empty($result)) {
                return $this->responseError(Message::get('V008', Message::get($result->meta_title)));
            }
        }
        if (!empty($input['meta_description'])) {
            $result = Category::model()->where('meta_description', $input['meta_description'])->first();
            if (!empty($result)) {
                return $this->responseError(Message::get('V008', Message::get($result->meta_description)));
            }
        }
        if (!empty($input['meta_keyword']) && $input['meta_keyword'] != "[]") {
            $result = Category::model()->where('meta_keyword', $input['meta_keyword'])->first();
            if (!empty($result)) {
                return $this->responseError(Message::get('V008', Message::get($result->meta_keyword)));
            }
        }
        if (!empty($input['meta_robot'])) {
            $result = Category::model()->where('meta_robot', $input['meta_robot'])->first();
            if (!empty($result)) {
                return $this->responseError(Message::get('V008', Message::get($result->meta_robot)));
            }
        }
        DB::beginTransaction();
        try {
            $error = false;
            foreach ($input['store_details'] as $detail) {
                $item = Category::model()
                    ->join('category_stores as cs', 'cs.category_id', '=', 'categories.id')
                    ->where('categories.code', $input['code'])
                    ->where('categories.type', $input['type'])
                    ->where('cs.store_id', $detail['store_id'])
                    ->first();
                if (!empty($item)) {
                    $error = true;
                    break;
                }
            }
            if ($error) {
                return $this->response->errorBadRequest(Message::get("V028", "#" . $input['code']));
            }
            $category = $this->model->upsert($input);
            Log::create($this->model->getTable(),
                "#ID:" . $category->id . "-" . $category->code . "-" . $category->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("categories.create-success", $category->name)];
    }

    public function update($id, Request $request, CategoryUpdateValidator $categoryUpdateValidator)
    {
        $input         = $request->all();
        $input['id']   = $id;
        $input['type'] = !empty($input['type']) ? $input['type'] : 'PRODUCT';

        $categoryUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if (!empty($input['description'])) {
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $categoryUpdateValidator->validate($input);
        try {
            $error = false;
            foreach ($input['store_details'] as $detail) {
                $item = Category::model()
                    ->join('category_stores as cs', 'cs.category_id', '=', 'categories.id')
                    ->where('categories.code', $input['code'])
                    ->where('categories.type', $input['type'])
                    ->where('cs.store_id', $detail['store_id'])
                    ->select('categories.id')
                    ->first();
                if (!empty($item) && $item->id != $id) {
                    $error = true;
                    break;
                }
            }
            if ($error) {
                return $this->response->errorBadRequest(Message::get("V028", "#" . $input['code']));
            }
            DB::beginTransaction();
            $category = $this->model->upsert($input);
            Log::update($this->model->getTable(),
                "#ID:" . $category->id . "-" . $category->code . "-" . $category->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("categories.update-success", $category->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $category = Category::find($id);
            if (empty($category)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $category->delete();
            Log::delete($this->model->getTable(), "#ID:" . $category->id . "-" . $category->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("categories.delete-success", $category->name)];
    }

    public function getClientProductCategory(Request $request, CategoryTransformer $categoryTransformer)
    {
        $input    = $request->all();
        $limit    = array_get($input, 'limit', 20);
        $store_id = $input['store_id'] ?? null;
        if (empty($store_id)) {
            return $this->response->errorBadRequest(Message::get("V009", Message::get('stores')));
        }
        $input['store_id'] = $store_id;
        $categories        = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($categories, $categoryTransformer);
    }

    ##################################################### NOT AUTHENTICATION ###########################################

    public function getClientCategory(Request $request, CategoryTransformer $categoryTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                    list($area_ids, $group_id) = DataUser::getInstance()->all();
                    $input['area_ids'] = $area_ids ?? [];
                    $input['group_id'] = $group_id;
                }
            }
        }

        $input             = $request->all();
        $limit             = array_get($input, 'limit', 20);
        $input['store_id'] = $store_id;

        $categories = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($categories, $categoryTransformer);
    }

    public function getClientCategoryDetail($id, Request $request, CategoryTransformer $categoryTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }

        $category = Category::model()->where('id', $id)->whereHas('CategoryStoreDetails',
            function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });
        if (TM::getMyUserType() != USER_TYPE_USER) {
            $category_id = Category::model()->select([DB::raw('group_concat(id) as cate_ids')])
                ->where(['category_publish' => '1'])->first();
            if (!empty($category_id->cate_ids)) {
                $category = $category->whereIn('id', explode(',', $category_id->cate_ids));
            }
        }
        $category = $category->first();
        if (empty($category)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($category, $categoryTransformer);
    }

    public function getClientCategoryDetailBySlug($slug, Request $request, CategoryTransformer $categoryTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }

        $category = Category::model()->where('slug', $slug)->whereHas('CategoryStoreDetails',
            function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });
        if (TM::getMyUserType() != USER_TYPE_USER) {
            $category_id = Category::model()->select([DB::raw('group_concat(id) as cate_ids')])
                ->where(['category_publish' => '1'])->first();
            if (!empty($category_id->cate_ids)) {
                $category = $category->whereIn('id', explode(',', $category_id->cate_ids));
            }
        }
        $category = $category->first();
        if (empty($category)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($category, $categoryTransformer);
    }

    public function getClientHierarchy(Request $request, CategoryHierarchyTransformer $categoryHierarchyTransformer)
    {
        $input             = $request->all();
        $limit             = array_get($input, 'limit', 20);
        $input['store_id'] = null;
        if (TM::getCurrentUserId()) {
            $input['store_id'] = TM::getCurrentStoreId();
            if (TM::getMyUserType() != USER_TYPE_USER) {
                $category_id = Category::model()->select([DB::raw('group_concat(id) as cate_ids')])
                    ->where(['category_publish' => '1'])->first();
                if (!empty($category_id->cate_ids)) {
                    $input['in_ids'] = explode(',', $category_id->cate_ids);
                }
            }
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $input['store_id'] = $store->id;
                    $category_id       = Category::model()->select([DB::raw('group_concat(id) as cate_ids')])
                        ->where(['category_publish' => '1'])->first();
                    if (!empty($category_id->cate_ids)) {
                        $input['in_ids'] = explode(',', $category_id->cate_ids);
                    }
                }
            }
        }

        $categories = $this->model->clientHierarchy($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($categories, $categoryHierarchyTransformer);
    }

    public function getClientCategoryProductTopSale(Request $request, CategoryProductTopSaleTransformer $categoryProductTopSaleTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                    list($area_ids, $group_id) = DataUser::getInstance()->all();
                    $input['area_ids'] = $area_ids ?? [];
                    $input['group_id'] = $group_id;
                }
            }
        }

        $input             = $request->all();
        $input['store_id'] = $store_id;
        $products          = Product::model()
            ->select('id', 'code', 'name', 'category_ids')
            ->where('store_id', $input['store_id'])
            ->where('sold_count', '>', '0')
            ->orderBy('sold_count', 'desc')
            ->get()->toArray();
        $arrayCategory     = [];
        $arr               = [];
        foreach ($products as $key => $item) {
            $arr = explode(',', $item['category_ids']);
            if (in_array($arr[0], $arrayCategory)) {
                continue;
            }
            $arrayCategory[] = $arr[0];
        }
        $input['array_category'] = $arrayCategory;
        $categories              = $this->model->searchProductTopSale($input, [], 10);
        Log::view($this->model->getTable());
        return $this->response->paginator($categories, $categoryProductTopSaleTransformer);
    }

    public function getClientAllCategory(Request $request)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                    list($area_ids, $group_id) = DataUser::getInstance()->all();
                    $input['area_ids'] = $area_ids ?? [];
                    $input['group_id'] = $group_id;
                }
            }
        }
        $input             = $request->all();
        $input['store_id'] = $store_id;
        $categories        = $this->model->getAll($input, ['parent:id,parent_id', 'stores:category_id,store_id,store_code,store_name', 'file:id,code']);
        Log::view($this->model->getTable());
        return response()->json(["data" => $categories]);
    }

    public function getClientDetailCategory($id, Request $request)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                    list($area_ids, $group_id) = DataUser::getInstance()->all();
                    $input['area_ids'] = $area_ids ?? [];
                    $input['group_id'] = $group_id;
                }
            }
        }
        $input             = $request->all();
        $input['store_id'] = $store_id;
        $categories        = Category::find($id);
        if (empty($categories)) {
            return ['data' => []];
        }
        $categories = $this->model->show($input, $id, ['parent:id,parent_id', 'stores:category_id,store_id,store_code,store_name', 'file:id,code']);
        Log::view($this->model->getTable());
        return response()->json(["data" => $categories]);
    }

    public function categoryExportExcel()
    {
        //ob_end_clean();
        $date = date('YmdHis', time());
        $cate = Category::model()->get();
        //ob_start();
        return Excel::download(new CategoryExport($cate), 'list_categories_' . $date . '.xlsx');
    }
}