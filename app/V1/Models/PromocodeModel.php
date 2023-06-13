<?php


namespace App\V1\Models;


use App\Promocode;
use App\Supports\Message;
use App\TM;

class PromocodeModel extends AbstractModel
{
    /**
     * CouponModel constructor.
     * @param Promocode|null $model
     */
    public function __construct(Promocode $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {

        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $promocode = Promocode::find($id);
            if (empty($promocode)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $promocode->code           = array_get($input, 'code', $promocode->code);
            $promocode->value          = array_get($input, 'value', $promocode->value);
            $promocode->user_use       = array_get($input, 'user_use', $promocode->user_use);
            $promocode->is_active      = array_get($input, 'is_active', $promocode->is_active);
            $promocode->save();
        } else {
            $param  = [
                'code'           => $input['code'],
                'value'          => $input['value'],
                'user_use'       => $input['user_use'],
                'is_active'      => 1,
            ];
            $promocode = $this->create($param);
        }

    

       
        return $promocode;
    }
}