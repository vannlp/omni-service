<?php


namespace App\V1\Validators\OrderStatus;


use App\Http\Validators\ValidatorBase;
use App\OrderStatus;
use App\Supports\Message;
use App\TM;
use Illuminate\Http\Request;

class OrderStatusCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'name'       => 'required|max:200',
            'code'       => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item  = OrderStatus::model()->where('code', $value)->where('status_for', $input['status_for'])->where('company_id', TM::getCurrentCompanyId())->first();
                    if (!empty($item)) {
                        return $fail(Message::get("unique", "$attribute: #$value"));
                    }
                    return true;
                }
            ],
            'company_id' => 'required|exists:companies,id,deleted_at,NULL',
            'order'      => 'nullable|numeric'
        ];
    }

    protected function attributes()
    {
        return [
            'name'       => Message::get("name"),
            'code'       => Message::get("code"),
            'company_id' => Message::get("company_id"),
            'order'      => Message::get("order"),
        ];
    }
}