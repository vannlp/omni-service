<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:48 AM
 */

namespace App\V1\Models;


use App\Area;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;

class AreaModel extends AbstractModel
{
    public function __construct(Area $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return Area
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            /** @var Area $item */
            $item = Area::find($id);
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->name = $input['name'];
            $item->code = $input['code'];
            $item->store_id = $input['store_id'];
            $item->image_id = Arr::get($input, 'image_id', $item->image_id);
            $item->description = array_get($input, 'description', null);
            $item->company_id = TM::getCurrentCompanyId();
            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'image_id'    => $input['image_id'],
                'store_id'    => $input['store_id'],
                'description' => array_get($input, 'description'),
                'company_id' => TM::getCurrentCompanyId(),
                'is_active'   => 1,
            ];

            /** @var Area $item */
            $item = $this->create($param);
        }

        return $item;
    }

}