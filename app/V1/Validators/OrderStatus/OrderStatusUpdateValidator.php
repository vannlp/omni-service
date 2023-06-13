<?php


namespace App\V1\Validators\OrderStatus;


use App\Http\Validators\ValidatorBase;
use App\OrderStatus;
use App\Supports\Message;
use App\TM;
use Illuminate\Http\Request;

class OrderStatusUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'required|exists:order_status,id,deleted_at,NULL',
            'name'       => 'required|max:200',
            'code'       => [
                'required',
                'max:20',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = OrderStatus::model()->where('code', $value)->where('status_for', $input['status_for'])->where('company_id', TM::getCurrentCompanyId())->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                },
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