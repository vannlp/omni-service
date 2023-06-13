<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductAttributeUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'product_id'         => 'required|exists:products,id,deleted_at,NULL',
            'attribute_group_id' => 'required|exists:attribute_groups,id,deleted_at,NULL',
            'attributes'         => 'required|array',
            'attributes.*'       => 'exists:attributes,id,deleted_at,NULL,attribute_group_id,'.$input['attribute_group_id']
        ];
    }

    protected function attributes()
    {
        return [
            'product_id'         => Message::get("product_id"),
            'attribute_group_id' => Message::get("attribute_group"),
            'attributes'         => Message::get("attribute")
        ];
    }
}