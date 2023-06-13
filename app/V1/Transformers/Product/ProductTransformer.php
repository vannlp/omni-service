<?php

/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\Product;

use App\ProductComment;
use App\Supports\DataUser;
use App\Category;
use App\File;
use App\Folder;
use App\Image;
use App\Product;
use App\Attribute;
use App\ProductPromotion;
use App\Store;
use App\Supports\TM_Error;
use App\TM;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;
use function GuzzleHttp\Psr7\str;

/**
 * Class ProductTransformer
 *
 * @package App\V1\CMS\Transformers
 */
class ProductTransformer extends TransformerAbstract
{
    public function transform(Product $product)
    {
        try {
            $category_ids = explode(',', $product->category_ids);
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
            $website_ids = !empty($product->website_ids) ? explode(",", $product->website_ids) : null;
            $realPrice   = $product->price_down;
            if ($product->price_down) {
                $downFrom = strtotime($product->down_from);
                $downTo   = strtotime($product->down_to);
                $now      = time();
                if ($now < $downFrom || $now > $downTo) {
                    $realPrice = 0;
                }
            }
            $now                = date('Y-m-d H:i:s', time());
            $promotion          = ProductPromotion::model()
                ->where('product_id', $product->id)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where('user_group_id', TM::getCurrentGroupId())
                ->first();
            $special            = !empty($promotion) ? $promotion->price : null;
            $special_end_date   = !empty($promotion) ? date('d-m-Y', strtotime($promotion->end_date)) : null;
            $special_start_date = !empty($promotion) ? date('d-m-Y', strtotime($promotion->start_date)) : null;
            $special_formated   = !empty($promotion) ? number_format($special) . "đ" : null;
            $fileCode           = object_get($product, 'file.code', null);

            $variant = $product->variants;
            $output  = [
                'id'                       => $product->id,
                'code'                     => $product->code,
                'name'                     => $product->name,
                'slug'                     => $product->slug,
                'url'                      => env('APP_URL') . "/product/{$product->slug}",
                'type'                     => $product->type,
                'tags'                     => $product->tags,
                'tax'                      => $product->tax,
                'qr_scan'                  => $product->qr_scan,
                'attribute_info'           => !empty($product->attribute_info) ? json_decode($product->attribute_info, true) : [],
                'sale_area'                => !empty($product->sale_area) ? json_decode($product->sale_area, true) : null,
                'meta_title'               => $product->meta_title,
                'meta_description'         => $product->meta_description,
                'meta_keyword'             => $product->meta_keyword,
                'meta_robot'               => $product->meta_robot,
                'variant'                  => array_map(function ($variant) {
                    return [
                        'id'                 => $variant['id'],
                        'product_id'         => $variant['product_id'],
                        'product_attributes' => $this->getAttName($variant['product_attributes']),
                        // 'product_attributes_name' => $this->getAttrName($variant['product_attributes']),
                        'product_code'       => $variant['product_code'],
                        'price'              => $variant['price'],
                        'image'              => $variant['image'],
                        'inventory'          => $variant['inventory'],
                    ];
                }, $variant->toArray()),
                'brand_id'                 => Arr::get($product, 'brand_id', null),
                'property_variant_ids'     => Arr::get($product, 'property_variant_ids', null),
                'property_variant'         => $product->productPropertyVariants->map(function ($item) {
                    return $item->only(['id', 'code', 'name']);
                }),
                'brand_name'               => Arr::get($product, 'brand.name', null),
                'child_brand_id'           => Arr::get($product, 'child_brand_id', null),
                'child_brand_name'         => Arr::get($product, 'child_brand_name', null),
                'age_id'                   => Arr::get($product, 'age_id', null),
                'age_name'                 => Arr::get($product, 'getAge.name', null),
                'capacity'                 => Arr::get($product, 'capacity', null),
                'cadcode'                  => Arr::get($product, 'cadcode', null),
                'manufacture_id'           => Arr::get($product, 'manufacture_id', null),
                'manufacture_name'         => Arr::get($product, 'getManufacture.name', null),
                'area_id'                  => Arr::get($product, 'area_id', null),
                'area_name'                => Arr::get($product, 'area.name', null),
                'shop_id'                  => Arr::get($product, 'shop_id', null),
                'shop_name'                => Arr::get($product, 'shop.name', null),
                'short_description'        => $product->short_description,
                'description'              => $product->description,
                'thumbnail_id'             => $product->thumbnail,
                'thumbnail'                => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                'gallery_image_ids'        => $product->gallery_images,
                'gallery_images'           => $this->stringToImage($product->gallery_images),
                'salient_feature_ids'      => $product->salient_features,
                'salient_features'         => $this->stringToImage($product->salient_features),
                'category_ids'             => !empty($product->category_ids) ? explode(",", $product->category_ids) : [],
                'categories'               => $this->getNameCategory($product->category_ids),
                'store_supermarket'        => $product->store_supermarket,
                'category_supermarket_ids' => $product->category_supermarket_ids,
                'price'                    => $product->price,
                'price_formatted'          => number_format($product->price) . "đ",
                'original_price'           => $product->price,
                'original_price_formatted' => number_format($product->price) . "đ",
                'special'                  => $special,
                'special_formatted'        => $special_formated,
                'special_start_date'       => $special_start_date,
                'special_end_date'         => $special_end_date,
                'website_ids'              => $website_ids ?? null,
                'real_price'               => $realPrice,
                'price_down'               => $product->price_down,
                'down_rate'                => $product->price > 0 ? $product->price_down * 100 / $product->price : 0,
                'down_from'                => !empty($product->down_from) ? date(
                    'd-m-Y H:i:s',
                    strtotime($product->down_from)
                ) : null,
                'down_to'                  => !empty($product->down_to) ? date(
                    'd-m-Y H:i:s',
                    strtotime($product->down_to)
                ) : null,
                'expiry_date'              => !empty($product->expiry_date) ? $product->expiry_date : null,
                'handling_object'          => $product->handling_object,
                'personal_object'          => $product->personal_object,
                'enterprise_object'        => $product->enterprise_object,
                'sku'                      => $product->sku,
                'upc'                      => $product->upc,
                'qty'                      => Arr::get($product->warehouse, 'quantity', 0),
                'length'                   => $product->length,
                'width'                    => $product->width,
                'height'                   => $product->height,
                'length_class'             => $product->length_class,
                'weight_class'             => $product->weight_class,
                'weight'                   => $product->weight,
                'status'                   => $product->status,
                'order'                    => $product->order,
                'sort_order'               => $product->sort_order,
                'view'                     => $product->view,
                'store_id'                 => $product->store_id,
                'is_featured'              => $product->is_featured,
                'is_cool'                  => $product->is_cool,
                'is_feature_name'          => PRODUCT_IS_FEATURE[$product->is_featured],
                'related_ids'              => $product->related_ids,
                'combo_liked'              => $product->combo_liked,
                'zalo_synced'              => $product->sync_zalo,
                'exclusive_premium'        => $product->exclusive_premium,
                'related_products'         => $this->getProduct($product->related_ids),
                'qty_out_min'              => $product->qty_out_min,
                'custom_date_updated'      => !empty($product->custom_date_updated) ? date(
                    'd-m-Y',
                    strtotime($product->custom_date_updated)
                ) : null,
                'version_name'             => $product->version_name,
                'point'                    => $product->point,

                'unit_id'   => $product->unit_id,
                'unit_code' => object_get($product, 'getUnit.code'),
                'unit_name' => object_get($product, 'getUnit.name'),

                'stores' => $product->stores->map(function ($item) {
                    return $item->only(['id', 'code', 'name']);
                }),

                'options'    => array_map(function ($option) {
                    return [
                        'id'     => $option['id'],
                        'code'   => array_get($option, 'option.code'),
                        'name'   => array_get($option, 'option.name'),
                        'type'   => array_get($option, 'option.type'),
                        'values' => $option['values'] ? json_decode($option['values'], true) : [],
                    ];
                }, $product->options->toArray()),
                'promotions' => array_map(function ($promotion) {
                    return [
                        'id'              => $promotion['id'],
                        'user_group_id'   => $promotion['user_group_id'],
                        'user_group_code' => $promotion['user_group']['code'] ?? null,
                        'user_group_name' => $promotion['user_group']['name'] ?? null,
                        'priority'        => $promotion['priority'],
                        'price'           => $promotion['price'],
                        'start_date'      => $promotion['start_date'],
                        'end_date'        => $promotion['end_date'],
                        'is_default'      => $promotion['is_default'],
                    ];
                }, $product->promotions->toArray()),

                'discounts' => array_map(function ($discount) {
                    return [
                        'id'                 => $discount['id'],
                        'user_group_id'      => $discount['user_group_id'],
                        'user_group_code'    => $discount['user_group']['code'] ?? null,
                        'user_group_name'    => $discount['user_group']['name'] ?? null,
                        'code'               => $discount['code'],
                        'price'              => $discount['price'],
                        'discount_unit_type' => $discount['discount_unit_type'],
                    ];
                }, $product->discounts->toArray()),

                'reward_points' => array_map(function ($reward) {
                    return [
                        'user_group_id'   => $reward['user_group_id'],
                        'user_group_code' => $reward['user_group']['code'] ?? null,
                        'user_group_name' => $reward['user_group']['name'] ?? null,
                        'point'           => $reward['point'],
                    ];
                }, $product->rewardPoints->toArray()),

                'versions'              => array_map(function ($version) {
                    return [
                        'id'                   => $version['id'],
                        'version_product_id'   => $version['version_product_id'],
                        'version_product_code' => $version['product_version']['code'] ?? null,
                        'version_product_name' => $version['product_version']['name'] ?? null,
                        'version'              => $version['version'],
                        'price'                => $version['price'],
                    ];
                }, $product->versions->toArray()),
                'order_count'           => $product->order_count,
                'property_variant_root' => json_decode($product->property_variant_root) ?? [],
                'group_code'            => json_decode($product->group_code) ?? [],
                'gift_item'             => !empty($item_gift) ? array_unique($item_gift, SORT_REGULAR) : [],
                'sold_count'            => $product->sold_count,
                'count_rate'            => $product->count_rate,
                'publish_status'        => $product->publish_status,
                'specification_id'      => $product->specification_id,
                'specification_value'   => object_get($product, 'specification.value', null),
                'publish_status_name'   => !empty($product->publish_status) ? PUBLISH_STATUS_NAME[$product->publish_status] : null,
                'created_at'            => date('d-m-Y', strtotime($product->created_at)),
                'created_by'            => object_get($product, 'createdBy.profile.full_name'),
                'updated_at'            => !empty($product->updated_at) ? date(
                    'd-m-Y',
                    strtotime($product->updated_at)
                ) : null,
                'updated_by'            => object_get($product, 'updatedBy.profile.full_name'),
                'star'                  => $this->getStarRate($product->id),
                'cat'                   => $product->cat,
                'subcat'                => $product->subcat,
                'division'              => $product->division,
                'source'                => $product->source,
                'cad_code_brandy'       => $product->cad_code_brandy,
                'cad_code_subcat'       => $product->cad_code_subcat,
                'cad_code_brand'        => $product->cad_code_brand,
                'packing'               => $product->packing,
                'sku_standard'          => $product->sku_standard,
                'sku_name'              => $product->sku_name,
                'p_type'                => $product->p_type,
                'p_attribute'           => $product->p_attribute,
                'p_variant'             => $product->p_variant,
                'p_sku'                 => $product->p_sku,
                'barcode'               => $product->barcode,
                'is_combo'              => $product->is_combo,
                'is_odd'                => $product->is_odd,
                'combo_code_from'       => $product->combo_code_from,
                'combo_name_from'       => $product->combo_name_from,
                'combo_specification_from'       => $product->combo_specification_from,
                'is_combo_multi'        => $product->is_combo_multi,
                'product_combo'         => $product->product_combo,
            ];
            return $output;
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
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
                // $result[$key]['url'] = env('UPLOAD_URL') . '/file/' . $value;
            }
        }
        return $result;
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

