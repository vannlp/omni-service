<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\Product;

use App\Category;
use App\File;
use App\Folder;
use App\Image;
use App\Product;
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
class ProductClientListTransformer extends TransformerAbstract
{
    public function transform(Product $product)
    {
        try {
            $folder_path = object_get($product, 'file.folder.folder_path');
            if (!empty($folder_path)) {
                $folder_path = str_replace("/", ",", $folder_path);
            } else {
                $folder_path = "uploads";
            }
            $folder_path = url('/v0') . "/img/" . $folder_path;
            $now         = date('Y-m-d H:i:s', time());
            $promotion   = ProductPromotion::model()
                ->where('product_id', $product->id)
                ->where('start_date', '<=', $now)
                ->where('end_date', '>=', $now)
                ->where('user_group_id', TM::getCurrentGroupId())
                ->first();
            $output      = [
                'id'                => $product->id,
                'name'              => $product->name,
                'description'       => $product->description,
                'short_description' => $product->short_description,
                'thumbnail_id'      => $product->thumbnail,
                'thumbnail'         => !empty($folder_path) ? $folder_path . ',' . object_get($product,
                        'file.file_name', null) : null,
                'gallery_image_ids' => $product->gallery_images,
                'gallery_images'    => $this->stringToImage($product->gallery_images),
                'price_formatted'   => number_format($product->price) . "đ",
                'price'             => $product->price,
                'warehouse_id'      => Arr::get($product, 'warehouse.warehouse_id', null),
                'warehouse_code'    => Arr::get($product, 'warehouse.warehouse_code', null),
                'weight_class'      => $product->weight_class,
                'weight'            => $product->weight,
                'versions'          => array_map(function ($version) {
                    return [
                        'id'                   => $version['id'],
                        'version_product_id'   => $version['version_product_id'],
                        'version_product_code' => $version['product_version']['code'] ?? null,
                        'version_product_name' => $version['product_version']['name'] ?? null,
                        'version'              => $version['version'],
                        'price'                => $version['price'],
                    ];
                }, $product->versions->toArray()),
                'slug'              => $product->slug,
                'url'               => env('APP_URL') . "/product/{$product->slug}",
                'categories'        => $this->getNameCategory($product->category_ids),
                'brand_id'          => Arr::get($product, 'brand_id', null),
                'brand_name'        => Arr::get($product, 'brand.name', null),
                'is_feature_name'   => PRODUCT_IS_FEATURE[$product->is_featured],
                'tags'              => $product->tags,
                'promotions'        => array_map(function ($promotion) {
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

                'discounts'      => array_map(function ($discount) {
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
                'status'         => $product->status,
                'count_rate'     => $product->count_rate,
                'publish_status' => $product->publish_status,
                'barcode'        => $product->barcode,
                'stores'         => $product->stores->map(function ($item) {
                    return $item->only(['id', 'code', 'name']);
                }),
            ];
            return $output;
        }
        catch (\Exception $ex) {
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
        $images = File::model()->whereIn('id', explode(",", $ids))->get();
        foreach ($images as $key => $image) {
            $data               = '';
            $result[$key]['id'] = $image->id;
            if (!empty($image->folder_id)) {
                $result[$key]['url'] = url('/v0') . "/img/" . 'uploads,' . $this->getFolder($image->folder_id,
                        $data) . $image->file_name;
            } else {
                $result[$key]['url'] = url('/v0') . "/img/" . 'uploads,' . $image->file_name;
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

}
