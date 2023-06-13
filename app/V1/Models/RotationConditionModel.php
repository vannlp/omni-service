<?php


namespace App\V1\Models;


use App\Rotation;
use App\RotationCondition;
use App\Supports\Message;
use App\TM;

class RotationConditionModel extends AbstractModel
{
    /**
     * CouponModel constructor.
     * @param RotationCondition|null $model
     */
    public function __construct(RotationCondition $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $rotation = RotationCondition::find($id);
            if (empty($rotation)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $rotation->rotation_id    = array_get($input, 'rotation_id', $rotation->rotation_id);
            $rotation->code           = array_get($input, 'code', $rotation->code);
            $rotation->name           = array_get($input, 'name', $rotation->name);
            $rotation->type           = array_get($input, 'type', $rotation->type);
            $rotation->price          = array_get($input, 'price', $rotation->price);
            $rotation->is_active      = array_get($input, 'is_active', $rotation->is_active);
            $rotation->updated_at     = date("Y-m-d H:i:s", time());
            $rotation->updated_by     = TM::getCurrentUserId();
            $rotation->save();
        } else {
            $param  = [
                'rotation_id'    => $input['rotation_id'],
                'code'           => $input['code'] ?? null,
                'name'           => $input['name'] ?? null,
                'type'           => $input['type'] ?? null,
                'price'          => $input['price'] ?? null,
                'is_active'      => $input['is_active'] ?? 0
            ];
            $rotation = $this->create($param);
        }
        return $rotation;
    }
}