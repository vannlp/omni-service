<?php

namespace App;

use App\Supports\DataUser;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PromotionProgram extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'promotion_programs';

    protected $fillable = [
        'code',
        'name',
        'type',
        'view',
        'description',
        'tags',
        'thumbnail_id',
        'iframe_image_id',
        'store_id',
        'general_settings',
        'status',
        'stack_able',
        'multiply',
        'sort_order',
        'start_date',
        'end_date',
        'total_user',
        'used',
        'used_order',
        'total_use_customer',
        'promotion_type',
        'coupon_code',
        'need_login',
        'condition_combine',
        'condition_bool',
        'act_type',
        'act_sale_type',
        'discount_by',
        'act_price',
        'act_exchange',
        'act_point',
        'default_store',
        'group_customer',
        'area',
        'area_ids',
        'act_sale',
        'act_not_product_condition',
        'act_not_special_product',
        'act_max_quality',
        'act_not_products',
        'act_categories',
        'act_not_categories',
        'act_products',
        'act_products_gift',
        'act_gift',
        'limit_qty_flash_sale',
        'min_qty_sale',
        'limit_buy',
        'act_time',
        'order_used',
        'limit_price',
        'act_quatity',
        'act_quatity_sale',
        'data_sync',
        'company_id',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at',
    ];

    protected $casts
    = [
        'act_sale'      => 'json'
    ];


    public function thumbnail()
    {
        return $this->belongsTo(File::class, 'thumbnail_id', 'id');
    }

    public function iframeImage()
    {
        return $this->belongsTo(File::class, 'iframe_image_id', 'id');
    }

    public function conditions()
    {
        return $this->hasMany(PromotionProgramCondition::class);
    }

    public function productPromotion()
    {
        return $this->hasMany(ProductPromotion::class, 'promotion_id', 'id');
    }


    public function getPromotionProgram($companyId = null, $actType = 'sale_off_all_products')
    {
        $companyId = $companyId ?? TM::getCurrentCompanyId();

        $now = Carbon::now()->format('Y-m-d');

        $promotionProgram = PromotionProgram::where('start_date', '<=', $now)
            ->where('act_type', $actType)
            ->where('end_date', '>=', $now)
            ->where('status', 1)
            ->whereColumn('total_user', '>=', 'used')
            ->where('company_id', $companyId)
            ->orderBy('sort_order')
            ->first();

        if (empty($promotionProgram)) {
            return $promotionProgram;
        }

        if (!empty($promotionProgram->group_customer)) {
            $groupCustomer = json_decode($promotionProgram->group_customer);
            if (empty($groupCustomer) || !in_array(DataUser::getInstance()->groupId, $groupCustomer)) {
                return null;
            }
        }

        function parseCategoryIDWithGrandChildren($categoryGrandchildren)
        {
            $result = [];
            foreach ($categoryGrandchildren as $items) {
                $result = array_merge($result, [$items->id]);
                if (!empty($items->grandChildren)) {
                    $result = array_merge($result, $items->grandChildren->pluck('id')->toArray());

                    $result = array_merge($result, parseCategoryIDWithGrandChildren($items->grandChildren));
                }
            }

            return $result;
        }

        switch ($actType) {
            case 'sale_off_all_products':
                if (!empty($promotionProgram) && !empty($promotionProgram->act_categories) && $promotionProgram->act_categories !== '""') {
                    $categoryIds           = array_column(json_decode($promotionProgram->act_categories, true), 'category_id');
                    $categoryGrandChildren = Category::whereIn('id', $categoryIds)->with('grandChildren')->get();
                    $categoryIds           = array_merge($categoryIds, parseCategoryIDWithGrandChildren($categoryGrandChildren));

                    $promotionProgram->categoryIds = $categoryIds;
                }
                break;
            case 'order_discount':
                break;
        }

        return $promotionProgram;
    }

    public function getPriceProduct($promotionProgram, array $productCategoryIds, $price)
    {
        if (!empty($promotionProgram)) {
            $flag = true;
            if (!empty($promotionProgram->categoryIds)) {
                foreach ($promotionProgram->categoryIds as $id) {
                    if (in_array((int)$id, $productCategoryIds)) {
                        $flag = false;
                        break;
                    }
                }
            }

            if ($flag == true) {
                $price = $this->parsePriceBySaleType($promotionProgram, $price);
            }
        }

        return $price;
    }

    public function parsePriceBySaleType($promotionProgram, $price)
    {
        if ($promotionProgram->act_sale_type == 'percentage') {
            $price = $price * ((100 - $promotionProgram->act_price) / 100);
        } else {
            $price = $price - $promotionProgram->act_price;
        }

        return $price;
    }

    public function PromotionsGiftAndIframe($product)
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
        $date = date('Y-m-d H:i:s', time());
        $promotion_gift = PromotionProgram::model()->where(['status' => 1, 'company_id' => $company_id, 'deleted' => 0])
        // ->whereIn('act_type', ['buy_x_get_y', 'combo'])
        ->where('start_date', "<=", $date)->where('end_date', '>=', $date)
        ->orderBy('updated_at', 'ASC')
        ->get();
        $data = [];
        if (!empty($promotion_gift)) {
            foreach ($promotion_gift as $prod) {
                if ($prod->act_type == 'buy_x_get_y') {
                    if (!empty($prod->act_products) || $prod->act_products != []) {

                        $promo_prod = array_pluck(json_decode($prod->act_products), 'product_code');
                        $check_prod = array_search($product->code, $promo_prod);
                        if (is_numeric($check_prod)) {
                            $data = ['iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => $prod->iframeImage->code];
                            if ($prod->act_type == 'buy_x_get_y') {
                                foreach (json_decode($prod->act_products_gift) as $gift) {
                                    $product_gift[] = [
                                        "id_product"        => (int)$gift->product_id,
                                        "product_name"      => $gift->title_gift ?? $gift->product_name,
                                        "qty_gift"          => $gift->qty_gift ?? 1,
                                        "id_promotion"      => $prod->id,
                                        "promotion_name"    => $prod->name
                                    ];
                                }
                                $data[] = ['product_gift' => $product_gift];
                            }
                        }
                    }
                    if (!empty($prod->act_categories) || $prod->act_categories != []) {
                        foreach (json_decode($prod->act_categories) as $item) {
                            $product_cate = Product::model()->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item->category_id,%")->get();
                            if (!empty($product_cate)) {
                                foreach ($product_cate as $p) {
                                    if ($product->id == $p->id) {
                                        if (!empty($prod->iframe_image_id) && !empty($prod->iframeImage->code)){
                                            $data = ['iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => $prod->iframeImage->code];
                                        }

                                        if ($prod->act_type == 'buy_x_get_y') {
                                            foreach (json_decode($prod->act_products_gift) as $gift) {
                                                $product_gift[] = [
                                                    "id_product"        => (int)$gift->product_id,
                                                    "product_name"      => $gift->title_gift ?? $gift->product_name,
                                                    "qty_gift"          => $gift->qty_gift ?? 1,
                                                    "id_promotion"      => $prod->id,
                                                    "promotion_name"    => $prod->name
                                                ];
                                                $data[] = ['product_gift' => $product_gift];
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if ($prod->act_type == 'combo') {
                    if (!empty($prod->act_gift) || $prod->act_gift != []) {
                        foreach (json_decode($prod->act_gift) as $key) {
                            if ($product->id == $key->product_id && !empty($key->gift) && $key->gift != "[]") {
                                $data = ['iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => $prod->iframeImage->code ?? null];
                                foreach ($key->gift as $gift) {
                                    $product_gift[] = [
                                        "id_product"        => (int)$gift->product_id,
                                        "product_name"      => $gift->title_gift ?? $gift->product_name,
                                        "qty_gift"          => $gift->qty_gift ?? 1,
                                        "id_promotion"      => $prod->id,
                                        "promotion_name"    => $prod->name
                                    ];
                                    break;
                                }
                                $data['product_gift'] = $product_gift;
                            }               
                        }
                    }
                }
                // if($prod->act_type != 'combo' && $prod->act_type != 'buy_x_get_y'){
                //     if($prod->act_not_products  == '[]' && $prod->act_categories  == '[]' && $prod->act_not_categories  == '[]' && $prod->act_products  == '[]'){
                //         $data = ['iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => $prod->iframeImage->code ?? null];
                        // $product_gift[] = [
                        //     "id_product"        => $product->id,
                        //     "product_name"      => $product->name,
                        //     "qty_gift"          => 1,
                        //     "id_promotion"      => $prod->id,
                        //     "promotion_name"    => $prod->name
                        // ];
                    //     break;
                    // }
                //    if($prod->act_not_products == '[]'){
                        
                //    }
                //    if($prod->act_categories == '[]'){
                    
                //     }
                //     if($prod->act_not_categories == '[]'){
                        
                //     }
                //     if($prod->act_products == '[]'){
                        
                //     }
                // }
            }
        }
        return $data;
    }
}
