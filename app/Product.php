<?php

/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

use App\Supports\DataUser;
use App\Supports\TM_Error;
use App\V1\Models\PriceDetailModel;
use App\V1\Models\PriceModel;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

/**
 * Class Product
 *
 * @package App
 */
class Product extends BaseModel
{
    /**
     * @var
     */
    protected $client;
    /**
     * The table associated with the model.
     *
     * @var string
     */

    protected $table = 'products';

    protected $fillable
    = [
        'id',
        'code',
        'name',
        'slug',
        'type',
        'tags',
        'tax',
        'attribute_info',
        'sale_area',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'meta_robot',
        'shop_id',
        'age_id',
        'capacity',
        'expiry_date',
        'cadcode',
        'manufacture_id',
        'area_id',
        'area_name',
        'short_description',
        'description',
        'thumbnail',
        'website_ids',
        'category_ids',
        'store_supermarket',
        'category_supermarket_ids',
        'brand_id',
        'child_brand_id',
        'child_brand_name',
        'price',
        'handling_object',
        'discount_unit_type',
        'personal_object',
        'enterprise_object',
        'user_group_id',
        'qty_out_min',
        'sku',
        'upc',
        'qty',
        'length',
        'width',
        'height',
        'length_class',
        'weight_class',
        'weight',
        'status',
        'order',
        'sort_order',
        'view',
        'store_id',
        'is_featured',
        'related_ids',
        'manufacturer_id',
        'gallery_images',
        'custom_date_updated',
        'combo_liked',
        'exclusive_premium',
        'version_name',
        'sold_count',
        'order_count',
        'unit_id',
        'publish_status',
        'data_sync',
        'specification_id',
        'property_variant_ids',
        'count_rate',
        'rate_avg',
        'gift_item',
        'is_active',
        'qty_flash_sale',
        'qr_scan',
        'property_variant_root',
        'property_variant_root_ids',
        'group_code',
        'group_code_ids',
        'cat',
        'subcat',
        'division',
        'source',
        'cad_code_brandy',
        'cad_code_subcat',
        'cad_code_brand',
        'packing',
        'sku_standard',
        'sku_name',
        'p_type',
        'p_attribute',
        'p_variant',
        'p_sku',
        'city_area_code',
        'district_area_code',
        'ward_area_code',
        'is_odd',
        'is_combo',
        'is_combo_multi',
        'product_combo',
        'combo_code_from',
        'barcode',
        'deleted',
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    protected $casts = [
        'product_combo' => 'json'
    ];

    public function getUnit()
    {
        return $this->hasOne(__NAMESPACE__ . '\Unit', 'id', 'unit_id');
    }

    public function unit()
    {
        return $this->hasOne(__NAMESPACE__ . '\Unit', 'id', 'unit_id');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'thumbnail');
    }

    public function category()
    {
        return $this->hasOne(__NAMESPACE__ . '\Category', 'id', 'category_id');
    }

    public function brand()
    {
        return $this->hasOne(Brand::class, 'id', 'brand_id');
    }

    public function category_code()
    {
        return $this->hasOne(__NAMESPACE__ . '\Category', 'id', 'category_ids');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function masterData()
    {
        return $this->hasOne(__NAMESPACE__ . '\MasterData', 'id', 'manufacturer_id');
    }

    public function storeProduct()
    {
        return $this->hasOne(Store::class, 'id', 'store_id');
    }

    public function area()
    {
        return $this->hasOne(__NAMESPACE__ . '\Area', 'id', 'area_id');
    }

    public function storeOrigin()
    {
        return $this->belongsTo(Store::class, 'store_id', 'id');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'product_stores', 'product_id', 'store_id');
    }

    public function shop()
    {
        return $this->hasOne(Store::class, 'id', 'shop_id');
    }


    public function options()
    {
        return $this->hasMany(ProductOption::class, 'product_id', 'id');
    }

