<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 08:59 PM
 */

namespace App\V1\Transformers\Price;


use App\Price;
use App\Supports\TM_Error;
use App\UserGroup;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class PriceTransformer extends TransformerAbstract
{
    public function transform(Price $price)
    {
        try {
            $groupId = explode(",", $price->group_ids);
            $saleArea = explode(",", $price->sale_area);

            if(!empty($price->city_code)){
                $strCitycode = explode(",", $price->city_code);
            }
            if(!empty($price->district_code)){
                $strDistrictcode = explode(",", $price->district_code);
            }
            if(!empty($price->ward_code)){
                $strWardcode = explode(",", $price->citward_codey_code);
            }

            return [
                'id'                   => $price->id,
                'code'                 => $price->code,
                'name'                 => $price->name,
                'from'                 => !empty($price->from) ? date("Y-m-d", strtotime($price->from)) : null,
                'to'                   => !empty($price->to) ? date("Y-m-d", strtotime($price->to)) : null,
                'group_ids'            => !empty($groupId) ? $groupId : [],
                'sale_area'            => !empty($saleArea) ? $saleArea : [],
                'sale_area_list'       => !empty($price->sale_area_list) ? json_decode($price->sale_area_list) : [],
                'city_code'            => $strCitycode ?? null,
                'district_code'        => $strDistrictcode ?? null,
                'ward_code'            => $strWardcode ?? null,
                'groups'               => empty($price->group_ids) ? [] : $this->getUserGroup($price->group_ids),
                'description'          => $price->description,
                'status'               => $price->status,
                'status_name'          => $price->status == 1 ? "Kích hoạt" : "Chưa kích hoạt",
                'company_id'           => $price->company_id,
                'order'                => $price->order,
                'duplicated_from'      => $price->duplicated_from,
                'duplicated_from_name' => Arr::get($price, 'getPrice.name', null),
                'is_active'            => $price->is_active,
                'details'              => $price->details->toArray(),
                'updated_at'           => !empty($price->updated_at) ? date('Y-m-d', strtotime($price->updated_at)) : null,
                'created_at'           => !empty($price->created_at) ? date('Y-m-d', strtotime($price->created_at)) : null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    private function getUserGroup($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $userGroup = UserGroup::model()
            ->whereIn('id', explode(",", $ids))
            ->select([
                'id as user_group_id',
                'code as user_group_code',
                'name as user_group_name',
            ])
            ->get()->toArray();
        return $userGroup;
    }
}
