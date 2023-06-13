<?php

namespace App\V1\Transformers\Collection;

use League\Fractal\TransformerAbstract;

class CollectionSearchTransformer extends TransformerAbstract
{
    public function transform($collection)
    {
        $products = $collection->products->transform(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'type' => $product->type
            ];
        });

        return [
            'id' => $collection->id,
            'name' => $collection->name,
            'description' => $collection->description,
            'products' => $products
        ];
    }
}
