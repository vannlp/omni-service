<?php

namespace App\V1\Transformers\ProductVariant;

use App\ProductVariant;
use League\Fractal\TransformerAbstract;

class ProductVariantTransformer extends TransformerAbstract
{
    public function transform(ProductVariant $productVariant)
    {
        return [
            'id'                     => $productVariant->id,
            'product_id'             => $productVariant->product_id,
            'product_attributes'     => $productVariant->product_attributes,
            'product_code'           => $productVariant->product_code,
            'price'                  => $productVariant->price,
            'image'                  => $productVariant->image,
            'inventory'              => $productVariant->inventory,
            'is_active'              => $productVariant->is_active,
            'deleted'                => $productVariant->deleted,
            'created_at'             => $productVariant->created_at,
            'updated_at'             => $productVariant->updated_at,
            'product_attribute_name' => $productVariant->product_attribute_name,
            'promotions'             => array_map(function ($item) {
                return [
                    'id'                 => $item['id'],
                    'product_variant_id' => $item['product_variant_id'],
                    'user_group_id'      => $item['user_group_id'],
                    'order'              => $item['order'],
                    'priority'           => $item['priority'],
                    'price'              => $item['price'],
                    'start_date'         => !empty($item['start_date']) ? date('d/m/Y', strtotime($item['start_date'])) : null,
                    'end_date'           => !empty($item['end_date']) ? date('d/m/Y', strtotime($item['end_date'])) : null,
                    'is_default'         => $item['is_default'],
                    'is_active'          => $item['is_active'],
                    'created_at'         => $item['created_at'],
                    'updated_at'         => $item['updated_at']
                ];
            }, $productVariant->promotions->toArray())
        ];
    }
}