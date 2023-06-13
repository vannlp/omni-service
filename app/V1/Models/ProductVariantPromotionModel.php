<?php

namespace App\V1\Models;

use App\ProductVariantPromotion;
use Illuminate\Support\Arr;

class ProductVariantPromotionModel extends AbstractModel
{
    /**
     * ProductVariantPromotionModel constructor.
     *
     * @param ProductVariantPromotion|null $model
     */
    public function __construct(ProductVariantPromotion $model = null)
    {
        parent::__construct($model);
    }

    /**
     * Fill data
     *
     * @param array $data
     * @return array
     */
    public function fillData(array $data)
    {
        return [
            'product_variant_id' => $data['product_variant_id'],
            'user_group_id'      => Arr::get($data, 'user_group_id', null),
            'priority'           => Arr::get($data, 'priority', null),
            'order'              => Arr::get($data, 'order', null),
            'price'              => (float)Arr::get($data, 'price', 0),
            'start_date'         => !empty($data['start_date']) ? $data['start_date'] : null,
            'end_date'           => !empty($data['end_date']) ? $data['end_date'] : null,
            'is_default'         => Arr::get($data, 'is_default', null),
            'is_active'          => Arr::get($data, 'is_active', 1)
        ];
    }

}