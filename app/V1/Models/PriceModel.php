<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 08:58 PM
 */

namespace App\V1\Models;


use App\Price;
use App\PriceDetail;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\UserGroup;
use App\UserStore;
use Illuminate\Support\Arr;

class PriceModel extends AbstractModel
{
    /**
     * PriceModel constructor.
     *
     * @param Price|null $model
     */
    public function __construct(Price $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            if (!empty($input['group_ids'])) {
                $error         = false;
                $allUserGroups = UserGroup::all()->pluck('name', 'id')->toArray();
                foreach ($input['group_ids'] as $key => $group_id) {
                    if (empty($allUserGroups[$group_id])) {
                        $error = true;
                        break;
                    }
                }
                if ($error) {
                    throw new \Exception(Message::get("V003", Message::get("group_id")));
                }
                $strGroupIds = implode(",", $input['group_ids']);
            }

            if (!empty($input['sale_area'])) {
                $strSalearea = implode(",", $input['sale_area']);
            }

            if (!empty($input['city_code'])) {
                $strCitycode = implode(",", $input['city_code']);
            }
            if (!empty($input['district_code'])) {
                $strDistrictcode = implode(",", $input['district_code']);
            }
            if (!empty($input['ward_code'])) {
                $strWardcode = implode(",", $input['ward_code']);
            }

            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $price = Price::find($id);
                if (empty($price)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $price->name            = array_get($input, 'name', $price->name);
                $price->code            = array_get($input, 'code', $price->code);
                $price->from            = date("Y-m-d H:i:s", strtotime(array_get($input, 'from', $price->from)));
                $price->to              = date("Y-m-d H:i:s", strtotime(array_get($input, 'to', $price->to)));
                $price->description     = array_get($input, 'description', null);
                $price->group_ids       = $strGroupIds ?? null;
                $price->sale_area       = $strSalearea ?? null;
                $price->sale_area_list  = json_encode($input['sale_area_list']);
                $price->city_code       = $strCitycode ?? null;
                $price->district_code   = $strDistrictcode ?? null;
                $price->ward_code       = $strWardcode ?? null;
                $price->status          = array_get($input, 'status', $price->status);
                $price->order           = array_get($input, 'order', $price->order);
                $price->dup_type        = array_get($input, 'dup_type', $price->dup_type);
                $price->duplicated_from = array_get($input, 'duplicated_from', $price->duplicated_from);
                $price->value           = array_get($input, 'value', $price->value);
                $price->is_active       = array_get($input, 'is_active', $price->is_active);
                $price->updated_at      = date("Y-m-d H:i:s", time());
                $price->company_id      = array_get($input, 'company_id', $price->company_id);
                $price->updated_by      = TM::getCurrentUserId();
                $price->save();
            } else {
                $param = [
                    'code'            => $input['code'],
                    'name'            => $input['name'],
                    'from'            => !empty($input['from']) ? date("Y-m-d H:i:s", strtotime(array_get($input, 'from'))) : null,
                    'to'              => !empty($input['to']) ? date("Y-m-d H:i:s", strtotime(array_get($input, 'to'))) : null,
                    'description'     => array_get($input, 'description', null),
                    'group_ids'       => $strGroupIds ?? null,
                    'sale_area'       => $strSalearea ?? null,
                    'sale_area_list'  => json_encode($input['sale_area_list']) ?? null,
                    'city_code'       => $strCitycode ?? null,
                    'district_code'   => $strDistrictcode ?? null,
                    'ward_code'       => $strWardcode ?? null,
                    'status'          => array_get($input, 'status', null),
                    'order'           => array_get($input, 'order', null),
                    'duplicated_from' => array_get($input, 'duplicated_from', null),
                    'dup_type'        => array_get($input, 'dup_type', null),
                    'value'           => array_get($input, 'value', 0),
                    'company_id'      => TM::getCurrentCompanyId(),
                    'is_active'       => 1,
                ];
                $price = $this->create($param);
            }
            // Create | Update Price Details
            $priceId = $price->id;
            if (!empty($input['details'])) {
                $allPriceDetail       = PriceDetail::model()->where('price_id', $priceId)->get()->toArray();
                $allPriceDetail       = array_pluck($allPriceDetail, 'id', 'id');
                $allPriceDetailDelete = $allPriceDetail;
                foreach ($input['details'] as $key => $item) {
                    $id = $item['id'] ?? null;
                    if (!empty($allPriceDetailDelete[$id])) {
                        unset($allPriceDetailDelete[$id]);
                    }
                    $priceDetail = PriceDetail::find($id);
                    if (empty($priceDetail)) {
                        $param            = [
                            "price_id"   => $priceId,
                            "product_id" => $item['product_id'],
                            "from"       => date('Y-m-d H:i:s', strtotime($item['from'])),
                            "to"         => date('Y-m-d H:i:s', strtotime($item['to'])),
                            "price"      => !empty($item['price']) ? $item['price'] : 0,
                            "status"     => $item['status'],
                            //                            "product_variant_id" => $item['product_variant_id'],
                        ];
                        $priceDetailModel = new PriceDetailModel();
                        $priceDetailModel->create($param);
                    } else {
                        $priceDetail->price_id   = $priceId;
                        $priceDetail->product_id = $item['product_id'];
                        $priceDetail->from       = date('Y-m-d H:i:s', strtotime($item['from']));
                        $priceDetail->to         = date('Y-m-d H:i:s', strtotime($item['to']));
                        $priceDetail->price      = !empty($item['price']) ? $item['price'] : 0;
                        $priceDetail->status     = $item['status'];
//                        $priceDetail->product_variant_id = $item['product_variant_id'];
                        $priceDetail->save();
                    }
                }
                if (!empty($allPriceDetailDelete)) {
                    PriceDetail::model()->whereIn('id', array_values($allPriceDetailDelete))->delete();
                }
            }

            if (!empty($input['duplicated_from']) && empty($id)) {
                $details = Price::find($input['duplicated_from']);
                $details->load('details');
                $details = Arr::get($details, 'details', null);
                $now     = date("Y-m-d H:i:s");
                foreach ($details as $detail) {
                    $paramDetails[] = [
                        "price_id"   => $priceId,
                        "product_id" => $detail->product_id,
                        "from"       => date('Y-m-d H:i:s', strtotime($price->from)),
                        "to"         => date('Y-m-d H:i:s', strtotime($price->to)),
                        "price"      => $input['dup_type'] == 1 ? $detail->price + ($detail->price * $input['value'] / 100) : $detail->price - ($detail->price * $input['value'] / 100),
                        "status"     => $detail->status,
                        //                        "product_variant_id" => $detail->product_variant_id,
                        "created_at" => $now,
                        "created_by" => TM::getCurrentUserId()
                    ];
                }
                $priceDetailModel = new PriceDetail();
                $priceDetailModel->insert($paramDetails);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $price;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $query->orderBy('order', 'ASC');
        if (!empty($input['name'])) {
            $query->where('name', 'like', "%{$input['name']}%");
        }
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (isset($input['status'])) {
            $query->where('status', $input['status']);
        }
        if (!empty($input['store_id'])) {
            $company = UserStore::where('store_id', $input['store_id'])->first();
            if ($company) {
                $query->where('company_id', $company->company_id);
            }
        }
//        $query->where('company_id', TM::getCurrentCompanyId());
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
}