    private function getAttName($attIds)
    {
        if (empty($attIds)) {
            return null;
        }
        $result = Attribute::model()->whereIn('id', $attIds)->get();
        return array_map(function ($variant) {
            return [
                'id'   => $variant['id'],
                'name' => $variant['name'],
            ];
        }, $result->toArray());
    }

    private function getProduct($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $product = Product::model()->whereIn('id', explode(",", $ids))->get()->toArray();
        $data    = [];
        foreach ($product as $key => $item) {
            $file     = File::find($item['thumbnail']);
            $fileCode = Arr::get($file, 'code', null);
            $data[]   = [
                'id'                       => $item['id'],
                'code'                     => $item['code'],
                'name'                     => $item['name'],
                'slug'                     => $item['slug'],
                'type'                     => $item['type'],
                'tags'                     => $item['tags'],
                'tax'                      => $item['tax'],
                'short_description'        => $item['short_description'],
                'description'              => $item['description'],
                'thumbnail_id'             => $item['thumbnail'],
                'thumbnail'                => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode : null,
                'gallery_image_ids'        => $item['gallery_images'],
                'gallery_images'           => $this->stringToImage($item['gallery_images']),
                'category_ids'             => $item['category_ids'],
                'categories'               => $this->getNameCategory($item['category_ids']),
                'store_supermarket'        => $item['store_supermarket'],
                'category_supermarket_ids' => $item['category_supermarket_ids'],
                'price'                    => $item['price'],
                'sku'                      => $item['sku'],
                'upc'                      => $item['upc'],
                'qty'                      => $item['qty'],
                'length'                   => $item['length'],
                'width'                    => $item['width'],
                'height'                   => $item['height'],
                'length_class'             => $item['length_class'],
                'weight_class'             => $item['weight_class'],
                'status'                   => $item['status'],
                'order'                    => $item['order'],
                'view'                     => $item['view'],
                'store_id'                 => $item['store_id'],
                'manufacture_id'           => $item['manufacture_id'],
                'created_at'               => date('d-m-Y', strtotime($item['created_at'])),
                'updated_at'               => !empty($item['updated_at']) ? date(
                    'd-m-Y',
                    strtotime($item['updated_at'])
                ) : null,
            ];
        }
        return $data;
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
