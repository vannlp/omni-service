<?php

namespace App\V1\Models;

use App\ProductVariant;
use Illuminate\Support\Arr;

class ProductVariantModel extends AbstractModel
{
    /**
     * ProductVariantModel constructor.
     *
     * @param ProductVariant|null $model
     */
    public function __construct(ProductVariant $model = null)
    {
        parent::__construct($model);
    }

    public function generateCombinations(array $data, array &$all = array(), array $group = array(), $value = null, $i = 0)
    {
        $keys = array_keys($data);
        if (isset($value) === true) {
            array_push($group, $value);
        }

        if ($i >= count($data)) {
            array_push($all, $group);
        } else {
            $currentKey     = $keys[$i];
            $currentElement = $data[$currentKey];
            foreach ($currentElement as $val) {
                $this->generateCombinations($data, $all, $group, $val, $i + 1);
            }
        }

        return $all;
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
            'product_id'         => $data['product_id'],
            'product_attributes' => $data['product_attributes'],
            'product_code'       => Arr::get($data, 'product_code', null),
            'image'              => Arr::get($data, 'image', null),
            'inventory'          => 0,
            'price'              => (float)Arr::get($data, 'price', 0),
            'is_active'          => Arr::get($data, 'is_active', true)
        ];
    }

}