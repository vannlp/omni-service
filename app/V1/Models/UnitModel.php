<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:23
 */

namespace App\V1\Models;


use App\Supports\Message;
use App\TM;
use App\Unit;

class UnitModel extends AbstractModel
{
    public function __construct(Unit $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return Unit|mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            /** @var Unit $item */
            $item = Unit::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->name = $input['name'];
            $item->code = $input['code'];
            $item->description = array_get($input, 'description', null);
            $item->company_id = TM::getCurrentCompanyId();
            $item->store_id = $input['store_id'];
            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'description' => array_get($input, 'description'),
                'company_id'  => TM::getCurrentCompanyId(),
                'store_id'    => $input['store_id'],
                'is_active'   => 1,
            ];

            $item = $this->create($param);
        }

        return $item;
    }
}