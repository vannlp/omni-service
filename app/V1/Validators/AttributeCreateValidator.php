<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AttributeCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
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
                        ->whereNull('deleted_at');
                })
            ],
            'description'        => 'nullable|string|max:150'
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