<?php

namespace App\V1\Transformers\AttributeGroup;

use App\AttributeGroup;
use League\Fractal\TransformerAbstract;

class AttributeGroupTransformer extends TransformerAbstract
{
    public function transform(AttributeGroup $attributeGroup)
    {
        return [
            'id'             => $attributeGroup->id,
            'store_id'       => $attributeGroup->store_id,
            'type'           => $attributeGroup->type,
            'name'           => $attributeGroup->name,
            'description'    => $attributeGroup->description,
            'slug'           => $attributeGroup->slug,
            'created_at'     => date('d-m-Y', strtotime($attributeGroup->created_at)),
            'created_by'     => object_get($attributeGroup, 'userCreated.name'),
            'updated_at'     => date('d-m-Y', strtotime($attributeGroup->updated_at)),
            'updated_by'     => object_get($attributeGroup, 'userUpdated.name')
        ];
    }
}