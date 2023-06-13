<?php

namespace App\V1\Transformers\Brand;

use App\Brand;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class BrandTransformer extends TransformerAbstract
{
    public function transform(Brand $brand)
    {
        return [
            'id'           => $brand->id,
            'name'         => $brand->name,
            'description'  => $brand->description,
            'slug'         => $brand->slug,
            'store_id'     => $brand->store_id,
            'parent_id'    => $brand->parent_id,
            'parent_name' => Arr::get($brand, 'parent.name', null),
            'has_children' => $brand->children->count(),
            'children'     => $brand->children->map(function ($item) {
                return $item->only(['id', 'name', 'description', 'slug', 'store_id']);
            }),
            'created_at'   => date('d-m-Y', strtotime($brand->created_at)),
            'created_by'   => object_get($brand, 'userCreated.name'),
            'updated_at'   => date('d-m-Y', strtotime($brand->updated_at)),
            'updated_by'   => object_get($brand, 'userUpdated.name')
        ];
    }
}