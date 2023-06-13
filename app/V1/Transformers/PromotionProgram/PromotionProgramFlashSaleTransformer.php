<?php

namespace App\V1\Transformers\PromotionProgram;

use App\Category;
use App\File;
use App\Folder;
use App\Foundation\PromotionHandle;
use App\OrderDetail;
use App\Product;
use App\PromotionProgram;
use App\Supports\TM_Error;
use App\V1\Models\ProductAttributeModel;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use App\ProductComment;
use App\ProductPromotion;
use App\Supports\DataUser;
use App\TM;
use App\V1\Controller\ProductController;
use App\V1\Controllers\PromotionProgramController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionProgramFlashSaleTransformer extends TransformerAbstract
{
    /**
     * @var $promotionPrograms
     */
    protected $promotionPrograms;
    protected $flash_sale;

    /**
     * ProductClientTransformer constructor.
     *
     * @param $promotionPrograms
     */
    public function __construct($promotionPrograms, $flash_sale)
    {
        $this->promotionPrograms = $promotionPrograms;
        $this->flash_sale        = $flash_sale;
    }

    public function transform(Product $product)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $product_gift = [];
        $category_gift = [];

        $PromotionsGiftAndIframe = (new PromotionProgram())->PromotionsGiftAndIframe($product);
        
        try {
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
                $percent_price = $product->price - $price;
                $percentage_price_old = round(($percent_price / $product->price) * 100);
            }

            $special            = null;
            $special_start_date = null;
            $special_end_date   = null;
            $special_formated   = null;
            $special_percentage = 0;


            $promotionPrograms = $this->promotionPrograms;
            // $promotionPrograms = (new PromotionHandle())->promotionApplyProduct($this->promotionPrograms, $product);
            $promotionPrice    = 0;
            if (!empty($promotionPrograms) && !$promotionPrograms->isEmpty()) {

                $type_promotion = array_pluck($promotionPrograms, 'promotion_type');
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
                    
                    $prod = array_pluck($promotion->productPromotion, 'product_id');
                    $search_prod = array_search($product->id, $prod);

                    if(!is_numeric($search_prod) ){
                            continue;
                    }
                  
                    $iframe_image_id = $promotion->iframe_image_id;
                    $iframe_image = $promotion->iframeImage->code ?? null;


                    // if ($promotion->discount_by == "product") {
                    //     $promotionPrice += (new PromotionHandle())->parsePriceByProducts($product->code, $price, $promotion);
                    // } else {
                    //     $promotionPrice += (new PromotionHandle())->parsePriceBySaleType($price, $promotion);;
                    // }

                    $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id,  $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);

                    // $prod = array_pluck($promotion->productPromotion, 'product_id');
                    // $search_prod = array_search($product->id, $prod);

                    // if (is_numeric($search_prod)) {

                    //         if ($promotion->discount_by == "product") {
                    //             if ($promotion->act_sale_type == 'percentage') {
                    //                 $promotionPrice = $price * ($promotion->productPromotion[$search_prod]->price / 100);
                    //             }
                    //             if ($promotion->act_sale_type != 'percentage') {
                    //                 $promotionPrice = $promotion->productPromotion[$search_prod]->price;
                    //             }
                    //         }
        
                    //         if ($promotion->discount_by != "product") {
                    //             if ($promotion->act_sale_type == 'percentage') {
                    //                 $promotionPrice = $price * ($promotion->act_price / 100);
                    //             }
                    //             if ($promotion->act_sale_type != 'percentage') {
                    //                 $promotionPrice = $promotion->act_price;
                    //             }
                    //         }
                        
                    // }

                    if ($key == 0) {
                        $special_start_date = !empty($promotion->start_date) ? date('d-m-Y', strtotime($promotion->start_date)) : null;
                        $special_end_date   = !empty($promotion->end_date) ? date('d-m-Y', strtotime($promotion->end_date)) : null;
                    }
                }

                $special          = $price - $promotionPrice;
                $special_formated = number_format($special) . "đ";
                if (isset($special)) {
                    $special_percentage = $price != 0 || !empty($price) ? round(($promotionPrice / $price) * 100) : 0;
//                    $special_percentage = round(($promotionPrice / $price) * 100);
                }
            }
            $start = Arr::get($product->enddateFS($product, $this->flash_sale), 'start_date', null);
            $end = Arr::get($product->enddateFS($product, $this->flash_sale), 'end_date', null);

            $order_sale = OrderDetail::model()
                ->join('orders', 'orders.id', 'order_details.order_id')
                ->whereRaw("order_details.created_at BETWEEN '$start' AND '$end'")
                ->where('orders.status', '!=', 'CANCELED')
                ->where('order_details.product_id', $product->id)
                ->groupBy('order_details.product_id')
                ->sum('order_details.qty');
            if ($order_sale > $product->qty_flash_sale) {
                $order_sale = $product->qty_flash_sale;
            }
            setlocale(LC_MONETARY, 'vi_VN');
            $productAttributes = (new ProductAttributeModel())->getListByProductId($product->id, true);
            $fileCode          = object_get($product, 'file.code', null);
            $rate              = $product->comments;

            if (empty($iframe_image_id) && empty($iframe_image)) {
                $iframe_image_id = Arr::get($product->promotionTagsAndIframe($product), 'iframe_image_id', null);
                $iframe_image = Arr::get($product->promotionTagsAndIframe($product), 'iframe_image', null);
            }

            $output            = [
                'id'                           => $product->id,
                'code'                         => $product->code,
                'name'                         => $product->name,
                'slug'                         => $product->slug,
                'url'                          => env('APP_URL') . "/product/{$product->slug}",
                'type'                         => $product->type,
                'tags'                         => $product->tags,
                'tax'                          => $product->tax,
                'promotion_tags'               => !empty(Arr::get($product->promotionTagsAndIframe($product), 'tags', null)) ? json_decode(Arr::get($product->promotionTagsAndIframe($product), 'tags', null)) : [],
                'star_rating'                  => 0,
                'short_description'            => $product->short_description,
                'description'                  => $product->description,
                'thumbnail_id'                 => $product->thumbnail,
                'thumbnail'                    => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode : null,
                'iframe_image_id'              => $iframe_image_id ?? null,
                'iframe_image'                 => !empty($iframe_image_id) ? env('GET_FILE_URL') . $iframe_image : null,
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
                'old_product_price'            => $product->price == $price ? 0 :  $product->price,
                'old_product_price_formatted'  => number_format($product->price) . "đ",
                'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
                'promotion_price'              => $promotionPrice,
                'promotion_price_formatted'    => number_format($promotionPrice) . "đ",
                'special'                      => $special,
                'special_formatted'            => $special_formated,
                'special_start_date'           => $special_start_date,
                'special_end_date'             => $special_end_date,
                'special_percentage'           => $special_percentage,
                'special_percentage_formatted' => $special_percentage . "%",
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
                'unit_id'                      => Arr::get($product->unit, 'id', null),
                'unit_name'                    => Arr::get($product->unit, 'name', null),
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
                'manufacturer_id'              => $product->manufacturer_id,
                'manufacturer_name'            => object_get($product, 'masterData.name'),
                'manufacturer_code'            => object_get($product, 'masterData.code'),
                'qty_out_min'                  => $product->qty_out_min,
                'custom_date_updated'          => !empty($product->custom_date_updated) ? date(
                    'd-m-Y',
                    strtotime($product->custom_date_updated)
                ) : null,
                'sold_count'                   => $sold_count = $product->sold_count ?? 0,
                'sold_count_formatted'         => format_number_in_k_notation($sold_count),
                'order_count'                  => $product->order_count ?? 0,
                'count_rate'                   => $product->count_rate,
                'version_name'                 => $product->version_name,
                'publish_status'               => $product->publish_status,
                'created_at'                   => date('d-m-Y', strtotime($product->created_at)),
                'updated_at'                   => date('d-m-Y', strtotime($product->updated_at)),
                'star'                         => $this->getStarRate($product->id),
                'len_rate'                     => (int)count($product->comments->toArray()),
                'flash_sale'                   => $this->flash_sale,
                'sort'                         => Arr::get($product->enddateFS($product, $this->flash_sale), 'sort', null),
                'end_date_flash_sale'          => Arr::get($product->enddateFS($product, $this->flash_sale), 'end_date', null),
                'qty_sale'                     => $order_sale ?? null,
                'qty_flash_sale'               => $product->qty_flash_sale,
                'qty_status'                   => "",
                'product_gift'                 => $PromotionsGiftAndIframe['product_gift'] ?? [],
            ];
            return $output;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    /**
     * Get gallery_images of product
     *
     * @param $ids
     * @return array|string
     */
    private function stringToImage($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $result = [];
        $images = File::model()->whereIn('id', explode(",", $ids))->select('id', 'code')->get();
        foreach ($images as $key => $image) {
            $result[$key]['id']  = $image->id;
            $result[$key]['url'] = env('UPLOAD_URL') . '/file/' . $image->code;
        }
        return $result;
    }

    /**
     * Get folder name image of product
     * @param $id
     * @param $fordel_path
     * @return string
     */
    //    public function getFolder($id, &$fordel_path)
    //    {
    //        $folder = Folder::model()->find($id);
    //        if (!empty($folder)) {
    //            $fordel_path = ($folder->folder_name . ',' . $fordel_path);
    //            if (!empty($folder->parent_id)) {
    //                $this->getFolder($folder->parent_id, $fordel_path);
    //            }
    //        }
    //        return $fordel_path;
    //    }

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

    //    private function getProduct($ids)
    //    {
    //        if (empty($ids)) {
    //            return [];
    //        }
    //        $product = Product::model()->whereIn('id', explode(",", $ids))->get()->toArray();
    //        $data    = [];
    //        foreach ($product as $key => $item) {
    //            $data[] = [
    //                'id'                       => $item['id'],
    //                'code'                     => $item['code'],
    //                'name'                     => $item['name'],
    //                'slug'                     => $item['slug'],
    //                'type'                     => $item['type'],
    //                'tags'                     => $item['tags'],
    //                'tax'                      => $item['tax'],
    //                'short_description'        => $item['short_description'],
    //                'description'              => $item['description'],
    //                'thumbnail_id'             => $item['thumbnail'],
    //                'thumbnail'                => !empty($this->getImageThumbnail($item['thumbnail'])) ? url('/v0') . "/img/" . 'uploads,' . $this->getImageThumbnail($item['thumbnail']) : null,
    //                'gallery_image_ids'        => $item['gallery_images'],
    //                'gallery_images'           => $this->stringToImage($item['gallery_images']),
    //                'category_ids'             => $item['category_ids'],
    //                'store_supermarket'        => $item['store_supermarket'],
    //                'category_supermarket_ids' => $item['category_supermarket_ids'],
    //                'categories'               => $this->getNameCategory($item['category_ids']),
    //                'price'                    => $item['price'],
    //                'sku'                      => $item['sku'],
    //                'upc'                      => $item['upc'],
    //                'qty'                      => $item['qty'],
    //                'length'                   => $item['length'],
    //                'width'                    => $item['width'],
    //                'height'                   => $item['height'],
    //                'length_class'             => $item['length_class'],
    //                'weight_class'             => $item['weight_class'],
    //                'status'                   => $item['status'],
    //                'order'                    => $item['order'],
    //                'view'                     => $item['view'],
    //                'store_id'                 => $item['store_id'],
    //                'manufacturer_id'          => $item['manufacturer_id'],
    //                'created_at'               => date('d-m-Y', strtotime($item['created_at'])),
    //                'updated_at'               => !empty($item['updated_at']) ? date('d-m-Y',
    //                    strtotime($item['updated_at'])) : null,
    //            ];
    //        }
    //        return $data;
    //    }
    //
    //    private function getImageThumbnail($id)
    //    {
    //        if (empty($id)) {
    //            return [];
    //        }
    //        $thumbnail = File::model()->select(['file_name'])->where('id', explode(",", $id))->get()->toArray();
    //        $thumbnail = array_pluck($thumbnail, 'file_name');
    //        $thumbnail = implode(', ', $thumbnail);
    //        return $thumbnail;
    //    }

    //    private function getImage($ids)
    //    {
    //        if (empty($ids)) {
    //            return [];
    //        }
    //        $images = File::model()->select(['id as file_id', 'url'])->whereIn('id', explode(",", $ids))->get();
    //        return $images->toArray();
    //    }

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
}
