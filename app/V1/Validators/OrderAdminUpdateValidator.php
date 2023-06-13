<?php
/**
 * User: kpistech2
 * Date: 2019-10-27
 * Time: 17:22
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class OrderAdminUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'         => 'required|exists:orders,id,deleted_at,NULL',
            'status'     => 'max:500',
            'partner_id' => 'required|exists:users,id,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'status'     => Message::get("status"),
            'partner_id' => Message::get("partner_id"),
        ];
    }
}