<?php


namespace App\V1\Models;


use App\Rotation;
use App\RotationCondition;
use App\RotationResult;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\DB;

class RotationModel extends AbstractModel
{
    /**
     * CouponModel constructor.
     * @param Rotation|null $model
     */
    public function __construct(Rotation $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $rotation = Rotation::find($id);
            if (empty($rotation)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $rotation->code           = array_get($input, 'code', $rotation->code);
            $rotation->name           = array_get($input, 'name', $rotation->name);
            $rotation->thumbnail_id   = array_get($input, 'thumbnail_id', $rotation->thumbnail_id);
            $rotation->description    = array_get($input, 'description', $rotation->description);
            $rotation->start_date     = array_get($input, 'start_date', $rotation->start_date);
            $rotation->end_date       = array_get($input, 'end_date', $rotation->end_date);
            $rotation->company_id     = TM::getCurrentCompanyId();
            $rotation->is_active      = array_get($input, 'is_active', $rotation->is_active);
            $rotation->updated_at     = date("Y-m-d H:i:s", time());
            $rotation->updated_by     = TM::getCurrentUserId();
            $rotation->save();
        } else {
            $param  = [
                'code'           => $input['code'] ?? null,
                'name'           => $input['name'] ?? null,
                'thumbnail_id'   => $input['thumbnail_id'] ?? null,
                'description'    => $input['description'] ?? null,
                'start_date'     => $input['start_date'] ?? null,
                'end_date'       => $input['end_date'] ?? null,
                'company_id'     => TM::getCurrentCompanyId(),
                'is_active'      => $input['is_active'] ?? 0
            ];
            $rotation = $this->create($param);
        }
        $allConditionRotation       = RotationCondition::model()->where('rotation_id', $rotation->id)->get()->toArray();
        $allConditionRotation       = array_pluck($allConditionRotation, 'id', 'id');
        $allConditionRotationDelete = $allConditionRotation;
        if (!empty($input['conditions'])) {
            foreach ($input['conditions'] as $key => $detail) {
                $param =[
                    'name'         => !empty($detail['name']) ? $detail['name'] : null,
                    'code'         => !empty($detail['code']) ? $detail['code'] : null,
                    'rotation_id'  => !empty($id) ? $id : null,
                    'type'         => !empty($detail['type']) ? $detail['type'] : null,
                    'price'        => !empty($detail['price']) ? $detail['price'] : null, 
                    'is_active'    => !empty($detail['is_active']) ? $detail['is_active'] : 0
                ];
                if (empty($detail['id']) || empty($allConditionRotation[$detail['id']])) {
                    $bannerDetail = new RotationCondition();
                    $bannerDetail->create($param);
                    continue;
                }
                $this->refreshModel();
                $param['id'] = $detail['id'];
                $detailModel = new RotationConditionModel();
                $detailModel->update($param);
                unset($allConditionRotationDelete[$detail['id']]);
            }
        }
        if (!empty($allConditionRotationDelete)) {
            RotationCondition::model()->whereIn('id', array_values($allConditionRotationDelete))->delete();
        }
        $allResultRotation       = RotationResult::model()->where('rotation_id', $rotation->id)->get()->toArray();
        $allResultRotation       = array_pluck($allResultRotation, 'id', 'id');
        $allResultRotationDelete = $allResultRotation;
        if (!empty($input['results'])) {
            foreach ($input['results'] as $key => $detail) {
                    $param = [
                        'name'            => !empty($detail['name']) ? $detail['name'] : null,
                        'code'            => !empty($detail['code']) ? $detail['code'] : null,
                        'type'            => !empty($detail['type']) ? $detail['type'] : null,
                        'coupon_id'       => !empty($detail['coupon_id']) ? $detail['coupon_id'] : null,
                        'coupon_name'     => !empty($detail['coupon_name']) ? $detail['coupon_name'] : null,
                        'description'     => !empty($detail['description']) ? $detail['description'] : null,
                        'ratio'           => !empty($detail['ratio']) ? $detail['ratio'] : null,
                        'rotation_id'     => !empty($id) ? $id : null,
                    ];
                if (empty($detail['id']) || empty($allResultRotation[$detail['id']])) {
                    // Create Detail
                    $bannerDetail = new RotationResult();
                    $bannerDetail->create($param);
                    continue;
                }
                // Update
                $this->refreshModel();
                $param['id'] = $detail['id'];
                $detailModel = new RotationResultModel();
                $detailModel->update($param);
                unset($allResultRotationDelete[$detail['id']]);
            }
        }
        if (!empty($allResultRotationDelete)) {
            RotationResult::model()->whereIn('id', array_values($allResultRotationDelete))->delete();
        }
        return $rotation; 
}
}