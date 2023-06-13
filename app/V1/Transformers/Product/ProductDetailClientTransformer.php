<?php

namespace App\V1\Transformers\Product;

use App\Category;
use App\Coupon;
use App\File;
use App\Folder;
use App\Foundation\PromotionHandle;
use App\OrderDetail;
use App\Product;
use App\ProductComment;
use App\ProductDiscount;
use App\ProductPromotion;
use App\PromotionProgram;
use App\Property;
use App\PropertyVariant;
use App\Store;
use App\Supports\DataUser;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Support\Facades\DB;
use App\UserGroup;
use App\V1\Controllers\PromotionProgramController;
use App\V1\Models\ProductAttributeModel;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class ProductDetailClientTransformer extends TransformerAbstract
{
    protected $promotionPrograms;
    protected $notify;

    public function __construct($promotionPrograms, $notify = null)
    {
        $this->promotionPrograms = $promotionPrograms;
        $this->notify            = $notify;
    }

    public function transform(Product $product)
    {
        $categoryNutizen = Category::model()->where(['category_publish' => 1, 'is_nutizen' => 1])->get()->pluck('id')->toArray();
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $iframe_image_id = null;
        $iframe_image    = null;

        $PromotionsGiftAndIframe = (new PromotionProgram())->PromotionsGiftAndIframe($product);

        try {
            if (!empty($product->property_variant_ids)) {
                $variant_ids = explode(',', $product->property_variant_ids);
                $variant     = PropertyVariant::model()->select('property_id')->whereIn('id', $variant_ids)->get();
                foreach ($variant as $key => $value) {
                    $attrs[] = $value->property_id;
                }
                $property_ids  = array_unique($attrs);
                $property      = Property::model()->with('variant')->whereIn('id', $property_ids)->get();
                $property_item = [];
                foreach ($property as $key) {
                    $variant_item = [];
                    if (!empty($key['variant'])) {
                        foreach ($key['variant'] as $item) {
                            if (in_array($item['id'], $variant_ids)) {
                                $variant_item[] = [
                                    'id'            => $item['id'],
                                    'name'          => $item['name'],
                                    'code'          => $item['code'],
                                    'property_code' => $key['code'],
                                    'property_id'   => $key['id']
                                ];
                            }
                        }
                    }
                    $property_item[] = [
                        'property_id'   => $key['id'],
                        'property_code' => $key['code'],
                        'name'          => $key['name'],
                        'variant'       => $variant_item ?? [],
                    ];
                }
            }
            $category_ids = explode(',', $product->category_ids);
            $is_nutizen   = $this->checkProductStore($categoryNutizen, $category_ids);
            $item_gift    = json_decode($product->gift_item);
            if (empty($item_gift)) {
                $category = Category::model()->whereIn('id', $category_ids)->get();
                foreach ($category as $value) {
                    if ($value->gift_item) {
                        foreach (json_decode($value->gift_item) as $key => $value) {
                            $item_gift[] = $value;
                        }
                    }
                }
            }
            $flash_sale            = 0;
            $start_date_flash_sale = null;
            $end_date_flash_sale   = null;
            if (!empty($this->promotionPrograms)) {

                foreach ($this->promotionPrograms as $value) {
                    if ($value->promotion_type == 'FLASH_SALE') {
                        if ($value->act_type == "sale_off_on_products") {
                            $promo_prod = array_pluck(json_decode($value->act_products), 'product_code');
                            $check_prod = array_search($product->code, $promo_prod);
                            if (is_numeric($check_prod)) {
                                $flash_sale            = 1;
                                $start_date_flash_sale = $value->start_date;
                                $end_date_flash_sale   = $value->end_date;
                            }
                        }
                        if ($value->act_type == "sale_off_on_categories") {
                            foreach (json_decode($value->act_categories) as $cate) {
                                if (in_array($cate->category_id, explode(',', $product->category_ids))) {
                                    $flash_sale            = 1;
                                    $start_date_flash_sale = $value->start_date;
                                    $end_date_flash_sale   = $value->end_date;
                                    break;
                                }
                            }
                        }
                        if (!empty($start_date_flash_sale) && !empty($end_date_flash_sale)) {
                            $order_sale = OrderDetail::model()
                                ->join('orders', 'orders.id', 'order_details.order_id')
                                ->whereRaw("order_details.created_at BETWEEN '$value->start_date' AND '$value->end_date'")
                                ->where('orders.status', '!=', 'CANCELED')
                                ->where('order_details.product_id', $product->id)
                                ->groupBy('order_details.product_id')
                                ->sum('order_details.qty');
                            if ($order_sale > $product->qty_flash_sale) {
                                $order_sale = $product->qty_flash_sale;
                            }
                            if (empty($order_sale)) {
                                $order_sale = 0;
                            }
                            break;
                        }
                    }
                }
            }
            $fileCode  = object_get($product, 'file.code', null);
            $realPrice = $product->price_down;
            if ($product->price_down) {
                $downFrom = strtotime($product->down_from);
                $downTo   = strtotime($product->down_to);
                $now      = time();
                if ($now < $downFrom || $now > $downTo) {
                    $realPrice = 0;
                }
            }

            $price = Arr::get($product->priceDetail($product), 'price', $product->price);

            if ($price < $product->price) {
                $percent_price        = $product->price - $price;
                $percentage_price_old = round(($percent_price / $product->price) * 100);
            }

            $special            = null;
            $special_start_date = null;
            $special_end_date   = null;
            $special_formated   = null;
            $special_percentage = 0;

            // $promotionPrograms = (new PromotionHandle())->promotionApplyProduct($this->promotionPrograms, $product);
            $promotionPrograms = $this->promotionPrograms;

            $promotionPrice = 0;
            if (!empty($promotionPrograms) && !$promotionPrograms->isEmpty()) {

                $type_promotion   = array_pluck($promotionPrograms, 'promotion_type');
                $keyIsFlashSale = [];
                foreach($type_promotion ?? [] as $_key => $_value){
                    if($_value !== 'FLASH_SALE'){
                        continue;
                    }
                    $keyIsFlashSale[$_key] = $_key;
                }
                // $check_flash_sale = array_search('FLASH_SALE', $type_promotion);

                foreach ($promotionPrograms as $key => $promotion) {

                    if($promotion->promotion_type == 'FLASH_SALE'){
                        if (empty($keyIsFlashSale)){
                            continue;
                        } 
                        if (!in_array($key,$keyIsFlashSale)){
                            continue;
                        }
                    }
                    
                    $chk = false;
                    if(!empty($keyIsFlashSale)){
                        foreach($keyIsFlashSale as $item){
                            $prodPluck = array_pluck($promotionPrograms[$item]->productPromotion, 'product_id');
                            $search_prod = array_search($product->id, $prodPluck);
                            if(is_numeric($search_prod)){
                                $chk = true;
                                break;
                            }
                        }
                    }
                    if(!empty($keyIsFlashSale) && !in_array($key,$keyIsFlashSale) && $chk){
                        continue;
                    }

                    $prod        = array_pluck($promotion->productPromotion, 'product_id');
                    $search_prod = array_search($product->id, $prod);

                    if(!is_numeric($search_prod) ){
                        continue;
                    }

                    // if (is_numeric($check_flash_sale) && !is_numeric($search_prod)) {
                    //     continue;
                    // }

                    if (is_numeric($search_prod)) {
                        $iframe_image_id = $promotion->iframe_image_id;
                        $iframe_image    = $promotion->iframeImage->code ?? null;
                    }

                    $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id, $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);

                }
                $special          = $price - $promotionPrice;
                $special_formated = number_format($special) . "đ";
                if (isset($special)) {
                    $special_percentage = $price != 0 || !empty($price) ? round(($promotionPrice / $price) * 100) : 0;
//                    $special_percentage = round(($promotionPrice / $price) * 100);
                }
            }
            $is_comment = 0;
            if (!empty(TM::getCurrentUserId())) {
                $countProductComment = ProductComment::model()->where('product_id', $product->id)
                    ->where('user_id', TM::getCurrentUserId())
                    ->where('type', PRODUCT_COMMENT_TYPE_RATE)
                    ->count();

                $countProductOrder = OrderDetail::where('product_id', $product->id)->whereHas('order', function ($query) {
                    $query->where('customer_id', TM::getCurrentUserId());
                    $query->where('status', ORDER_STATUS_COMPLETED);
                })->count();

                if ($countProductComment != $countProductOrder) {
                    $is_comment = 1;
                }
            }
            setlocale(LC_MONETARY, 'vi_VN');
            $cateIds           = explode(",", $product->category_ids);
            $productAttributes = (new ProductAttributeModel())->getListByProductId($product->id, true);
            $coupons           = Coupon::model()->where(DB::raw("CONCAT(',',product_ids,',')"), 'like', "%," . $product->id . ",%")->where('status', 1);

            $coupons->orWhere(function ($q) use ($cateIds) {
                foreach ($cateIds as $cateid) {
                    $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$cateid,%")->where('status', 1);
                }
            });
            $coupons     = $coupons->get();
            $coupon_temp = [];
            foreach ($coupons as $key => $value) {
                $coupon_temp[] = [
                    'id'                    => $value->id,
                    'code'                  => $value->code,
                    'name'                  => $value->name,
                    'type'                  => $value->type,
                    'content'               => $value->content,
                    'type_discount'         => $value->type_discount,
                    'type_apply'            => $value->type_apply,
                    'condition'             => $value->condition,
                    'discount'              => $value->discount,
                    'limit_price'           => $value->limit_price,
                    'total'                 => $value->itotald,
                    'mintotal'              => $value->mintotal,
                    'maxtotal'              => $value->maxtotal,
                    'free_shipping'         => $value->free_shipping,
                    'product_ids'           => $value->product_ids,
                    'product_codes'         => $value->product_codes,
                    'product_names'         => $value->product_names,
                    'category_ids'          => $value->category_ids,
                    'category_codes'        => $value->category_codes,
                    'category_names'        => $value->category_names,
                    'product_except_ids'    => $value->product_except_ids,
                    'product_except_codes'  => $value->product_except_codes,
                    'product_except_names'  => $value->product_except_names,
                    'category_except_ids'   => $value->category_except_ids,
                    'category_except_codes' => $value->category_except_codes,
                    'category_except_names' => $value->category_except_names,
                    'date_start'            => date("d-m-Y", strtotime($value->date_start)),
                    'date_end'              => date("d-m-Y", strtotime($value->date_end)),
                    'uses_total'            => $value->uses_total,
                    'uses_customer'         => $value->uses_customer,
                    'status'                => $value->status,
                    'company_id'            => $value->icompany_idd,
                    'store_id'              => $value->store_id,
                    'deleted'               => $value->deleted,
                    'created_at'            => date("d-m-Y H:i:s", strtotime($value->created_at)),
                    'created_by'            => date("d-m-Y H:i:s", strtotime($value->created_by)),
                    'updated_at'            => date("d-m-Y H:i:s", strtotime($value->updated_at)),
                    'updated_by'            => date("d-m-Y H:i:s", strtotime($value->updated_by)),
                    'coupon_code'           => $value->coupon_code,
                    'coupon_name'           => $value->coupon_name,
                    'coupon_price'          => $value->coupon_price,
                    'thumbnail'             => $value->thumbnail,
                    'thumbnail_id'          => $value->thumbnail_id
                ];
            }

            if (empty($iframe_image_id) && empty($iframe_image)) {
                $iframe_image_id = Arr::get($product->promotionTagsAndIframe($product), 'iframe_image_id', null);
                $iframe_image    = Arr::get($product->promotionTagsAndIframe($product), 'iframe_image', null);
            }

            $output = [
                'id'                           => $product->id,
                'is_comment'                   => $is_comment,
                'code'                         => $product->code,
                'name'                         => $product->name,
                'slug'                         => $product->slug,
                'url'                          => env('APP_URL') . "/product/{$product->slug}",
                'type'                         => $product->type,
                'tags'                         => $product->tags,
                'promotion_tags'               => !empty(Arr::get($product->promotionTagsAndIframe($product), 'tags', null)) ? json_decode(Arr::get($product->promotionTagsAndIframe($product), 'tags', null)) : [],
                'tax'                          => $product->tax,
                'short_description'            => $product->short_description,
                'description'                  => $product->description,
                'thumbnail_id'                 => $product->thumbnail,
                'thumbnail'                    => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'iframe_image_id'              => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? $PromotionsGiftAndIframe['iframe_image_id'] : $iframe_image_id,
                'iframe_image'                 => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? env('GET_FILE_URL') . $PromotionsGiftAndIframe['iframe_image'] : (!empty($iframe_image) ? env('GET_FILE_URL') . $iframe_image : null),
                'gallery_image_ids'            => $product->gallery_images,
                'gallery_images'               => $this->stringToImage($product->gallery_images),
                'category_ids'                 => $product->category_ids,
                'categories'                   => $this->getNameCategory($product->category_ids),
                'favorites_count'              => $product->favorites_count,
                'brand'                        => $product->brand,
                'area'                         => $product->area,
                'variants'                     => $product->variants,
                'productAttributes'            => $productAttributes,
                'price'                        => $price,
                'price_formatted'              => number_format($price) . "đ",
                'original_price'               => $price,
                'original_price_formatted'     => number_format($price) . "đ",
                'old_product_price'            => $product->price == $price ? 0 : $product->price,
                'old_product_price_formatted'  => number_format($product->price) . "đ",
                'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
                'special'                      => $special,
                'special_percentage_formatted' => $special_percentage . "%",
                'attribute_info'               => !empty($product->attribute_info) ? json_decode($product->attribute_info, true) : [],
                'property_variant_ids'         => $product->property_variant_ids,
                //                'property_variant'             => $product->productPropertyVariants->map(function ($item) {
                //                    return $item->only(['id', 'code', 'name']);
                //                }),
                'promotion_price_formatted'    => number_format($promotionPrice) . "đ",
                'promotion_price'              => $promotionPrice,
                'special_formatted'            => $special_formated,
                //                'property_variant'             => $product->productPropertyVariants->map(function ($item) {
                //                    return $item->only(['id', 'code', 'name']);
                //                }),
                //                'promotion_price_formatted'    => !empty($flash_sale) == 1 ? ($order_sale >= $product->qty_flash_sale ? 0 . "đ" : number_format($promotionPrice) . "đ") : number_format($promotionPrice) . "đ",
                //                'promotion_price'              => !empty($flash_sale) == 1 ? ($order_sale >= $product->qty_flash_sale ? 0 : $promotionPrice) : $promotionPrice,
                //                'special_formatted'            => !empty($flash_sale) == 1 ? ($order_sale >= $product->qty_flash_sale ? 0 . "đ" : $special_formated) : $special_formated,
                'special_start_date'           => $special_start_date,
                'special_end_date'             => $special_end_date,
                'special_percentage'           => $special_percentage,
                'real_price'                   => $realPrice,
                'price_down'                   => $product->price_down,
                'down_rate'                    => $product->price != 0 ? $product->price_down * 100 / $product->price : 0,
                'down_from'                    => !empty($product->down_from) ? date(
                    'd-m-Y H:i:s',
                    strtotime($product->down_from)
                ) : null,
                'down_to'                      => !empty($product->down_to) ? date(
                    'd-m-Y H:i:s',
                    strtotime($product->down_to)
                ) : null,
                'handling_object'              => $product->handling_object,
                'personal_object'              => $product->personal_object,
                'enterprise_object'            => $product->enterprise_object,
                'check_flash_sale'             => $flash_sale,
                'sku'                          => $product->sku,
                'upc'                          => $product->upc,
                'qty'                          => Arr::get($product->warehouse, 'quantity', 0),
                'length'                       => $product->length,
                'width'                        => $product->width,
                'height'                       => $product->height,
                'length_class'                 => $product->length_class,
                'weight_class'                 => $product->weight_class,
                'weight'                       => $product->weight,
                'status'                       => $product->status,
                'order'                        => $product->order,
                'view'                         => $product->view,
                'store_id'                     => $product->store_id,
                'store_name'                   => Arr::get($product->storeOrigin, 'name'),
                'shop_info'                    => [
                    'id'   => Arr::get($product->shop, 'id', null),
                    'name' => Arr::get($product->shop, 'name', null)
                ],
                'store_origin'                 => [
                    'id'   => Arr::get($product->storeOrigin, 'id'),
                    'name' => Arr::get($product->storeOrigin, 'name')
                ],
                'stores'                       => $product->stores->map(function ($item) {
                    return $item->only(['id', 'name']);
                }),
                'is_featured'                  => $product->is_featured,
                'related_ids'                  => $product->related_ids,
                'combo_liked'                  => $product->combo_liked,
                'exclusive_premium'            => $product->exclusive_premium,
                //                'related_products'         => $this->getProduct($product->related_ids),
                'manufacturer_id'              => $product->manufacturer_id,
                'manufacturer_name'            => object_get($product, 'masterData.name'),
                'manufacturer_code'            => object_get($product, 'masterData.code'),
                'qty_out_min'                  => $product->qty_out_min,
                'custom_date_updated'          => !empty($product->custom_date_updated) ? date(
                    'd-m-Y',
                    strtotime($product->custom_date_updated)
                ) : null,
                'sold_count'                   => $sold_count = ($product->sold_count ?? 0) + ($order_sale ?? 0),
                'sold_count_formatted'         => format_number_in_k_notation($sold_count),
                'order_count'                  => $product->order_count ?? 0,
                'gift_item'                    => !empty($item_gift) ? array_unique($item_gift, SORT_REGULAR) : [],
                'version_name'                 => $product->version_name,
                'count_rate'                   => $product->count_rate,
                'publish_status'               => $product->publish_status,
                'created_at'                   => date('d-m-Y', strtotime($product->created_at)),
                'updated_at'                   => !empty($product->updated_at) ? date('d-m-Y', strtotime($product->updated_at)) : null,
                'star'                         => $this->getStarRate($product->id),
                'len_rate'                     => (int)count($product->comments->toArray()),
                'star_rating'                  => 0,
                'qty_sale'                     => $order_sale ?? "",
                'qty_flash_sale'               => $product->qty_flash_sale,
                'sale_area'                    => $product->sale_area,
                'property_variant'             => $property_item ?? [],
                'product_gift'                 => $PromotionsGiftAndIframe['product_gift'] ?? [],
                'flash_sale'                   => !empty($this->promotionPrograms) ? $this->promotionPrograms : [],
                'end_date_flash_sale'          => !empty($end_date_flash_sale) ? $end_date_flash_sale : null,
                'coupon_temp'                  => !empty($coupon_temp) ? $coupon_temp : [],
                // 'coupon_by_cate_id'            => !empty($coupon_temp_cate) ? $coupon_temp_cate : [],
                'is_nutizen'                   => $is_nutizen['is_nutizen'],
                'notify'                       => $this->notify,
                'barcode'                      => $product->barcode,
            ];

            return $output;
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
    }

    private function thumbnailToUrl($id)
    {
        $file = File::find($id);
        if (empty($file)) {
            return null;
        }
        return $file->url;
    }

    /**
     * Get gallery_images of product
     * @param $ids
     * @return array|string
     */
    private function stringToImage($ids)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (empty($ids)) {
            return [];
        }
        $result = [];
        // $images = File::model()->whereIn('id', explode(",", $ids))->get();
        // foreach ($images as $key => $image) {
        //     $result[$key]['id']  = $image->id;
        //     $result[$key]['url'] = env('UPLOAD_URL') . '/file/' . $image->code;
        // }

        $allImages = File::model()->where('company_id', $company_id)->pluck('code', 'id')->toArray();
        if (!empty($allImages)) {
            foreach (explode(",", $ids) as $key => $value) {
                if (!$value) {
                    continue;
                }
                $result[$key]['id']  = $value;
                $result[$key]['url'] = env('UPLOAD_URL') . '/file/' . $allImages[$value];
            }
        }
        return $result;
    }

    /**
     * Get folder name image of product
     * @param $id
     * @param $fordel_path
     * @return string
     */
    public function getFolder($id, &$fordel_path)
    {
        $folder = Folder::model()->find($id);
        if (!empty($folder)) {
            $fordel_path = ($folder->folder_name . ',' . $fordel_path);
            if (!empty($folder->parent_id)) {
                $this->getFolder($folder->parent_id, $fordel_path);
            }
        }
        return $fordel_path;
    }

    /**
     * @param $ids
     * @return array|string
     */
    private function getNameCategory($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $category = Category::model()->select(['name'])->whereIn('id', explode(",", $ids))->get()->toArray();
        $category = array_pluck($category, 'name');
        $category = implode(', ', $category);
        return $category;
    }

    private function getProduct($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $product = Product::model()->whereIn('id', explode(",", $ids))->get()->toArray();
        $data    = [];
        foreach ($product as $key => $item) {
            $data[] = [
                'id'                => $item['id'],
                'code'              => $item['code'],
                'name'              => $item['name'],
                'slug'              => $item['slug'],
                'type'              => $item['type'],
                'tags'              => $item['tags'],
                'tax'               => $item['tax'],
                'short_description' => $item['short_description'],
                'description'       => $item['description'],
                'thumbnail_id'      => $item['thumbnail'],
                'thumbnail'         => !empty($this->getImageThumbnail($item['thumbnail'])) ? url('/v0') . "/img/" . 'uploads,' . $this->getImageThumbnail($item['thumbnail']) : null,
                'gallery_image_ids' => $item['gallery_images'],
                'gallery_images'    => $this->getImage($item['gallery_images']),
                'category_ids'      => $item['category_ids'],
                'categories'        => $this->getNameCategory($item['category_ids']),
                'price'             => $item['price'],
                'sku'               => $item['sku'],
                'upc'               => $item['upc'],
                'qty'               => $item['qty'],
                'length'            => $item['length'],
                'width'             => $item['width'],
                'height'            => $item['height'],
                'length_class'      => $item['length_class'],
                'weight_class'      => $item['weight_class'],
                'status'            => $item['status'],
                'order'             => $item['order'],
                'view'              => $item['view'],
                //                'related_ids'       => $item['related_ids'],
                //                'related_products'  => $this->getProduct($item['related_ids']),
                'manufacturer_id'   => $item['manufacturer_id'],
                'created_at'        => date('d-m-Y', strtotime($item['created_at'])),
                'updated_at'        => !empty($item['updated_at']) ? date(
                    'd-m-Y',
                    strtotime($item['updated_at'])
                ) : null,
            ];
        }
        return $data;
    }

    /**
     * @param $ids
     * @return array
     */
    private function getImage($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $images = File::model()->select(['id as file_id', 'url'])->whereIn('id', explode(",", $ids))->get();
        return $images->toArray();
    }

    private function getImageThumbnail($id)
    {
        if (empty($id)) {
            return [];
        }
        $thumbnail = File::model()->select(['file_name'])->where('id', explode(",", $id))->get()->toArray();
        $thumbnail = array_pluck($thumbnail, 'file_name');
        $thumbnail = implode(', ', $thumbnail);
        return $thumbnail;
    }

    private function count_star($product_id, $star)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $data = ProductComment::model()
            ->where('store_id', $store_id)
            ->where('company_id', $company_id)
            ->where('type', PRODUCT_COMMENT_TYPE_RATE)
            ->where('product_id', $product_id)
            ->where('is_active', 1);
        if (!empty($star)) {
            $data = $data->where('rate', $star);
        }
        $data = $data->select('rate')->get()->toArray();
        return (int)count($data);
    }

    public function getStarRate($id)
    {
        $star_1 = $this->count_star($id, 1);
        $star_2 = $this->count_star($id, 2);
        $star_3 = $this->count_star($id, 3);
        $star_4 = $this->count_star($id, 4);
        $star_5 = $this->count_star($id, 5);
        $total  = $this->count_star($id, null);

        $result['total_rate'] = [
            'total' => $total,
        ];
        $start                = $star_1 + $star_2 + $star_3 + $star_4 + $star_5;
        $result['avg_star']   = [
            'avg'        => $start > 0 ? $avg = round(($star_1 * 1 + $star_2 * 2 + $star_3 * 3 + $star_4 * 4 + $star_5 * 5) / $start, 2) : 0,
            'avg_format' => $avg ?? "0" . "/5",
        ];
        return $result;
    }

    function checkProductStore($categoryNutizen, $categories)
    {
        if (empty($categoryNutizen)) {
            return [
                'is_nutizen'  => 0,
                'is_nutifood' => 0
            ];
        }
        foreach ($categories as $category) {
            if (in_array($category, $categoryNutizen)) {
                return [
                    'is_nutizen'  => 1,
                    'is_nutifood' => 0
                ];
            }
        }
        return [
            'is_nutizen'  => 0,
            'is_nutifood' => 1
        ];
    }
}
