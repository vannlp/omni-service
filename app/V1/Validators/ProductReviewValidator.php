<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class ProductReviewValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'product_id' => 'required|exists:products,id,deleted_at,NULL',
            'rate'       => 'required|numeric',
            'message'    => 'nullable|max:255'
        ];
    }

    protected function attributes()
    {
        return [
            'product_id' => Message::get("product_id"),
            'rate'       => Message::get("rate"),
            'message'    => Message::get("message")
        ];
    }
}