<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BatchCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'              => 'required|unique_create_company_delete:batches,code',
            'name'              => 'required',
            'description'       => 'max:500',
        ];
    }

    protected function attributes()
    {
        return [
            'code'              => Message::get("code"),
            'name'              => Message::get("name"),
            'description'       => Message::get("description"),
        ];
    }
}