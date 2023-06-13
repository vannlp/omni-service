<?php

namespace App\V1\Transformers\Brand;

use App\Brand;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class BrandClientTransformer extends TransformerAbstract
{
    public function transform(Brand $brand)
    {
        return [
            'id'          => $brand->id,
            'name'        => $brand->name,
            'description' => $brand->description,
            'slug'        => $brand->slug,
            'parent_id'   => $brand->parent_id,
            'parent_name' => Arr::get($brand, 'parent.name', null),
            'has_children' => $brand->children->count(),
            'children'     => $brand->children->map(function ($item) {
        return $item->only(['id', 'name', 'description', 'slug', 'store_id']);
    }),
        ];
    }
}