<?php
/**
 * User: dai.ho
 * Date: 8/06/2020
 * Time: 3:08 PM
 */

namespace App\V1\Models;


use App\ShippingMethod;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;

class ShippingMethodModel extends AbstractModel
{
    public function __construct(ShippingMethod $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                /** @var ShippingMethod $item */
                $item = ShippingMethod::find($id);
                if (empty($item)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $item->name = $input['name'];
                $item->code = $input['code'];
                $item->price = !empty($input['price']) ? $input['price'] : null;
                $item->description = array_get($input, 'description', null);
                $item->company_id = TM::getCurrentCompanyId();
                $item->updated_at = date("Y-m-d H:i:s", time());
                $item->updated_by = TM::getCurrentUserId();
                $item->save();
            } else {
                $param = [
                    'code'        => $input['code'],
                    'name'        => $input['name'],
                    'price'       => !empty($input['price']) ? $input['price'] : null,
                    'description' => array_get($input, 'description'),
                    'company_id'  => TM::getCurrentCompanyId(),
                    'is_active'   => 1,
                ];
                /** @var ShippingMethod $item */
                $item = $this->create($param);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }

        return $item;
    }
}