<?php

namespace App\V1\Controllers;

use App\Cart;
use App\Category;
use App\Exports\ProductExport;
use App\Foundation\PromotionHandle;
use App\OrderDetail;
use App\ProductComment;
use App\PromotionProgram;
use App\Property;
use App\Setting;
use App\ShippingAddress;
use App\Store;
use App\Supports\DataUser;
use App\TM;
use App\PropertyVariant;
use App\Product;
use App\Supports\Log;
use App\ProductReview;
use App\SearchHistory;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Library\CDP;
use App\V1\Transformers\Product\CommentByProductTransformer;
use App\V1\Transformers\Product\ProductClientListTransformer;
use App\V1\Transformers\Product\ProductClientTransformer;
use App\V1\Transformers\Product\ProductClientTransformerDataString;
use App\V1\Transformers\Product\ProductDetailClientTransformer;
use App\V1\Transformers\Product\ProductLandingPageClientTransformer;
use App\V1\Transformers\Product\ProductListTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\V1\Models\ProductModel;
use Illuminate\Support\Facades\DB;
use App\V1\Models\ProductReviewModel;
use App\V1\Models\SearchHistoryModel;
use App\V1\Validators\ProductCreateValidator;
use App\V1\Validators\ProductReviewValidator;
use App\V1\Validators\ProductUpsertValidator;
use App\V1\Transformers\Product\ProductTransformer;
use App\V1\Transformers\Product\ProductReviewTransformer;
use App\Supports\Html2Text;
use App\V1\Transformers\Product\ProductListAdminTransformer;
use Maatwebsite\Excel\Facades\Excel;
use function App\V1\Models\arrCategoryGrandchildren;
use Illuminate\Support\Arr;

/**
 * Class ProductController
 * @package App\V1\Controllers
 */
class ProductController extends BaseController
{
    /**
     * @var ProductModel
     */
    protected $model;
    protected $modelProductReview;

    /**
     * ProductController constructor.
     * @param ProductModel $model
     */
    public function __construct(ProductModel $model)
    {
        $this->model              = $model;
        $this->modelProductReview = new ProductReviewModel();
    }

    public function search(Request $request, ProductTransformer $productTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);

        $products = $this->model->search($input, [
            'category',
            'getAge',
            'getManufacture',
            'rewardPoints.userGroup',
            'stores',
            'options.option',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
        ], $limit);

        foreach ($products as $item) {
            $data[] = $item['id'];
        }

