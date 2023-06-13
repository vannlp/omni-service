<?php

namespace App\V1\Transformers\Shop;

use App\Category;
use App\File;
use App\Folder;
use App\Product;
use App\ProductDiscount;
use App\ProductPromotion;
use App\Store;
use App\Supports\TM_Error;
use App\TM;
use App\UserGroup;
use App\V1\Models\ProductAttributeModel;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class ProductDetailTransformer extends TransformerAbstract
{
    /**
     * @var $promotionProgram
     */
    protected $promotionProgram;

    /**
     * ProductDetailTransformer constructor.
     * @param $promotionProgram
     */
    public function __construct($promotionProgram)
    {
        $this->promotionProgram = $promotionProgram;
    }

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
            $realPrice   = $product->price_down;
            if ($product->price_down) {
                $downFrom = strtotime($product->down_from);
                $downTo   = strtotime($product->down_to);
                $now      = time();
                if ($now < $downFrom || $now > $downTo) {
                    $realPrice = 0;
                }
            }

            $price = Arr::get($product->priceDetail($product), 'price', $product->price);

            if($price < $product->price){
                $percent_price = $product->price - $price;
                $percentage_price_old = round(($percent_price / $product->price) * 100);
            }

            $special            = null;
            $special_start_date = null;
            $special_end_date   = null;
            $special_formated   = null;
            $special_percentage = 0;

            if (!empty($this->promotionProgram)) {
                $promotionProgram = $this->promotionProgram;
                $flag             = true;
                if (!empty($promotionProgram->categoryIds)) {
                    $productCategoryIds = explode(",", $product->category_ids);
                    foreach ($promotionProgram->categoryIds as $id) {
                        if (in_array((int)$id, $productCategoryIds)) {
                            $flag = false;
                            break;
                        }
                    }
                }

                if ($flag == true) {
                    if ($promotionProgram->act_sale_type == 'percentage') {
                        $special            = $price * ((100 - $promotionProgram->act_price) / 100);
                        $special_percentage = $promotionProgram->act_price;
                    } else {
                        $special = $price - $promotionProgram->act_price;
                    }

                    $special_formated = number_format($special) . "đ";

                    $special_start_date = !empty($promotionProgram->start_date) ? date('d-m-Y', strtotime($promotionProgram->start_date)) : null;
                    $special_end_date   = !empty($promotionProgram->end_date) ? date('d-m-Y', strtotime($promotionProgram->end_date)) : null;
                }
            }

            setlocale(LC_MONETARY, 'vi_VN');

            $productAttributes = (new ProductAttributeModel())->getListByProductId($product->id, true);
            $output            = [
                'id'                       => $product->id,
                'code'                     => $product->code,
                'name'                     => $product->name,
                'slug'                     => $product->slug,
                'url'                      => env('APP_URL') . "/product/{$product->slug}",
                'type'                     => $product->type,
                'tags'                     => $product->tags,
                'tax'                      => $product->tax,
                'short_description'        => $product->short_description,
                'description'              => $product->description,
                'thumbnail_id'             => $product->thumbnail,
                'thumbnail'                => !empty($folder_path) ? $folder_path . ',' . object_get($product, 'thumbnailFile.file_name', null) : null,
                'gallery_image_ids'        => $product->gallery_images,
                'gallery_images'           => $this->stringToImage($product->gallery_images),
                'category_ids'             => $product->category_ids,
                'categories'               => $this->getNameCategory($product->category_ids),
                'favorites_count'          => $product->favorites_count,
                'brand'                    => $product->brand,
                'area'                     => $product->area,
                'variants'                 => $product->variants,
                'productAttributes'        => $productAttributes,
                'price'                    => $price,
                'price_formatted'          => number_format($price) . "đ",
                'original_price'           => $price,
                'original_price_formatted' => number_format($price) . "đ",
                'old_product_price'            => $product->price == $price ? 0 :  $product->price,
                'old_product_price_formatted'  => number_format($product->price) . "đ",
                'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
                'special'                  => $special,
                'special_formatted'        => $special_formated,
                'special_start_date'       => $special_start_date,
                'special_end_date'         => $special_end_date,
                'special_percentage'       => $special_percentage,
                'real_price'               => $realPrice,
                'price_down'               => $product->price_down,
                'down_rate'                => $product->price != 0 ? $product->price_down * 100 / $product->price : 0,
                'down_from'                => !empty($product->down_from) ? date('d-m-Y H:i:s',
                    strtotime($product->down_from)) : null,
                'down_to'                  => !empty($product->down_to) ? date('d-m-Y H:i:s',
                    strtotime($product->down_to)) : null,
                'handling_object'          => $product->handling_object,
                'personal_object'          => $product->personal_object,
                'enterprise_object'        => $product->enterprise_object,
                'sku'                      => $product->sku,
                'upc'                      => $product->upc,
                'qty'                      => $product->qty,
                'length'                   => $product->length,
                'width'                    => $product->width,
                'height'                   => $product->height,
                'length_class'             => $product->length_class,
                'weight_class'             => $product->weight_class,
                'weight'                   => $product->weight,
                'status'                   => $product->status,
                'order'                    => $product->order,
                'view'                     => $product->view,
                'store_id'                 => $product->store_id,
                'store_name'               => Arr::get($product->storeOrigin, 'name'),
                'store_origin'             => [
                    'id'   => Arr::get($product->storeOrigin, 'id'),
                    'name' => Arr::get($product->storeOrigin, 'name')
                ],
                'stores'                   => $product->stores->map(function ($item) {
                    return $item->only(['id', 'name']);
                }),
                'is_featured'              => $product->is_featured,
                'related_ids'              => $product->related_ids,
                'combo_liked'              => $product->combo_liked,
                'exclusive_premium'        => $product->exclusive_premium,
                //                'related_products'         => $this->getProduct($product->related_ids),
                'manufacturer_id'          => $product->manufacturer_id,
                'manufacturer_name'        => object_get($product, 'masterData.name'),
                'manufacturer_code'        => object_get($product, 'masterData.code'),
                'qty_out_min'              => $product->qty_out_min,
                'custom_date_updated'      => !empty($product->custom_date_updated) ? date('d-m-Y',
                    strtotime($product->custom_date_updated)) : null,
                'sold_count'               => $sold_count = $product->sold_count ?? 0,
                'sold_count_formatted'     => format_number_in_k_notation($sold_count),
                'order_count'              => $product->order_count ?? 0,
                'version_name'             => $product->version_name,

                'publish_status' => $product->publish_status,
                'created_at'     => date('d-m-Y', strtotime($product->created_at)),
                'updated_at'     => !empty($product->updated_at) ? date('d-m-Y', strtotime($product->updated_at)) : null
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
                'updated_at'        => !empty($item['updated_at']) ? date('d-m-Y',
                    strtotime($item['updated_at'])) : null,
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
}
