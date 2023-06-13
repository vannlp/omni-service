<?php
/**
 * User: dai.ho
 * Date: 27/01/2021
 * Time: 10:35 AM
 */

namespace App\Sync\Validators;


use App\Supports\Message;

class OrderCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'OrderType'             => 'nullable|in:' . implode(",", array_keys(ORDER_TYPE_SYNC)),
            'Status'                => 'required',
            'OrderNumber'           => 'required',
            'ShippingPhone'         => 'max:14',
            'ShippingName'          => 'required',
            'CustomerName'          => 'required',
            'CustomerCode'          => 'required|exists:users,code,deleted_at,NULL',
            'ApprovedBy'            => 'required|exists:users,id,deleted_at,NULL',
            'ShippingAddress'       => 'required',
            'Ward'                  => 'required|exists:wards,name,deleted_at,NULL',
            'District'              => 'required|exists:districts,district,deleted_at,NULL',
            'City'                  => 'required|exists:cities,province,deleted_at,NULL',
            'OrderDate'             => 'required',
            'ApprovedDate'          => 'required',
            'CompletedDate'         => 'required',
            'DistributorCode'       => 'required|exists:users,code,deleted_at,NULL',
            'details'               => 'required|array',
            'details.*.ProductCode' => 'required|exists:products,code,deleted_at,NULL',
            'details.*.Quantity'    => 'required|numeric',
            'details.*.Price'       => 'required|numeric',
            'details.*.Total'       => 'required|numeric'
        ];
    }

    protected function attributes()
    {
        return [
            'OrderType'             => 'OrderType',
            'Status'                => 'Status',
            'OrderNumber'           => 'OrderNumber',
            'ShippingPhone'         => 'ShippingPhone',
            'ShippingName'          => 'ShippingName',
            'CustomerName'          => 'CustomerName',
            'CustomerCode'          => 'CustomerCode',
            'ApprovedBy'            => 'ApprovedBy',
            'ShippingAddress'       => 'ShippingAddress',
            'Ward'                  => 'Ward',
            'District'              => 'District',
            'City'                  => 'City',
            'OrderDate'             => 'OrderDate',
            'ApprovedDate'          => 'ApprovedDate',
            'CompletedDate'         => 'CompletedDate',
            'DistributorCode'       => 'DistributorCode',
            'details'                => Message::get("detail"),
            'details.*.qty'          => Message::get("quantity"),
            'details.*.price'        => Message::get("price"),
            'details.*.product_code' => Message::get("products"),
            'details.*.total'        => Message::get("total")
        ];
    }
}