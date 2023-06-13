<?php
/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 08:08 PM
 */

namespace App\V1\Validators;


use App\Customer;
use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Http\Request;

class CustomerUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'              => 'required|exists:customers,id,deleted_at,NULL',
            'code'            => [
                'nullable',
                'min:3',
                'max:50',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    if (!empty($value)) {
                        $item = Customer::model()->where('code', $value)->get()->toArray();
                        if (!empty($item) && count($item) > 0) {
                            if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                                return $fail(Message::get("unique", "$attribute: #$value"));
                            }
                        }
                    }
                    return true;
                }
            ],
            'name'            => 'nullable|min:3|max:300',
            'card_name'       => 'nullable|min:3|max:30',
            'branch_name'     => 'max:300',
            'sscid'           => 'nullable|max:32',
            'email'           => [
                'nullable',
                'email',
                'max:50',
                function ($attribute, $value, $fail) {
                    $input = Request::capture();
                    $item = Customer::model()->where('email', $value)->get()->toArray();
                    if (!empty($item) && count($item) > 0) {
                        if (count($item) > 1 || ($input['id'] > 0 && $item[0]['id'] != $input['id'])) {
                            return $fail(Message::get("unique", "$attribute: #$value"));
                        }
                    }
                }
            ],
            'password'        => 'nullable|min:8',
            'group_id'        => 'nullable|exists:customer_groups,id,deleted_at,NULL',
            'type_id'         => 'nullable|exists:customer_types,id,deleted_at,NULL',
            'phone'           => 'max:14',
            'note'            => 'max:300',
            'is_seller'       => 'max:1|in:0,1',
            'address'         => 'max:500',
            'receipt_address' => 'max:300',
            'tax_number'      => 'max:15',
            'account_number'  => 'max:15',
            'bank_type'       => 'max:15',
            'spokesman'       => 'max:50',
            'id_number'       => 'max:15',
        ];
    }

    protected function attributes()
    {
        return [
            'name'            => Message::get("alternative_name"),
            'branch_name'     => Message::get("branch_name"),
            'email'           => Message::get("email"),
            'password'        => Message::get("password"),
            'phone'           => Message::get("phone"),
            'note'            => Message::get("note"),
            'is_seller'       => Message::get("is_seller"),
            'address'         => Message::get("address"),
            'receipt_address' => Message::get("address"),
            'tax_number'      => Message::get("tax_number"),
            'account_number'  => Message::get("account_number"),
            'bank_type'       => Message::get("bank_type"),
            'spokesman'       => Message::get("spokesman"),
            'id_number'       => Message::get("id_number"),
        ];
    }
}
