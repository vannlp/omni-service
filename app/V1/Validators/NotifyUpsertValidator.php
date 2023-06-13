<?php

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class NotifyUpsertValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                   => 'exists:notifies,id,deleted_at,NULL',
            'title'                => 'required|max:100',
            'body'                 => 'required',
            'type'                 => 'in:PRODUCT,NEWS,CATEGORY',
            'target_id'            => 'nullable|numeric',
            'product_search_query' => 'max:300',
            'delivery_date'        => 'nullable|date_format:d-m-Y H:i:s',
            'notify_for'           => 'required|in:ALL,PARTNER,CUSTOMER',
            'frequency'            => 'nullable|in:ASAP,ONCE,DAILY',
        ];
    }

    protected function attributes()
    {
        return [
            'id'                   => Message::get("id"),
            'title'                => Message::get("title"),
            'body'                 => Message::get("body"),
            'product_search_query' => Message::get("product_search_query"),
            'delivery_date'        => Message::get("delivery_date"),
            'notify_for'           => Message::get("notify_for"),
            'type'                 => Message::get("type"),
        ];
    }
}