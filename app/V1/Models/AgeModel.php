<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:48 AM
 */

namespace App\V1\Models;


use App\Age;
use App\Area;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;

class AgeModel extends AbstractModel
{
    public function __construct(Age $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $item = Age::find($id);
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->name       = $input['name'] ?? $item->name;
            $item->code       = $input['code'] ?? $item->code;
            $item->store_id   = $input['store_id'] ?? TM::getCurrentStoreId();
            $item->company_id = TM::getCurrentCompanyId();
            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'code'       => $input['code'],
                'name'       => $input['name'],
                'store_id'   => TM::getCurrentStoreId(),
                'company_id' => TM::getCurrentCompanyId()
            ];

            /** @var Area $item */
            $item = $this->create($param);
        }
        return $item;
    }

}