    public function promotions()
    {
        return $this->hasMany(ProductPromotion::class, 'product_id', 'id');
    }

    public function rewardPoints()
    {
        return $this->hasMany(ProductRewardPoint::class, 'product_id', 'id');
    }

    public function discounts()
    {
        return $this->hasMany(ProductDiscount::class, 'product_id', 'id');
    }

    public function versions()
    {
        return $this->hasMany(ProductVersion::class, 'product_id', 'id');
    }

    public function productStores()
    {
        return $this->hasMany(ProductStore::class, 'product_id', 'id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }

    public function getPrice($groupId, $companyId, $product)
    {
        $now              = date('Y-m-d H:i:s', time());
        $priceDetailModel = new PriceDetailModel();
        $priceModel       = new PriceModel();
        $result           = Price::model()
            ->join(
                $priceDetailModel->getTable(),
                $priceDetailModel->getTable() . '.price_id',
                '=',
                $priceModel->getTable() . '.id'
            )
            ->where($priceModel->getTable() . '.company_id', $companyId)
            ->where($priceModel->getTable() . '.from', '<=', $now)
            ->where($priceModel->getTable() . '.to', '>=', $now)
            ->where($priceDetailModel->getTable() . '.product_id', $product->id)
            ->orderBy('order', 'ASC')
            ->whereRaw("concat(',' ,group_ids, ',') like '%,$groupId,%'")
            ->first();
        return $result->price ?? $product->price;
    }

    /**
     * HasMany comment
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(ProductComment::class, 'product_id', 'id');
    }

    public function favorites()
    {
        return $this->hasMany(ProductFavorite::class, 'product_id', 'id');
    }

    public function priceDetail($product = null)
    {
        $group_id = app('request')->input('customer_group_id', DataUser::getInstance()->groupId);
        $now = date('Y-m-d', time());
        if (!empty($product)) {

            if (!empty(TM::getCurrentCityCode())) {
                $now = date('Y-m-d', time());
                $price_detail = PriceDetail::model()->where('product_id', $product->id)->join('prices', 'prices.id', '=', 'price_details.price_id')
                    ->where("prices.status", 1)->where("prices.deleted", 0)
                    ->where(DB::raw("CONCAT(',',group_ids,',')"), 'like', "%,$group_id,%")
                    ->whereRaw("'{$now}' BETWEEN prices.from AND prices.to")
                    ->orderByDesc('order');
                $price_detail->Where(function ($q) {
                    $q->whereNull('city_code');
                    $q->orWhere(
                        [
                            [DB::raw("CONCAT(',',city_code,',')"), 'like', "%," . TM::getCurrentCityCode() . ",%"],
                        ]
                    );
                });
                $price_detail = $price_detail->select('price')->first();
                return $price_detail;
            }
            if (empty(TM::getCurrentCityCode())) {
                $price_detail = PriceDetail::model()->where('product_id', $product->id)->join('prices', 'prices.id', '=', 'price_details.price_id')
                    ->where("prices.status", 1)
                    ->where("prices.deleted", 0)
                    ->where(DB::raw("CONCAT(',',group_ids,',')"), 'like', "%,$group_id,%")
                    ->where(DB::raw("CONCAT(',',sale_area,',')"), 'like', "%,79,%")
                    ->whereRaw("'{$now}' BETWEEN prices.from AND prices.to")
                    ->orderByDesc('order');
                $price_detail = $price_detail->select('price')->first();
                return $price_detail;
            }
        }
        return $this->hasOne(PriceDetail::class, 'product_id', 'id')
            ->whereHas('price', function ($query) use ($group_id) {
                $query->where(DB::raw("CONCAT(',',group_ids,',')"), 'like', "%,$group_id,%");
                $now = date('Y-m-d', time());
                $query->whereDate('from', '<=', $now)
                    ->whereDate('to', '>=', $now);
            })
            ->join('prices', 'prices.id', '=', 'price_details.price_id')
            ->where("prices.status", 1)
            ->where("prices.deleted", 0)
            ->orderByDesc('order');
    }

    public function warehouse()
    {
        return $this->hasOne(WarehouseDetail::class, 'product_id', 'id');
    }

    public function specification()
    {
        return $this->hasOne(Specification::class, 'id', 'specification_id');
    }

    public function getAge()
    {
        return $this->hasOne(Age::class, 'id', 'age_id');
    }

    public function getManufacture()
    {
        return $this->hasOne(Manufacture::class, 'id', 'manufacture_id');
    }

    public function checkFile($url)
    {
        $this->client = new Client();
        try {
            $response = $this->client->request('GET', $url);
            $r        = $response->getStatusCode();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            $r        = $response['code'];
        }
        return $r;
    }

    public function productPropertyVariants()
    {
        return $this->belongsToMany(PropertyVariant::class, 'product_property_variants');
    }

    public function promotionTagsAndIframe($product = null)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $date = date('Y-m-d H:i:s', time());

        $promotion_tags = PromotionProgram::model()->where(['status' => 1, 'company_id' => $company_id, 'deleted' => 0])
            ->where('start_date', "<=", $date)->where('end_date', '>=', $date)
            ->where('status', 1)
            ->get();
        if (!empty($promotion_tags)) {
            foreach ($promotion_tags as $prod) {
                if ($prod->promotion_type == 'FLASH_SALE') {
                    if (!empty($prod->act_products) || $prod->act_products != []) {
                        $promo_prod = array_pluck(json_decode($prod->act_products), 'product_code');
                        $check_prod = array_search($product->code, $promo_prod);
                        if (is_numeric($check_prod)) {
                            return ['tags' => $prod->tags, 'iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => !empty($prod->iframe_image_id) ? $prod->iframeImage->code : null];
                        }
                    }

                    if (!empty($prod->act_categories) || $prod->act_categories != []) {
                        foreach (json_decode($prod->act_categories) as $item) {
                            $product_cate = Product::model()->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item->category_id,%")->get();
                            if (!empty($product_cate)) {
                                foreach ($product_cate as $p) {
                                    if ($product->id == $p->id) {
                                        return ['tags' => $prod->tags, 'iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => !empty($prod->iframe_image_id) ? $prod->iframeImage->code : null];
                                    }
                                }
                            }
                        }
                    }
                }
                if ($prod->promotion_type != 'FLASH_SALE') {
                    foreach ($prod->conditions as $i => $condition) {
                        if ($product->code == $condition->item_code || in_array($condition->item_id, explode(',', $product->category_ids))) {
                            return ['tags' => $prod->tags, 'iframe_image_id' => $prod->iframe_image_id, 'iframe_image' => !empty($prod->iframe_image_id) ? $prod->iframeImage->code : null];
                        }
                    }
                }
            }
        }
        return null;
    }

    public function enddateFS($product = null, $flash_sale = null)
    {
        if (empty($product) || empty($flash_sale)) {
            return null;
        }
        foreach ($flash_sale as $fs) {
            if ($fs['act_type'] == "sale_off_on_products") {
                foreach ($fs['act_products'] as $prod) {
                    if ($product->code == $prod->product_code) {
                        return ['start_date' => $fs['start_date'], 'end_date' => $fs['end_date'], 'sort' => (int)($prod->sort ?? null)];
                    }
                }
            }
            if ($fs['act_type'] == "sale_off_on_categories") {
                foreach ($fs['act_categories'] as $cate) {
                    if (in_array($cate->category_id, explode(',', $product->category_ids))) {
                        return ['start_date' => $fs['start_date'], 'end_date' => $fs['end_date'], 'sort' => (int)($cate->sort ?? null)];
                    }
                }
            }
        }

        return null;
    }

    // public function getTableColumns() {
    //     return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    // }
    public function getProductCombo()
    {
        return $this->hasOne(Product::class, 'code', 'combo_code_from');
    }
}
