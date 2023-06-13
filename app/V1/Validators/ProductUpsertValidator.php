<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Product;
use App\Supports\Message;
use Illuminate\Http\Request;

/**
 * Class ProductUpsertValidator
 *
 * @package App\V1\CMS\Validators
 */
class ProductUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            // Tab Common
            'id'                             => 'required|exists:products,id,deleted_at,NULL',
            'code'                           => 'required|max:50|unique_update_delete:products',
            'name'                           => 'required|max:100',
            'tax'                            => 'numeric',
            //            'area_id'                        => 'required|exists:areas,id,deleted_at,NULL',
            'brand_id'                       => 'nullable|exists:brands,id,deleted_at,NULL',
            'age'                            => 'nullable|exists:ages,id,deleted_at,NULL',
            'status'                         => 'integer|max:1',
            'short_description'              => 'required',
            'description'                    => 'required',
            'tag'                            => 'max:100',
            'type'                           => 'required',
            'shop_id'                        => 'exists:stores,id,deleted_at,NULL',
            'specification_id'               => 'required|exists:specifications,id,deleted_at,NULL',
            'unit_id'                        => 'required|exists:units,id,deleted_at,NULL',
            'point'                          => 'nullable|numeric',
            'category_ids'                   => 'required|array',

            // Tab Images
            'thumbnail'                      => 'nullable:max:200',
            'gallery_images'                 => 'nullable:max:200',

            // Tab Data
            //            'price'                          => 'nullable|numeric|min:0|not_in:0',
            'price'                          => 'nullable|numeric|min:0',
            'sku'                            => 'max:100',
            'upc'                            => 'max:100',
            'qty'                            => 'numeric',
            'qty_out_min'                    => 'numeric',

            // Tab Store
            'stores'                         => "nullable|array",
            'stores.*.store_id'              => 'required|exists:stores,id,deleted_at,NULL',

            // Tab Option
            'options'                        => "nullable|array",
            'options.*.id'                   => 'required|exists:catalog_options,id,deleted_at,NULL',
            'options.*.values'               => 'nullable|array',
            'options.*.values.*.unit_id'     => 'nullable|exists:units,id,deleted_at,NULL',
            'options.*.values.*.price'       => 'nullable|numeric',

            // Tab Reward
            'reward_points'                  => "nullable|array",
            'reward_points.*.user_group_id'  => 'required|exists:user_groups,id,deleted_at,NULL',
            'reward_points.*.point'          => 'nullable|numeric',

            // Tab LINK
            'related_ids'                    => 'max:200',

            // Tab Properties
            'width'                          => 'numeric',
            'height'                         => 'numeric',
            'order'                          => 'numeric',
            'length'                         => 'numeric',
            'length_class'                   => 'in:' . PRODUCT_LENGTH_CLASS_CM . "," . PRODUCT_LENGTH_CLASS_IN . "," . PRODUCT_LENGTH_CLASS_MM,
            'weight'                         => 'required|numeric',
            'weight_class'                   => 'required|in:' . PRODUCT_WEIGHT_CLASS_GR . "," . PRODUCT_WEIGHT_CLASS_KG,

            // Tab SEO

            // Tab Discount
            'discounts'                      => "nullable|array",
            'discounts.*.user_group_id'      => 'required|exists:user_groups,id,deleted_at,NULL',
            'discounts.*.price'              => 'nullable|numeric',
            'discounts.*.discount_unit_type' => "required|in:" . PRODUCT_UNIT_TYPE_PERCENT . "," . PRODUCT_UNIT_TYPE_MONEY,

            // Tab Promotion
            'promotions'                     => "nullable|array",
            'promotions.*.user_group_id'     => 'required|exists:user_groups,id,deleted_at,NULL',
            'promotions.*.priority'          => 'nullable|numeric',
            'promotions.*.price'             => 'nullable|numeric',
            'promotions.*.start_date'        => 'nullable|date_format:Y-m-d',
            'promotions.*.end_date'          => 'nullable|date_format:Y-m-d',

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
        ];
    }

    protected function attributes()
    {
        return [
            'name'              => Message::get("name"),
            'category_ids'      => Message::get("category_ids"),
            'code'              => Message::get("code"),
            'tag'               => Message::get("tag"),
            'type_id'           => Message::get("type_id"),
            'short_description' => Message::get("short_description"),
            'description'       => Message::get("description"),
            'thumbnail'         => Message::get("thumbnail"),
            'gallery_images'    => Message::get("gallery_images"),
            'sku'               => Message::get("sku"),
            'shop_id'           => Message::get("shop_id"),
            'specification_id'  => Message::get("specification_id"),
            'upc'               => Message::get("upc"),
            'qty_out_min'       => Message::get("qty_out_min"),
            'length_class'      => Message::get("length_class"),
            'tax'               => Message::get("tax"),
            'area_id'           => Message::get("area_id"),
            'qty'               => Message::get("qty"),
            'length'            => Message::get("length"),
            'width'             => Message::get("width"),
            'height'            => Message::get("height"),
            'weight_class'      => Message::get("weight_class"),
            'weight'            => Message::get("weight"),
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
