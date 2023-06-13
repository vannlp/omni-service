<?php

namespace App\V1\Transformers\Attribute;

use App\Attribute;
use League\Fractal\TransformerAbstract;

class AttributeTransformer extends TransformerAbstract
{
    public function transform(Attribute $attribute)
    {
        return [
            'id'                 => $attribute->id,
            'type'               => $attribute->type,
            'attribute_group_id' => $attribute->attribute_group_id,
            'attributeGroup'     => $attribute->attributeGroup,
            'name'               => $attribute->name,
            'description'        => $attribute->description,
            'value'              => $attribute->value,
            'slug'               => $attribute->slug,
            'order'              => $attribute->order,
            'created_at'         => date('d-m-Y', strtotime($attribute->created_at)),
            'created_by'         => object_get($attribute, 'userCreated.name'),
            'updated_at'         => date('d-m-Y', strtotime($attribute->updated_at)),
            'updated_by'         => object_get($attribute, 'userUpdated.name')
        ];
    }
}