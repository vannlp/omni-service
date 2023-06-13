<?php
/**
 * Date: 2/28/2019
 * Time: 11:12 AM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            // Tab Common
            'id'                             => 'exists:products,id,deleted_at,NULL',
            'code'                           => 'required|string|max:50|unique:products,code',
            'name'                           => 'required|max:100',
            'tax'                            => 'numeric',
            //            'area_id'                        => 'required|exists:areas,id,deleted_at,NULL',
            'brand_id'                       => 'nullable|exists:brands,id,deleted_at,NULL',
            'age'                            => 'nullable|exists:ages,id,deleted_at,NULL',
            'status'                         => 'integer|max:1',
            'short_description'              => 'required',
            'description'                    => 'required',
            'category_ids'                   => 'required|array',
            'tag'                            => 'max:100',
            'type'                           => 'required|in:PRODUCT,SERVICE',
            'unit_id'                        => 'nullable|exists:units,id,deleted_at,NULL',
            'point'                          => 'nullable|numeric',

            // Tab Images
            'thumbnail'                      => 'nullable:max:200',
            'gallery_images'                 => 'nullable:max:200',

            // Tab Option
            'options'                        => "nullable|array",
            'options.*.id'                   => 'required|exists:catalog_options,id,deleted_at,NULL',
            'options.*.values'               => 'nullable|array',
            'options.*.values.*.unit_id'     => 'nullable|exists:units,id,deleted_at,NULL',
            'options.*.values.*.price'       => 'nullable|numeric',

            // Tab Data
            //            'price'                          => 'nullable|numeric|min:0|not_in:0',
            'price'                          => 'nullable|numeric|min:0',
            'sku'                            => 'max:100',
            'upc'                            => 'max:100',
            'qty'                            => 'numeric',
            'qty_out_min'                    => 'numeric',

            // Tab LINK
            'related_ids'                    => 'max:200',

            // Tab Properties
            'width'                          => 'numeric',
            'height'                         => 'numeric',
            'order'                          => 'numeric',
            'shop_id'                        => 'exists:stores,id,deleted_at,NULL',
            'specification_id'               => 'required|exists:specifications,id,deleted_at,NULL',
            'length'                         => 'numeric',
            'length_class'                   => 'nullable|in:' . PRODUCT_LENGTH_CLASS_CM . "," . PRODUCT_LENGTH_CLASS_IN . "," . PRODUCT_LENGTH_CLASS_MM,
            'weight'                         => 'numeric',
            'weight_class'                   => 'required|in:' . PRODUCT_WEIGHT_CLASS_GR . "," . PRODUCT_WEIGHT_CLASS_KG,

            // Tab SEO
            //            'slug'                           => 'required',

            // Tab Store
            'stores'                         => "nullable|array",
            'stores.*.store_id'              => 'required|exists:stores,id,deleted_at,NULL',

            // Tab Discount
            'discounts'                      => "nullable|array",
            'discounts.*.user_group_id'      => 'required|exists:user_groups,id,deleted_at,NULL',
            'discounts.*.price'              => 'nullable|numeric',
            'discounts.*.discount_unit_type' => "required|in:" . PRODUCT_UNIT_TYPE_PERCENT . "," . PRODUCT_UNIT_TYPE_MONEY,

            // Tab Promotion
            'promotions'                     => "nullable|array",
            'promotions.*.user_group_id'     => 'required|exists:user_groups,id,deleted_at,NULL',
            'promotions.*.priority'          => 'required|numeric',
            'promotions.*.price'             => 'nullable|numeric',
            'promotions.*.start_date'        => 'nullable|date_format:Y-m-d',
            'promotions.*.end_date'          => 'nullable|date_format:Y-m-d',

            // Tab Reward
            'reward_points'                  => "nullable|array",
            'reward_points.*.user_group_id'  => 'required|exists:user_groups,id,deleted_at,NULL',
            'reward_points.*.point'          => 'nullable|numeric',

            // Tab Version
            'versions'                       => "nullable|array",
            'versions.*.version_product_id'  => 'required|exists:products,id,deleted_at,NULL',
            'versions.*.version'             => 'required',
            'versions.*.price'               => 'nullable|numeric',

            'personal_object'   => 'nullable|max:5',
            'enterprise_object' => 'nullable|max:5',
            'view'              => 'numeric',
            'store_id'          => 'max:200',
            'is_featured'       => 'integer|max:1',
            'is_active'         => 'integer|max:1',
            // 'barcode'           => 'unique_create_delete:products,barcode',
        ];
    }

    protected function attributes()
    {
        return [
            'name'              => Message::get("name"),
            'code'              => Message::get("code"),
            'category_ids'      => Message::get("category_ids"),
            'tag'               => Message::get("tag"),
            'type_id'           => Message::get("type_id"),
            'shop_id'           => Message::get("shop_id"),
            'specification_id'  => Message::get("specification_id"),
            'short_description' => Message::get("short_description"),
            'description'       => Message::get("description"),
            'thumbnail'         => Message::get("thumbnail"),
            'gallery_images'    => Message::get("gallery_images"),
            'sku'               => Message::get("sku"),
            'qty_out_min'       => Message::get("qty_out_min"),
            'upc'               => Message::get("upc"),
            'length_class'      => Message::get("length_class"),
            'weight'            => Message::get("weight"),
            'tax'               => Message::get("tax"),
            'area_id'           => Message::get("area_id"),
            'qty'               => Message::get("qty"),
            'length'            => Message::get("length"),
            'width'             => Message::get("width"),
            'height'            => Message::get("height"),
            'weight_class'      => Message::get("weight_class"),
            'order'             => Message::get("order"),
            'is_active'         => Message::get("is_active"),
            'status'            => Message::get("status"),
            'view'              => Message::get("view"),
            'store_id'          => Message::get("store_id"),
            'is_featured'       => Message::get("is_featured"),
            'related_ids'       => Message::get("related_ids"),
            'price'             => Message::get("price"),
            'personal_object'   => Message::get("personal_object"),
            'enterprise_object' => Message::get("enterprise_object"),
        ];
    }
}