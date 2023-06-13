<?php

namespace App\V1\Controllers\Client;

use App\Category;
use App\Price;
use App\Product;
use App\Store;
use App\TM;
use App\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductController
{
    /**
     * @var int $storeId
     */
    protected $storeId;

    /**
     * @var int $companyId
     */
    protected $companyId;

    /**
     * @var int $areaId
     */
    protected $areaId;

    /**
     * @var int $groupId
     */
    protected $groupId;

    /**
     * ProductController constructor.
     */
    public function __construct()
    {
        if (!empty(TM::getCurrentUserId())) {
            $this->storeId   = TM::getCurrentStoreId();
            $this->companyId = TM::getCurrentCompanyId();
            $this->groupId   = TM::getCurrentGroupId();
            $group           = UserGroup::find(TM::getCurrentGroupId());
            if (!empty($group) && $group->is_view) {
                $this->areaId = Auth::user()->area_id;
            }
        }
        else {
            $request    = app('request');
            $storeToken = $request->input('store_token');
            if (empty($storeToken)) {
                $authorization = $request->headers->get('authorization');
                if (!empty($authorization)) {
                    $authorization = trim(str_replace('Bearer ', '', $authorization));
                    if (strlen($authorization) == 64) {
                        $storeToken = $authorization;
                    }
                }
            }
            if (!empty($storeToken)) {
                $store = Store::select(['id', 'company_id'])->where('token', $storeToken)->first();
                if (!empty($store)) {
                    $this->storeId   = $store->id;
                    $this->companyId = $store->company_id;
                    $group           = UserGroup::where('company_id', $this->companyId)->where('is_default', 1)->first();
                    if (!empty($group)) {
                        $this->groupId = $group->id;
                    }
                }
            }
        }
    }

    public function getList(Request $request)
    {
        $arrCatId = [];

        if (TM::getMyUserType() != USER_TYPE_USER) {
            $arrCatId = Category::select('id')
                ->where([
                    'category_publish' => 1,
                    'product_publish'  => 1
                ])->get()->pluck('id')->toArray();
        }

        $query = Product::whereHas('stores', function ($query) {
            $query->where('store_id', $this->storeId);
        })
            ->where(function ($query) use ($arrCatId) {
                if (!empty($arrCatId)) {
                    foreach ($arrCatId as $item) {
                        $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                    }
                }

                if (!empty($this->areaId)) {
                    $query->where('area_id', $this->areaId);
                }
            })
            ->where(function ($query) use ($request) {
                if (!empty($request->input('brand_id'))) {
                    $query->where('brand_id', $request->input('brand_id'));
                }
            })
            ->with([
                'priceDetail' => function ($query) use ($request) {
                    $query->whereHas('price', function ($query) {
                        $query->where(DB::raw("CONCAT(',',group_ids,',')"), ",$this->groupId,");
                        $now = date('Y-m-d', time());
                        $query->whereDate('from', '<=', $now)
                            ->whereDate('to', '>=', $now);
                    });

                    $priceFrom = $request->input('price_from');
                    $priceTo   = $request->input('price_to');

                    if (!empty($priceFrom) && !empty($priceTo)) {
                        $query->whereBetween('price_details.price', [$priceFrom, $priceTo]);
                    }

                    if (!empty($priceFrom) && empty($priceTo)) {
                        $query->where('price', '>=', $priceFrom);
                    }

                    if (empty($priceFrom) && !empty($priceTo)) {
                        $query->where('price', '<=>', $priceTo);
                    }

                    $query->join('prices', 'prices.id', '=', 'price_details.price_id')
                        ->orderByDesc('order');
                }
            ])
            ->whereHas('priceDetail', function ($query) use ($request) {
                $query->whereHas('price', function ($query) {
                    $query->where(DB::raw("CONCAT(',',group_ids,',')"), ",$this->groupId,");
                    $now = date('Y-m-d', time());
                    $query->whereDate('from', '<=', $now)
                        ->whereDate('to', '>=', $now);
                });

                $priceFrom = $request->input('price_from');
                $priceTo   = $request->input('price_to');

                if (!empty($priceFrom) && !empty($priceTo)) {
                    $query->whereBetween('price_details.price', [$priceFrom, $priceTo]);
                }

                if (!empty($priceFrom) && empty($priceTo)) {
                    $query->where('price', '>=', $priceFrom);
                }

                if (empty($priceFrom) && !empty($priceTo)) {
                    $query->where('price', '<=>', $priceTo);
                }

                $query->join('prices', 'prices.id', '=', 'price_details.price_id')
                    ->orderByDesc('order');

            })
            ->paginate($request->input('limit', 20));

        return response()->json($query);
    }
}