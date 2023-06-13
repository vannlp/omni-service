<?php
/**
 * Created by PhpStorm.
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 4:07 PM
 */

namespace App\V1\Transformers\SalePrice;


use App\CustomerGroup;
use App\SalePrice;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class SalePriceTransformer extends TransformerAbstract
{
    public function transform(SalePrice $salePrice)
    {
        $priceDetails = [];
        $details = $salePrice->salePriceDetails;
        foreach ($details as $detail) {
            $priceDetails[] = [
                'customer_group_id'   => $detail->customer_group_ids,
                'customer_group_name' => object_get($detail, 'customerGroup.name'),
            ];
        }
        $priceCustomerGroupName = array_pluck($priceDetails, 'customer_group_name');
        $customer_groups_name_concat = implode(', ', $priceCustomerGroupName);
        try {
            return [
                'id'                          => $salePrice->id,
                'product_id'                  => $salePrice->product_id,
                'product_name'                => object_get($salePrice, 'product.name'),
                'product_type'                => object_get($salePrice, 'product.type'),
                'product_code'                => object_get($salePrice, 'product.code'),
                'product_tax'                 => object_get($salePrice, 'product.tax'),
                'category_name'               => object_get($salePrice, 'product.cate.name'),
                'unit_id'                     => $salePrice->unit_id,
                'unit_name'                   => object_get($salePrice, 'units.name'),
                'price'                       => $salePrice->price,
                'company_id'                  => $salePrice->company_id,
                'customer_group_ids'          => object_get($salePrice, 'customer_group_ids', null),
                'customer_groups_name_concat' => !empty($customer_groups_name_concat) ? $customer_groups_name_concat : null,
                'price_id'                    => $salePrice->price_id,
                'price_name'                  => object_get($salePrice, 'types.name'),
                'from'                        => !empty($salePrice->from) ? date("d-m-Y", strtotime($salePrice->from)) : null,
                'to'                          => !empty($salePrice->to) ? date("d-m-Y", strtotime($salePrice->to)) : null,
                //                'date_from'                   => date("d-m-Y H:i:s", strtotime(object_get($salePrice, 'types.from'))),
                //                'date_to'                     => date("d-m-Y H:i:s", strtotime(object_get($salePrice, 'types.to'))),
                'discount'                    => $salePrice->discount,
                'cs_number'                   => $salePrice->cs_number,
                'seed_level'                  => $salePrice->seed_level,
                'packing_standard'            => $salePrice->packing_standard,
                'description'                 => $salePrice->description,
                'is_active'                   => $salePrice->is_active,
                'created_at'                  => !empty($salePrice->created_at) ? date('d-m-Y',
                    strtotime($salePrice->created_at)) : null,
                'updated_at'                  => !empty($salePrice->updated_at) ? date('d-m-Y',
                    strtotime($salePrice->updated_at)) : null,

            ];

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }


//    private function idToCustomerGroup($ids)
//    {
//        if (empty($ids)) {
//            return [];
//        }
//        $customerGroup = CustomerGroup::model()->select(['name'])->whereIn('id', explode(",", $ids))->get();
//        $category = array_pluck($customerGroup, 'name');
//        $category = implode(', ', $category);
//        return $category;
//    }

}