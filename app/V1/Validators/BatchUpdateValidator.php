<?php


namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class BatchUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'        => 'required|unique_update_company_delete:batches,code',
            'name'        => 'required',
            'description' => 'max:500',
            //            'warehouse_id' => 'exists:warehouses,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'code'        => Message::get("code"),
            'name'        => Message::get("name"),
            'description' => Message::get("description"),
            //            'warehouse_id' => Message::get("warehouse_id")
        ];
    }
}