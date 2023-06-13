<?php

namespace App\V1\Validators;

use App\AttributeGroup;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AttributeUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'id'                 => 'required|exists:attributes,id,deleted_at,NULL',
            'attribute_group_id' => [
                'required',
                'integer',
                'exists:attribute_groups,id,deleted_at,NULL'
            ],
            'name'               => [
                'required',
                'string',
                'max:70',
                Rule::unique('attributes')->where(function ($query) use ($input) {
                    $query->where('name', $input['name'])
                        ->where('attribute_group_id', Arr::get($input, 'attribute_group_id', null))
                        ->where('id', '!=', $input['id'])
                        ->whereNull('deleted_at');
                })
            ],
            'description'        => 'string|max:150'
        ];
    }

    protected function attributes()
    {
        return [
            'name'               => Message::get("name"),
            'description'        => Message::get("description"),
            'attribute_group_id' => Message::get("attribute_group")
        ];
    }
}