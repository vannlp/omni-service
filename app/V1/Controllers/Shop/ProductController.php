<?php

namespace App\V1\Controllers\Shop;

use App\Category;
use App\Product;
use App\PromotionProgram;
use App\Store;
use App\TM;
use App\UserGroup;
use App\V1\Controllers\BaseController;
use App\V1\Transformers\Shop\ProductDetailTransformer;
use App\V1\Transformers\Shop\ProductListTransformer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController extends BaseController
{
    /**
     * @var int|null $storeId
     */
    protected $storeId;

    /**
     * @var int|null $companyId
     */
    protected $companyId;

    /**
     * @var int|null $groupId
     */
    protected $groupId;

    /**
     * @var int|null $areaId
     */
    protected $areaId;

    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        if (TM::getCurrentUserId()) {
            $this->storeId   = TM::getCurrentStoreId();
            $this->groupId   = TM::getCurrentGroupId();
            $this->companyId = TM::getCurrentCompanyId();
            $group           = UserGroup::find(TM::getCurrentGroupId());
            if (!empty($group) && $group->is_view) {
                $this->areaId = Auth::user()->area_id;
            }
        } else {
            $authorization = app('request')->header('authorization');
            if (!empty($authorization) && strlen($authorization) == 71) {

                $storeToken = str_replace("Bearer ", "", $authorization);

                $store = Store::select(['id', 'company_id'])->where('token', $storeToken)->first();
                if (!$store) {
                    return ['data' => []];
                }
                $this->storeId   = $store->id;
                $this->companyId = $store->company_id;

                $group = UserGroup::where('company_id', $store->company_id)->where('is_default', 1)->first();
                if (!empty($group)) {
                    $this->groupId = $group->id;
                }
            }
        }
    }

    /**
     * Parse category id with grand children
     *
     * @param $categoryGrandchildren
     * @return array
     */
    private function parseCategoryIDWithGrandChildren($categoryGrandchildren)
    {
        $result = [];
        foreach ($categoryGrandchildren as $items) {
            $result = array_merge($result, [$items->id]);
            if (!empty($items->grandChildren)) {
                $result = array_merge($result, $items->grandChildren->pluck('id')->toArray());

                $result = array_merge($result, $this->parseCategoryIDWithGrandChildren($items->grandChildren));
            }
        }

        return $result;
    }

    /**
     * Category with grand children
     *
     * @param Request $request
     * @return array
     */
    private function categoryGrandChildren(Request $request)
    {
        if ($request->has('category_ids')) {
            $categoryGrandchildren = Category::whereIn('id', explode(',', $request->get('category_ids')))
                ->where([
                    'category_publish' => 1,
                    'product_publish'  => 1
                ])
                ->with('grandChildren')
                ->get();

            $category_ids = $this->parseCategoryIDWithGrandChildren($categoryGrandchildren);
        } else {
            $category_ids = Category::where([
                'category_publish' => 1,
                'product_publish'  => 1
            ])->get(['id'])->pluck('id')->toArray();
        }

        return $category_ids;
    }

    /**
     * Get promotion program
     *
     * @return mixed
     */
    private function getPromotionProgram()
    {
        $now = Carbon::now()->format('Y-m-d');

        $promotionProgram = PromotionProgram::where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->where('status', 1)
            ->whereColumn('total_user', '>=', 'used')
            ->where('company_id', $this->companyId)
            ->orderBy('sort_order')
            ->first();

        if (!empty($promotionProgram) && !empty($promotionProgram->act_categories) && $promotionProgram->act_categories !== '""') {
            $categoryIds           = array_column(json_decode($promotionProgram->act_categories, true), 'category_id');
            $categoryGrandChildren = Category::whereIn('id', $categoryIds)->with('grandChildren')->get();
            $categoryIds           = array_merge($categoryIds, $this->parseCategoryIDWithGrandChildren($categoryGrandChildren));

            $promotionProgram->categoryIds = $categoryIds;
        }

        return $promotionProgram;
    }

    /**
     * Get list product
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getListProduct(Request $request)
    {
        $request->merge([
            'category_ids' => $this->categoryGrandChildren($request),
            'group_id'     => $this->groupId,
            'area_id'      => $this->areaId
        ]);
        $products = Product::with([
            'brand:id,name',
            'area:id,name',
            'stores:id,name',
            'storeOrigin:id,name',
            'priceDetail'
        ])
            ->withCount('favorites')
            ->where('publish_status', 'approved')
            ->where('is_active', 1)
            ->where(function ($query) use ($request) {
                foreach ($request->all() as $key => $value) {
                    if (!empty($value)) {
                        switch ($key) {
                            case 'name':
                            case 'code':
                            case 'type':
                                $query->where($key, 'LIKE', "%$value%");
                                break;
                            case 'category_ids':
                                $query->where(function ($query) use ($value) {
                                    foreach ($value as $item) {
                                        $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                                    }
                                });
                                break;
                            case 'brand_id':
                                $query = $query->whereIn($key, (array)explode(',', $value));
                                break;
                            case 'is_featured':
                            case 'area_id':
                                $query->where($key, $value);
                                break;
                            case 'product_favorite_ids':
                                $query->whereIn('id', is_array($value) ? $value : [$value]);
                                break;
                            default:
                                break;
                        }
                    }
                }
            })->paginate($request->input('limit', 20));

        $promotionProgram = $this->getPromotionProgram();

        return $this->response->paginator($products, new ProductListTransformer($promotionProgram));
    }

    /**
     * Get product detail by ID
     *
     * @param $id
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getProductDetail($id, Request $request)
    {
        $request->merge(['group_id' => $this->groupId]);

        $product = Product::with([
            'variants',
            'brand:id,name',
            'area:id,name',
            'storeOrigin:id,name',
            'stores:id,name',
            'priceDetail'
        ])
            ->withCount('favorites')
            ->where('id', $id)
            ->where(function ($query) use ($request) {
                $category_ids = $this->categoryGrandChildren($request);
                foreach ($category_ids as $item) {
                    $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            })->first();

        $promotionProgram = $this->getPromotionProgram();
        return $this->response->item($product, new ProductDetailTransformer($promotionProgram));
    }

    /**
     * Get product detail by slug
     *
     * @param $slug
     * @param Request $request
     * @return \Dingo\Api\Http\Response
     */
    public function getProductDetailBySlug($slug, Request $request)
    {
        $request->merge(['group_id' => $this->groupId]);

        $product = Product::with([
            'variants',
            'brand:id,name',
            'area:id,name',
            'storeOrigin:id,name',
            'stores:id,name',
            'priceDetail'
        ])
            ->withCount('favorites')
            ->where('slug', $slug)
            ->where(function ($query) use ($request) {
                $category_ids = $this->categoryGrandChildren($request);
                foreach ($category_ids as $item) {
                    $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                }
            })->first();

        $promotionProgram = $this->getPromotionProgram();
        return $this->response->item($product, new ProductDetailTransformer($promotionProgram));
    }
}