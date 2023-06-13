<?php
/**
 * User: dai.ho
 * Date: 1/06/2020
 * Time: 1:38 PM
 */

namespace App\V1\Models;


use App\CatalogOption;
use App\Supports\Message;
use App\TM;

class CatalogOptionModel extends AbstractModel
{
    public function __construct(CatalogOption $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return CatalogOption|mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            /** @var CatalogOption $item */
            $item = CatalogOption::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->name = $input['name'];
            $item->code = $input['code'];
            $item->type = array_get($input, 'type', null);
            $item->order = array_get($input, 'order', null);
            $item->description = array_get($input, 'description', null);
            $item->company_id = TM::getCurrentCompanyId();
            $item->store_id = $input['store_id'];
            $item->values = json_encode($input['values']);
//            foreach ($input['values'] as $value) {
//                if (!empty($value['image_upload'])) {
//
//                }
//            }

            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'type'        => array_get($input, 'type'),
                'order'       => array_get($input, 'order'),
                'description' => array_get($input, 'description'),
                'company_id'  => TM::getCurrentCompanyId(),
                'store_id'    => $input['store_id'],
                'values'      => json_encode($input['values'] ?? []),
                'is_active'   => 1,
            ];

            $item = $this->create($param);
        }

        return $item;
    }
}