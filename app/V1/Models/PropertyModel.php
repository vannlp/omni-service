<?php

namespace App\V1\Models;


use App\Property;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Str;

class PropertyModel extends AbstractModel
{
    /**
     * @param Property|null $model
     */
    public function __construct(Property $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $item = Property::find($id);
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
                'code'       => Str::upper(Str::ascii(Str::slug($input['code']))),
                'name'       => $input['name'],
                'store_id'   => TM::getCurrentStoreId(),
                'company_id' => TM::getCurrentCompanyId()
            ];


            $item = $this->create($param);
        }
        return $item;
    }

}