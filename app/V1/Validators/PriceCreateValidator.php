<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 08:59 PM
 */

namespace App\V1\Validators;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PriceCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input     = $this->_input;
        $companyId = TM::getCurrentCompanyId();
        return [
            'code'                         => [
                'required',
                'max:70',
                Rule::unique('prices')->where(function ($query) use ($input, $companyId) {
                    $query->where('code', $input['code'])
                        ->where('company_id', $companyId)
                        ->whereNull('deleted_at');
                })
            ],
            //            'code'                 => 'required|max:20|unique_create_delete:prices,code',
            'name'                         => 'required|max:50',
            'from'                         => 'required|date_format:Y-m-d',
            'to'                           => 'required|date_format:Y-m-d',
            'status'                       => 'required',
            'group_ids'                    => 'required|array',
            'sale_area'                    => 'required|array',
            'order'                        => 'required',
            'duplicated_from'              => 'nullable|exists:prices,id,deleted_at,NULL',
            'dup_type'                     => 'nullable|in:0,1',
//            'value'                        => 'nullable|numeric',
            'details'                      => 'array',
            'details.*.product_id'         => 'required|exists:products,id,deleted_at,NULL',
            'details.*.price'              => 'required',
            'details.*.status'             => 'required',
//            'details.*.product_variant_id' => 'required|exists:product_variants,id,deleted_at,NULL',
            'details.*.from'               => 'required|date_format:Y-m-d',
            'details.*.to'                 => 'required|date_format:Y-m-d',
        ];
    }

    protected function attributes()
    {
        return [
            'code'                 => Message::get("code"),
            'name'                 => Message::get("alternative_name"),
            'group_ids'            => Message::get("group_id"),
            'from'                 => Message::get("from"),
            'to'                   => Message::get("to"),
            'status'               => Message::get("status"),
            'duplicated_from'      => Message::get("duplicated_from"),
            'details'              => Message::get("details"),
            'details.*.product_id' => Message::get("product_id"),
            'details.*.price'      => Message::get("price"),
            'details.*.status'     => Message::get("status"),
            'details.*.from'       => Message::get("from"),
            'details.*.to'         => Message::get("to"),
        ];
    }
}