        if (!empty($input['code'])) {
            $dataInput['code'] = $input['code'];
        }
        if (!empty($input['name'])) {
            $dataInput['name'] = $input['name'];
        }
        if (!empty($dataInput)) {
            $searchHistoryModel = new SearchHistoryModel();
            $searchHistoryModel->create([
                'search_by' => !empty($dataInput) ? implode(",", array_keys($dataInput)) : null,
                'keyword'   => !empty($dataInput) ? implode(",", array_filter($dataInput)) : null,
                'data'      => !empty($data) ? implode(",", $data) : null,
                'store_ids' => TM::getCurrentStoreId(),
            ]);
        }
        Log::view($this->model->getTable());
        return $this->response->paginator($products, $productTransformer)
        ->header('Cache-Control', 'max-age=7200, public');
    }

    public function topProductSearch(Request $request, ProductTransformer $transformer)
    {
        $input           = $request->all();
        $limit           = array_get($input, 'limit', 10);
        $data            = [];
        $searchHistories = SearchHistory::model();
        $storeId         = $input['store_id'] ?? null;
        if (!empty($storeId)) {
            $searchHistories = $searchHistories->where('store_ids', $storeId);
            //            $searchHistories = $searchHistories->where(function ($q) use ($storeId) {
            //                $q->orWhere(DB::raw("CONCAT(',',store_ids,',')"), 'like', "%,$storeId,%");
            //            });
        }
        $searchHistories = $searchHistories->get()->toArray();
        foreach ($searchHistories as $searchHistory) {
            $data[] = $searchHistory['data'];
        }
        $arrayMerge = [];
        foreach (array_filter($data) as $key => $datum) {
            $arrayMerge = array_merge($arrayMerge, explode(",", $datum));
        }
        $countValues = array_flip(array_count_values($arrayMerge));
        krsort($countValues);
        $products = Product::model()->whereIn('id', array_values($countValues))->paginate($limit);
        return $this->response->paginator($products, $transformer);
    }

    public function topKeywordSearch(Request $request)
    {
        $input           = $request->all();
        $limit           = array_get($input, 'limit', 20);
        $dataSearch      = [];
        $searchHistories = SearchHistory::model()->whereNotNull('data');
        $storeId         = $input['store_id'] ?? null;
        if (!empty($storeId)) {
            $searchHistories = $searchHistories->where('store_ids', $storeId);
            //            $searchHistories = $searchHistories->where(function ($q) use ($storeId) {
            //                $q->orWhere(DB::raw("CONCAT(',',store_ids,',')"), 'like', "%,$storeId,%");
            //            });
        }
        $searchHistories = $searchHistories->get()->toArray();
        foreach ($searchHistories as $searchHistory) {
            $dataSearch[] = $searchHistory['keyword'];
        }
        $arrayMerge = [];
        foreach (array_filter($dataSearch) as $key => $datum) {
            $arrayMerge = array_merge($arrayMerge, explode(",", $datum));
        }
        $countValues = array_filter(array_flip(array_count_values($arrayMerge)));
        krsort($countValues);
        $data = [];
        foreach ($countValues as $key => $value) {
            $data[] = [
                'keyword'            => "{$value}",
                'number_of_searches' => $key,
            ];
        }
        $data = array_slice($data, 0, $limit);
        return response()->json(['data' => $data]);
    }

    public function searchKeyword($keyword)
    {
        $searchHistories = SearchHistory::model()->where('keyword', 'like', "%{$keyword}%")->get()->toArray();
        $data            = [];
        foreach ($searchHistories as $searchHistory) {
            $data[] = [
                'keyword'      => $searchHistory['keyword'],
                'user_id'      => $searchHistory['created_by'],
                'created_date' => date('d-m-Y H:i', strtotime($searchHistory['created_at'])),
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function detail($id, ProductTransformer $productTransformer)
    {
        $product = Product::model()->with([
            'category',
            'rewardPoints.userGroup',
            'stores',
            'options.option',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
        ])->where('id', $id)->first();
        if (empty($product)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($product, $productTransformer);
    }

    public function create(Request $request, ProductCreateValidator $productCreateValidator)
    {
        $input = $request->all();
        $productCreateValidator->validate($input);
        $input['name']              = str_clean_special_characters($input['name']);
        $input['code']              = str_clean_special_characters($input['code']);
        $input['short_description'] = str_clean_special_characters($input['short_description']);
        $productCreateValidator->validate($input);
        try {
            // $check_product = Product::model()->where([
            //     'code'     => $input['code'],
            //     'store_id' => TM::getCurrentStoreId(),
            // ])->first();
            // if (!empty($check_product)) {
            //     return $this->responseError(Message::get("unique", $input['code']));
            // }
            if (!empty($input['meta_title'])) {
                $result = Product::model()->where('meta_title', $input['meta_title'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_title)));
                }
            }
            if (!empty($input['meta_description'])) {
                $result = Product::model()->where('meta_description', $input['meta_description'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_description)));
                }
            }
            if (!empty($input['meta_keyword']) && $input['meta_keyword'] != "[]") {
                $result = Product::model()->where('meta_keyword', $input['meta_keyword'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_keyword)));
                }
            }
            if (!empty($input['meta_robot'])) {
                $result = Product::model()->where('meta_robot', $input['meta_robot'])->first();
                if (!empty($result)) {
                    return $this->responseError(Message::get('V008', Message::get($result->meta_robot)));
                }
            }
            $product = $this->model->upsert($input);

            #CDP
            try {
                CDP::pushProductCdp($product, 'create - ProductController - line: 211');
            } catch (\Exception $exception) {
                TM_Error::handle($exception);
            }

            Log::create($this->model->getTable(), "#ID:" . $product->id . "-" . $product->code . "-" . $product->name);
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("products.create-success", $input['name'])];
    }

    public function update($id, Request $request, ProductUpsertValidator $productUpsertValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $productUpsertValidator->validate($input);
        $input['name']              = str_clean_special_characters($input['name']);
        $input['code']              = str_clean_special_characters($input['code']);
        $input['short_description'] = str_clean_special_characters($input['short_description']);
        $productUpsertValidator->validate($input);
        try {
            // $check_product = Product::model()->where('code', $input['code'])
            //     ->where('store_id', TM::getCurrentStoreId())
            //     ->where('id', '!=', $id)
            //     ->first();
            // if (!empty($check_product)) {
            //     return $this->responseError(Message::get("unique", $input['code']));
            // }

            $product = $this->model->upsert($input);

            #CDP
            try {
                CDP::pushProductCdp($product, 'update - ProductController - line: 269');
            } catch (\Exception $exception) {
                TM_Error::handle($exception);
            }

            Log::update($this->model->getTable(), "#ID:" . $product->id, null, $product->name);
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("products.update-success", $product->name)];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $product = Product::find($id);
            if (empty($product)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            // Delete Product
            $product->delete();

            // Delete Product In Warehouse Detail
            $queryDelete = "DELETE FROM `warehouse_details` WHERE `product_id` = {$id}";
            DB::statement($queryDelete);

            Log::delete($this->model->getTable(), "#ID:" . $product->id . "-" . $product->name);
            DB::commit();
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("products.delete-success", $product->name)];
    }

    public function productReview(Request $request, ProductReviewValidator $productReviewValidator)
    {
        $input = $request->all();
        $productReviewValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->productReview($input);
            Log::view($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("products.review-success", $result->name)];
    }

    public function getTotalPointReview($id)
    {
        $point = ProductReview::model()->where('product_id', $id)->avg('rate');
        if (empty($point)) {
            return ['data' => null];
        }
        $data = ['point' => number_format($point, 1) . "/5.0"];
        return response()->json(['data' => $data]);
    }

    public function getProductReview($id, Request $request, ProductReviewTransformer $productReviewTransformer)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['product_id'] = ['=' => $id];
        $result              = $this->modelProductReview->search($input, $with = [], $limit);
        return $this->response->paginator($result, $productReviewTransformer);
    }

    public function bestsellers(ProductTransformer $productTransformer)
    {
        $maxOrderQty = OrderDetail::model()
            ->select('product_id', DB::raw('SUM(qty) as total_qty'))
            ->groupBy('product_id')
            ->orderBy('total_qty', 'desc')
            ->first();
        $products    = Product::model()->with([
            'category',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
        ])->where('id', $maxOrderQty["product_id"])->first();

        if (empty($products)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($products, $productTransformer);
    }

    ########################################### NOT AUTHENTICATION ############################################
    public function getClientProduct(Request $request)
    {
        try {
            list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
            $input             = $request->all();
            $limit             = array_get($input, 'limit', 20);
            $input['store_id'] = $store_id;
            $input['area_ids'] = $area_ids ?? [];
            $input['group_id'] = $group_id;

            $request->merge($input);
            $setting = Setting::where('code', 'CONFIG-PRODUCT')->where('store_id', $store_id)->where('company_id', $company_id)->first();
            if ($setting) {
                $setting = json_decode($setting->data);
                if (isset($input['is_new']) && $setting[0]->key == 'NEW' && $setting[0]->value == 0) {
                    return ['data' => []];
                }
            }
            // if(!empty($input['id'])){
            //     $id_product = explode(',', $input['id']);        
            //     $input['id'] = $id_product;
            // }
            $products          = $this->model->searchClient($input, [
                'brand:id,name',
                'area:id,name',
                'stores:id,name',
                'storeOrigin:id,name',
                'category_code:slug'
            ], $limit);
            $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
                PromotionHandle::TYPE_USING_PRODUCT,
                $company_id
            );
            if (!empty($input['landing_page']) && $input['landing_page'] == "show") {
                return $this->response->paginator($products, new ProductLandingPageClientTransformer($promotionPrograms))
                ->header('Cache-Control', 'max-age=300, public');
            }
            return $this->response->paginator($products, new ProductClientTransformer($promotionPrograms))
            ->header('Cache-Control', 'max-age=300, public');
        }
        catch (\Exception $exception) {
            return $this->responseError($exception->getLine() . ":" . $exception->getMessage());
        }
    }

    public function getClientProductDataString(Request $request)
    {
        try {
            list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
            $input             = $request->all();
            $limit             = array_get($input, 'limit', 0);
            $input['store_id'] = $store_id;
            $input['area_ids'] = $area_ids ?? [];
            $input['group_id'] = $group_id;

            $request->merge($input);
            $setting = Setting::where('code', 'CONFIG-PRODUCT')->where('store_id', $store_id)->where('company_id', $company_id)->first();
            if ($setting) {
                $setting = json_decode($setting->data);
                if (isset($input['is_new']) && $setting[0]->key == 'NEW' && $setting[0]->value == 0) {
                    return ['data' => []];
                }
            }
            // if(!empty($input['id'])){
            //     $id_product = explode(',', $input['id']);        
            //     $input['id'] = $id_product;
            // }
            // $products = $this->model->searchClient($input, [
            //     'brand:id,name',
            //     'area:id,name',
            //     'stores:id,name',
            //     'storeOrigin:id,name',
            //     'category_code:slug'
            // ], $limit);

            // $products =  DB::table('products')->whereNull('deleted_at')->where('status', 1)
            // ->orderBy('id', 'DESC');

            if (!empty($limit)) {
                $products = DB::table('products')->whereNull('deleted_at')->where('status', 1)
                    ->orderBy('id', 'DESC')->limit($limit)->get();
            }

            if (empty($limt)) {
                $products = DB::table('products')->whereNull('deleted_at')->where('status', 1)
                    ->orderBy('id', 'DESC')->get();
            }

            // $products->get();
            // return $products;
            $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
                PromotionHandle::TYPE_USING_PRODUCT,
                $company_id
            );

            $t = new ProductModel();
            return ['data' => json_encode($t->transformProduct($products, $promotionPrograms))];
            // if (!empty($input['landing_page']) && $input['landing_page'] == "show") {
            //     return $this->response->paginator($products, new ProductLandingPageClientTransformer($promotionPrograms));
            // }
            // return $this->response->paginator($products, new ProductClientTransformerDataString($promotionPrograms));
        }
        catch (\Exception $exception) {
            return $this->responseError($exception->getLine() . ":" . $exception->getMessage());
        }
    }

    /**
     * Get client product sale off
     * @param Request $request
     * @param PromotionHandle $promotionHandle
     * @return array[]|\Dingo\Api\Http\Response
     */
    public function getClientProductSaleOff(Request $request, PromotionHandle $promotionHandle)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input             = $request->all();
        $input['store_id'] = $store_id;
        $input['area_ids'] = $area_ids ?? [];
        $input['group_id'] = $group_id;
        $request->merge($input);
        $promotionProgramByProducts = $promotionHandle->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );

        if ($promotionProgramByProducts->isEmpty()) {
            return ['data' => []];
        }

        $products = $this->model->searchClient($input, [
            'brand:id,name',
            'area:id,name',
            'stores:id,name',
            'storeOrigin:id,name',
        ], 0);

        foreach ($products as $key => $product) {
            $promotions = $promotionHandle->promotionApplyProduct($promotionProgramByProducts, $product);

            if (empty($promotions) || $promotions->isEmpty()) {
                unset($products[$key]);
            }
        }

        return $this->response->collection($products, new ProductClientTransformer($promotionProgramByProducts));
    }

    public function getClientProductDetail($id, Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $request->merge(['group_id' => $group_id]);

        $product      = Product::with(['variants', 'brand:id,name'])
            ->withCount('favorites')
            ->where('id', $id)
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });
        $category_ids = (new Category())->getIdsOfProduct($store_id, $area_ids);

        $product = $product->where(function ($q) use ($category_ids) {
            foreach ($category_ids as $item) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
            }
        });
        $product = $product->with(['priceDetail', 'stores:id,name', 'storeOrigin:id,name', 'shop:id,name'])->first();
        //        if (empty($product) || empty($product['shop_id'])) {
        if (empty($product)) {
            return ['data' => []];
        }

        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );

        $notify = null;
        if ($userId = TM::getCurrentUserId()) {
            $cart            = new Cart();
            $shippingAddress = ShippingAddress::model()->where(['user_id' => $userId, 'is_default' => 1])->first();
            if ($shippingAddress) {
                $notify = $cart->checkAddress($product->sale_area, $shippingAddress->city_code, $shippingAddress->district_code, $shippingAddress->ward_code);
            }
        }

        return $this->response->item($product, new ProductDetailClientTransformer($promotionPrograms, $notify));
    }

    public function getClientRelatedProduct($id, Request $request, ProductClientTransformer $productClientTransformer)
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

        $input   = $request->all();
        $limit   = array_get($input, 'limit', 20);
        $product = Product::model()->where(['id' => $id, 'store_id' => $store_id])->first();
        if (empty($product)) {
            return ['data' => []];
        }
        $tags        = $product->tags;
        $related_ids = $product->related_ids;

        $result = Product::model()->where('store_id', $store_id);
        if (!empty($tags)) {
            $tags = explode(",", $tags);
            $result->where(function ($q) use ($tags) {
                foreach ($tags as $tag) {
                    $q->orWhere(DB::raw("CONCAT(',',tags,',')"), 'like', "%,$tag,%");
                }
            });
        }
        if (!empty($related_ids)) {
            $related_ids = explode(",", $related_ids);
            $result->orWhereIn('id', $related_ids);
        }

        if (TM::getMyUserType() != USER_TYPE_USER) {
            $category_id_pro = Category::model()->select([DB::raw('group_concat(id) as cate_ids')])
                ->where(['category_publish' => '1', 'product_publish' => '1'])->first();
            if (!empty($category_id_pro->cate_ids)) {
                $category_ids = explode(',', $category_id_pro->cate_ids);
                $result       = $result->where(function ($q) use ($category_ids) {
                    foreach ($category_ids as $item) {
                        $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                    }
                });
            }
        }

        if ($limit) {
            if ($limit === 1) {
                $result = $result->first();
            } else {
                $result = $result->paginate($limit);
            }
        }
        return $this->response->paginator($result, $productClientTransformer);
    }

    public function getClientRelatedProductAdvance($id, Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $input = $request->all();
        $request->merge(['group_id' => $group_id]);
        $limit = array_get($input, 'limit', 20);

        $product = Product::where(['id' => $id])->first();
        if (empty($product)) {
            return ['data' => []];
        }

        $tags        = $product->tags;
        $related_ids = $product->related_ids;

        $short_descriptions = explode(" ", $product->short_description);

        $cate_ids = !empty($product->category_ids) ? explode(',', $product->category_ids) : [];

        $result = Product::whereHas('stores', function ($query) use ($store_id) {
            $query->where('store_id', $store_id);
        })
            ->where(function ($q) use ($tags, $related_ids, $short_descriptions, $cate_ids) {
                if (!empty($tags)) {
                    $tags = explode(",", $tags);
                    foreach ($tags as $tag) {
                        $q->orWhere(DB::raw("CONCAT(',',tags,',')"), 'like', "%,$tag,%");
                    }
                }
                if (!empty($related_ids)) {
                    $related_ids = explode(",", $related_ids);
                    $q->orWhereIn('id', $related_ids);
                }

                //                $word_count    = count($short_descriptions);
                //                $word_collects = [];
                //                for ($i = 0; $i < $word_count; $i++) {
                //                    for ($j = 1; $j < $word_count; $j++) {
                //                        $word = "";
                //                        for ($k = $i; $k <= $j; $k++) {
                //                            $word .= $short_descriptions[$k] . " ";
                //                        }
                //                        $word = rtrim($word);
                //                        //$q->orWhere('short_description', 'like', "%$word%");
                //                        $word_collects[$word] = $word;
                //                    }
                //                }
                //
                //                $keys = array_map('strlen', array_keys($word_collects));
                //                array_multisort($keys, SORT_DESC, $word_collects);

                //                $q->orWhereIn('short_description', array_values($word_collects));

                if (!empty($cate_ids)) {
                    foreach ($cate_ids as $cate_id) {
                        $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$cate_id,%");
                    }
                }
            });

        $category_ids = (new Category())->getIdsOfProduct($store_id, $area_ids);

        $result = $result->where(function ($q) use ($category_ids) {
            foreach ($category_ids as $item) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
            }
        });

        $result = $result->with(['brand:id,name', 'area:id,name'])
            ->withCount('favorites')
            ->paginate($limit);

        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );

        return $this->response->paginator($result, new ProductClientTransformer($promotionPrograms));
    }

    public function getClientProductByCategory(Request $request, ProductTransformer $productTransformer)
    {
        $input   = $request->all();
        $limit   = array_get($input, 'limit', 20);
        $product = $this->model->searchProductByCategory($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($product, $productTransformer);
    }

    public function getClientTopProductSearch(Request $request, ProductTransformer $transformer)
    {
        $limit    = array_get($request->all(), 'limit', 10);
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
        $data            = [];
        $searchHistories = SearchHistory::model();
        $storeId         = $store_id ?? null;
        if (!empty($storeId)) {
            $searchHistories = $searchHistories->where('store_ids', $storeId);
        }
        $searchHistories = $searchHistories->get()->toArray();
        foreach ($searchHistories as $searchHistory) {
            $data[] = $searchHistory['data'];
        }
        $arrayMerge = [];
        foreach (array_filter($data) as $key => $datum) {
            $arrayMerge = array_merge($arrayMerge, explode(",", $datum));
        }
        $countValues = array_flip(array_count_values($arrayMerge));
        krsort($countValues);
        $products = Product::model()->whereIn('id', array_values($countValues))->paginate($limit);
        return $this->response->paginator($products, $transformer);
    }

    public function getClientTopProductSale(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input             = $request->all();
        $limit             = array_get($input, 'limit', 20);
        $input['store_id'] = $store_id;
        $input['area_ids'] = $area_ids ?? [];
        $input['group_id'] = $group_id;
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
        $setting = Setting::where('code', 'CONFIG-PRODUCT')->where('store_id', $store_id)->where('company_id', $company_id)->first();
        if ($setting) {
            $setting = json_decode($setting->data);
            if (isset($input['is_hot']) && $setting[1]->key == 'HOT' && $setting[1]->value == 0) {
                return ['data' => []];
            }
        }


        $products = $this->model->searchClient($input, [
            'brand:id,name',
            'area:id,name',
            'stores:id,name',
            'storeOrigin:id,name'
        ], $limit);

        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );
        $products          = Product::model()->where('status', 1)->where('store_id', $store_id)->where('sold_count', '>', '0');

        if (TM::getMyUserType() != USER_TYPE_USER) {
            $category_id_pro = Category::model()->select([DB::raw('group_concat(id) as cate_ids')])
                ->where(['category_publish' => '1', 'product_publish' => '1'])->first();
            if (!empty($category_id_pro->cate_ids)) {
                $category_ids = explode(',', $category_id_pro->cate_ids);
                $products     = $products->where(function ($q) use ($category_ids) {
                    foreach ($category_ids as $item) {
                        $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                    }
                });
            }
        }

        $products = $products->orderBy('sold_count', 'desc')->paginate($limit);

        return $this->response->paginator($products, new ProductClientTransformer($promotionPrograms));
    }

    public function getClientProductDetailBySlug($slug, Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $request->merge(['group_id' => $group_id]);

        $product = Product::with(['variants', 'brand:id,name'])
            ->withCount('favorites')
            ->where('slug', $slug)
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });

        $category_ids = (new Category())->getIdsOfProduct($store_id, $area_ids);

        $product = $product->where(function ($q) use ($category_ids) {
            foreach ($category_ids as $item) {
                $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
            }
        });

        $product = $product->with('priceDetail')->first();
        if (empty($product)) {
            return ['data' => []];
        }
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );
        $notify            = null;
        if ($userId = TM::getCurrentUserId()) {
            $cart            = new Cart();
            $shippingAddress = ShippingAddress::model()->where(['user_id' => $userId, 'is_default' => 1])->first();
            if ($shippingAddress) {
                $notify = $cart->checkAddress($product->sale_area, $shippingAddress->city_code, $shippingAddress->district_code, $shippingAddress->ward_code);
            }
        }
        return $this->response->item($product, new ProductDetailClientTransformer($promotionPrograms, $notify));
    }

    /**
     * Client get comment by product
     * @param $productId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function clientGetCommentByProduct(Request $request, $productId)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();

        $results = ProductComment::where('product_id', $productId)
            ->whereIn('type', [PRODUCT_COMMENT_TYPE_RATE, PRODUCT_COMMENT_TYPE_QUESTION])
            ->where('store_id', $store_id)
            ->where('is_active', 1)
            ->where('company_id', $company_id)
            ->where('parent_id', $request->input('parent_id', null))
            ->with([
                'children' => function ($query) {
                    $query->withCount(['children']);
                },
            ])
            ->paginate($request->input('limit', 20));

        return $this->response->paginator($results, new CommentByProductTransformer());
    }

    /**
     * Client get questions by product
     * @param $productId
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function clientGetQuestionByProduct(Request $request, $productId)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $results = ProductComment::where('product_id', $productId)
            ->where('type', PRODUCT_COMMENT_TYPE_QAA)
            ->where('store_id', $store_id)
            ->where('company_id', $company_id)
            ->where('parent_id', $request->input('parent_id', null))
            ->with([
                'children' => function ($query) {
                    $query->withCount(['children']);
                },
            ])
            ->paginate($request->input('limit', 20));

        return $this->response->paginator($results, new CommentByProductTransformer());
    }

    private function count_star($product_id, $star)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $data = ProductComment::model()
            ->where('store_id', $store_id)
            ->where('company_id', $company_id)
            ->where('type', PRODUCT_COMMENT_TYPE_RATE)
            ->where('product_id', $product_id);
        if (!empty($star)) {
            $data = $data->where('rate', $star);
        }
        $data = $data->select('rate')->get()->toArray();
        return (int)count($data);
    }

    public function getStarRate($id, Request $request)
    {
        $star_1 = $this->count_star($id, 1);
        $star_2 = $this->count_star($id, 2);
        $star_3 = $this->count_star($id, 3);
        $star_4 = $this->count_star($id, 4);
        $star_5 = $this->count_star($id, 5);
        $total  = $this->count_star($id, null);

        $result['star']       = [
            '1_star'         => $star_1,
            'percent_1_star' => $star_1 > 0 ? round($star_1 / $total * 100, 2) : 0,
            '2_star'         => $star_2,
            'percent_2_star' => $star_2 > 0 ? round($star_2 / $total * 100, 2) : 0,
            '3_star'         => $star_3,
            'percent_3_star' => $star_3 > 0 ? round($star_3 / $total * 100, 2) : 0,
            '4_star'         => $star_4,
            'percent_4_star' => $star_4 > 0 ? round($star_4 / $total * 100, 2) : 0,
            '5_star'         => $star_5,
            'percent_5_star' => $star_5 > 0 ? round($star_5 / $total * 100, 2) : 0,
        ];
        $result['total_rate'] = [
            'total' => $total,
        ];
        $start                = $star_1 + $star_2 + $star_3 + $star_4 + $star_5;
        $result['avg_star']   = [
            'avg'        => $start > 0 ? $avg = round(($star_1 * 1 + $star_2 * 2 + $star_3 * 3 + $star_4 * 4 + $star_5 * 5) / $start, 2) : 0,
            'avg_format' => $avg ?? "0" . "/5",
        ];
        return response()->json(['data' => $result]);
    }

    public function searchLite(Request $request, ProductListTransformer $productListTransformer)
    {
        $input    = $request->all();
        $limit    = array_get($input, 'limit', 20);
        $products = $this->model->search($input, [
            'category',
            'rewardPoints.userGroup',
            'stores',
            'options.option',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
        ], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($products, $productListTransformer);
    }

    ########################################Product School####################################
    public function getProduct(Request $request, ProductClientListTransformer $clientListTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (empty($store_id)) {
            return ['data' => null];
        }
        $input             = $request->all();
        $input['store_id'] = $store_id;
        $limit             = array_get($input, 'limit', 20);
        $products          = $this->model->search($input, [
            'category',
            'rewardPoints.userGroup',
            'stores',
            'options.option',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
        ], $limit);
        DB::beginTransaction();
        foreach ($products as $item) {
            $data[] = $item['id'];
        }

        if (!empty($input['code'])) {
            $dataInput['code'] = $input['code'];
        }
        if (!empty($input['name'])) {
            $dataInput['name'] = $input['name'];
        }
        if (!empty($dataInput)) {
            $searchHistoryModel = new SearchHistoryModel();
            $searchHistoryModel->create([
                'search_by' => !empty($dataInput) ? implode(",", array_keys($dataInput)) : null,
                'keyword'   => !empty($dataInput) ? implode(",", array_filter($dataInput)) : null,
                'data'      => !empty($data) ? implode(",", $data) : null,
                'store_ids' => $input['store_id'],
            ]);
        }
        DB::commit();
        Log::view($this->model->getTable());
        return $this->response->paginator($products, $clientListTransformer);
    }

    public function keyWordSearchProduct(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (empty($store_id)) {
            return ['data' => null];
        }
        $input = $request->all();
        if (!empty($input['cat'])) {
            $category   = Category::model()->where('id', $input['cat'])->first();
            $id_propety = $category->categoryProperties->map(function ($detail) {
                return [
                    'id' => $detail->id,
                ];
            });
            if (!empty($category)) {
                if (!empty($category->property)) {
                    $variant = [];
                    foreach (json_decode($category->property) as $proper) {
                        $propety   = Property::model()->where('id', $proper->id)->first();
                        $variant[] = [
                            'property_id'   => $propety->id,
                            'property_code' => $propety->code,
                            'name'          => $propety->name,
                            'variant'       => $proper->variants,
                        ];
                    }
                }
            }
            // if (!empty($id_propety)) {
            //     $variant = [];
            //     foreach ($id_propety as $key) {
            //         $propety   = Property::model()->where('id', $key['id'])->first();
            //         $variant[] = [
            //             'property_id'   => $propety->id,
            //             'property_code' => $propety->code,
            //             'name'          => $propety->name,
            //             'variant'       => $propety->variant->map(function ($detail) use ($propety) {
            //                 return [
            //                     'id'            => $detail->id,
            //                     'code'          => $detail->code,
            //                     'name'          => $detail->name,
            //                     'property_id'   => $propety->id,
            //                     'property_code' => $propety->code,
            //                 ];
            //             })
            //         ];
            //     }
            // }
        }
        if (empty($input['cat'])) {
            $property = Property::model()->where('store_id', $store_id)->where('company_id', $company_id)->get();
            $variant  = [];
            foreach ($property as $key) {
                $variant[] = [
                    'property_id'   => $key->id,
                    'property_code' => $key->code,
                    'name'          => $key->name,
                    'variant'       => $key->variant->map(function ($detail) use ($key) {
                        return [
                            'id'            => $detail->id,
                            'code'          => $detail->code,
                            'name'          => $detail->name,
                            'checked'       => "fasle",
                            'property_id'   => $key->id,
                            'property_code' => $detail->code,
                        ];
                    })
                ];
            }
        }
        $code_category = ['0-6THANG', '6-12THANG', 'TU1-2TUOI', 'TREN1TUOI', 'TREN2TUOI'];
        $category_code = Category::model()->select('id', 'name', 'slug', 'property')->whereIn('code', $code_category)->get();
        foreach ($category_code as $item) {
            $category_name[] = [
                'id'    => $item['id'],
                'value' => $item['name'],
                'slug'  => $item['slug']
            ];
        }
        $price = [
            [
                "name"  => "Dưới 50,000đ",
                "value" => 50000
            ],
            [
                "name"  => "Từ 50,000đ - 100,000đ",
                "value" => [50000, 100000]
            ],
            [
                "name"  => "Từ 100,000đ - 200,000đ",
                "value" => [100000, 200000]
            ],
            [
                "name"  => "Trên 200,000đ",
                "value" => 200000
            ],
        ];
        return [
            'variant_propety' => $variant ?? [],
            // "price"           => $price ?? [],
            // "category"        => $category_name ?? [],
        ];
    }

    public function getProductDetail($id, ProductClientListTransformer $clientListTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (empty($store_id)) {
            return ['data' => null];
        }
        $product = Product::model()->with([
            'category',
            'rewardPoints.userGroup',
            'stores',
            'options.option',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
        ])->where('id', $id)->first();
        if (empty($product)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($product, $clientListTransformer);
    }

    public function exportProduct(Request $request)
    {
        //ob_end_clean();
        ini_set('max_execution_time', '600');
        set_time_limit(600);

        $input   = $request->all();
        $date    = date('YmdHis', time());
        $product = Product::model()->with(['warehouse', 'unit', 'specification', 'getAge', 'category_code', 'getManufacture', 'brand'])
            ->where(function ($q) use ($input) {
                if (!empty($input['name'])) {
                    $q->where('name', 'like', "%{$input['name']}%");
                }
                if (!empty($input['code'])) {
                    $q->where('code', $input['code']);
                }
                if (!empty($input['category_ids'])) {
                    $category_ids = $input['category_ids'];
                    $category_id  = explode(',', $category_ids);
                    $q->where(function ($q) use ($category_id) {
                        foreach ($category_id as $item) {
                            $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                        }
                    });
                }
                if (!empty($input['category_id'])) {
                    $category_id = $input['category_id'];
                    $q->where(function ($q) use ($category_id) {
                        $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$category_id,%");
                    });
                }
                if (!empty($input['city_code'])) {
                    $city_code = $input['city_code'];
                    $q->where(function ($q) use ($city_code) {
                        $q->orWhere(DB::raw("CONCAT(',',city_area_code,',')"), 'like', "%,$city_code,%");
                    });
                }
                if (!empty($input['district_code'])) {
                    $district_code = $input['district_code'];
                    $q->where(function ($q) use ($district_code) {
                        $q->orWhere(DB::raw("CONCAT(',',district_area_code,',')"), 'like', "%,$district_code,%");
                    });
                }
                if (!empty($input['ward_code'])) {
                    $ward_code = $input['ward_code'];
                    $q->where(function ($q) use ($ward_code) {
                        $q->orWhere(DB::raw("CONCAT(',',ward_area_code,',')"), 'like', "%,$ward_code,%");
                    });
                }
                if (isset($input['is_featured'])) {
                    $q->where('is_featured', '=', $input['is_featured']);
                }
                if (isset($input['combo_liked'])) {
                    $q->where('combo_liked', $input['combo_liked']);
                }
                if (isset($input['exclusive_premium'])) {
                    $q->where('exclusive_premium', $input['exclusive_premium']);
                }
                if (isset($input['status'])) {
                    $q->where('status', $input['status']);
                }
                if (!empty($input['type'])) {
                    $q->where('type', 'like', "%{$input['type']}%");
                }
                if (!empty($input['publish_status'])) {
                    $q->where('publish_status', "{$input['publish_status']}");
                }
            })
            ->whereHas('stores', function ($q) use ($input) {
                $q->where('id', TM::getCurrentStoreId());
            })
            ->get();

        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            TM::getCurrentCompanyId()
        );

        foreach ($product as $key => $value) {
            $html = new Html2Text($value['description']);
            if (!empty($value['category_ids'])) {
                $category_id = explode(',', $value['category_ids']);
                $cagory      = Category::model()->with('parent')->whereIn('id', $category_id)->get();
                $cat         = [];
                foreach ($cagory as $item) {

                    $cat[]     = empty($item['parent_id']) ? $item['code'] : array_get($item, "parent.code", "");
                    $sub_cat[] = !empty($item['parent_id']) ? $item['code'] : array_get($item, "parent.code");
                }
            }
            $category_flash_sale_ids = [];
            $product_flash_sale_ids  = [];
            if (!empty($promotionPrograms)) {
                foreach ($promotionPrograms as $val) {

                    // print_r($value->start_date);die;
                    if ($val->promotion_type == 'FLASH_SALE') {
                        foreach (json_decode($val->act_categories) as $key) {
                            $category_flash_sale_ids[] = $key->category_id;
                        }
                        foreach (json_decode($val->act_products) as $key) {
                            $product_flash_sale_ids[] = $key->product_id;
                        }
                        $order_sale = OrderDetail::model()
                            ->join('orders', 'orders.id', 'order_details.order_id')
                            ->whereRaw("order_details.created_at BETWEEN '$val->start_date' AND '$val->end_date'")
                            ->where('orders.status', '!=', 'CANCELED')
                            ->where('order_details.product_id', $value->id)
                            ->groupBy('order_details.product_id')
                            ->sum('order_details.qty');
                        if ($order_sale > $value->qty_flash_sale) {
                            $order_sale = $value->qty_flash_sale;
                        }
                        if (!empty($order_sale)) {
                            $order_sale = 0;
                        }
                    }
                }
            }
            // $price = $product->priceDetail2($product->sale_area);
            // $price = $price > 0 ? $price : $product->price;
            $price = Arr::get($value->priceDetail($value), 'price', $value->price);
            $special            = null;
            $promotionProgram   = (new PromotionHandle())->promotionApplyProduct($promotionPrograms, $value);
            $promotionPrice     = 0;
            if (!empty($promotionProgram) && !$promotionProgram->isEmpty()) {
                foreach ($promotionProgram as $promotion) {
                    $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $value->id, $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);
                }
                $special          = $price - $promotionPrice;
            }
            $data[] = [
                'name'                 => $value['name'],
                'code'                 => $value['code'],
                'price'                => $value['price'],
                'status'               => $value['status'],
                'warehouse_quantity'   => array_get($value, "warehouse.quantity"),
                'qr_scan'              => $value['qr_scan'] ?? 0,
                'length'               => $value['length'],
                'width'                => $value['width'],
                'height'               => $value['height'],
                'length_class'         => $value['length_class'],
                'weight'               => $value['weight'],
                'weight_class'         => $value['weight_class'],
                'sold_count'           => $value['sold_count'],
                'unit_name'            => array_get($value, "unit.name", 0),
                'specification_value'  => array_get($value, "specification.value", 0),
                'get_age_name'         => array_get($value, "getAge.name", ""),
                'capacity'             => $value['capacity'],
                'cat'                  => implode(",", array_filter($cat)) ?? "",
                'sub_cat'              => implode(",", array_filter($sub_cat)) ?? "",
                'expiry_date'          => $value['expiry_date'],
                'area_name'            => $value['area_name'],
                'cadcode'              => $value['cadcode'],
                'get_manufacture_code' => array_get($value, "getManufacture.code"),
                'brand_name'           => array_get($value, "brand.name"),
                'child_brand_name'     => $value['child_brand_name'],
                'short_des'            => $value['short_description'],
                'des'                  => $html->gettext(),
                'special'              => $special,
            ];
        }
        //ob_start(); // and this
        return Excel::download(new ProductExport($data), 'list_product_' . $date . '.xlsx');
    }


    public function productExportExcel()
    {
        //ob_end_clean(); // this
        $date    = date('YmdHis', time());
        $product = Product::model()->with(['warehouse'])->get();
        //ob_start(); // and this
        return Excel::download(new ProductExport($product), 'list_product_' . $date . '.xlsx');
    }

    /*=======================================================================================================*/

    public function productFilter(Request $request)
    {
        $data     = $request->all();
        $limit    = $request->get('limit', 9999);
        $category = $request->get('cat', null);
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (!$data) {
            return ['data' => null];
        }
        ksort($data);
        $product = Product::with([
            'category',
            'getAge',
            'getManufacture',
            'rewardPoints.userGroup',
            'stores',
            'stores',
            'unit:id,name',
            'options.option',
            'discounts.product',
            'discounts.userGroup',
            'promotions.product',
            'promotions.userGroup',
            'versions.product',
            'versions.productVersion',
            'brand:id,name,description',
            'area:id,code,name,image_id,description',
            'warehouse:id,product_id,quantity',
        ])
            ->where('products.status', 1)
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });
        //
        foreach ($data['data'] as $datum) {
            $product->where(function ($q) use ($datum, $category) {
                $datum = array_pluck($datum, 'id');
                $q->where(function ($q) use ($datum, $category) {
                    foreach ($datum as $item) {
                        $q->orWhere(DB::raw("CONCAT(',',property_variant_root_ids,',')"), 'LIKE', "%,$item,%");
                    }
                });
            });
        }
        if (!empty($category)) {
            $product->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$category,%");
        }
        $products          = $product->paginate($limit);
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );
        return $this->response->paginator($products, new ProductClientTransformer($promotionPrograms));
    }

    public function updateSortOrder(Request $request)
    {
        $data = $request->get('data', null);
        if (!$data || !is_array($data)) {
            return $this->responseError("Vui lòng nhập dữ liệu hoặc dữ liệu nhập vào không phải là mảng.");
        }
        DB::beginTransaction();
        $sql = "LOCK TABLE products WRITE; ";
        foreach ($data as $key => $value) {
            $sql .= "UPDATE `products` SET sort_order = $key WHERE id = $value; ";
        }
        $sql .= "UNLOCK TABLES;";

        DB::unprepared($sql);
        DB::commit();
        return response()->json(['status' => "OK", 'message' => "Successful"]);
    }
    
    public function get_product_admin(Request $request) {
        $input = $request->all();
        // dd($input);
        // if(empty($input['dis'])) {
        //     return $this->response->error("Vui lòng nhập kho bán", 400);
        // }
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $limit             = array_get($input, 'limit', 20);
        $input['store_id'] = $store_id;
        $input['area_ids'] = $area_ids ?? [];
        $input['group_id'] = $group_id;
        $input['is_admin'] = true;
        $products = (new ProductModel())->search($input, [], $limit);
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );

        return $this->response->paginator($products, new ProductListAdminTransformer($promotionPrograms, $input));
    }
}
