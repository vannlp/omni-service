<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:48 AM
 */

namespace App\V1\Models;


use App\AccessTradeSetting;
use App\Age;
use App\Area;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;

class AccessTradeSettingModel extends AbstractModel
{
    public function __construct(AccessTradeSetting $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $item = AccessTradeSetting::find($id);
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
//            $item->code      = $input['code']?? $item->code;
            $item->key       = $input['api_key'] ?? $item->name;
            $item->value       = $input['value_script'] ?? $item->code;
            $item->campaign_id       = $input['campaign_id'] ?? $item->campaign_id;
            $item->category_id   = $input['category_id'] ?? $item->category_id;
            $item->category_name   = $input['category_id'] ?? $item->category_name;
            $item->campaign_id   = $input['campaign_id'] ?? $item->campaign_id;
            $item->display_in_categories   = $input['display_in_categories'] ?? $item->display_in_categories;
            $item->store_id   = $input['store_id'] ?? TM::getCurrentStoreId();
            $item->company_id = TM::getCurrentCompanyId();
            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
//                'code'       => $input['code'],
                'campaign_id'       => $input['campaign_id'],
                'key'       => $input['api_key'],
                'campaign_id'       => $input['campaign_id'],
                'value'       => $input['value_script'],
                'display_in_categories'       => $input['display_in_categories'],
                'category_id'       => $input['category_id'],
                'category_name'       => $input['category_name'] ?? null,
                'store_id'   => TM::getCurrentStoreId(),
                'company_id' => TM::getCurrentCompanyId()
            ];

            /** @var Area $item */
            $item = $this->create($param);
        }
        return $item;
    }

}