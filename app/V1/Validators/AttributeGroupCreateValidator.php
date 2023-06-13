<?php

namespace App\V1\Validators;

use App\AttributeGroup;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AttributeGroupCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'store_id'    => [
                'required',
                'integer',
                'exists:stores,id,deleted_at,NULL'
            ],
            'type'        => 'required|string|in:' . implode(',', AttributeGroup::TYPE),
            'name'        => [
                'required',
                'string',
                'max:70',
                Rule::unique('attribute_groups')->where(function ($query) use ($input) {
                    $query->where('name', $input['name'])
                        ->where('store_id', Arr::get($input, 'store_id', null))
                        ->where('type', Arr::get($input, 'type', null))
                        ->whereNull('deleted_at');
                })
            ],
            'description' => 'nullable|string|max:150'
        ];
    }

    protected function attributes()
    {
        return [
            'name'        => Message::get("name"),
            'type'        => Message::get("attribute_type"),
            'description' => Message::get("description"),
            'store_id'    => Message::get("store_id")
        ];
    }
}