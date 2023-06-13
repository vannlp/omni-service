<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\PromotionProgram;
use App\PromotionProgramCondition;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\User;

class PromotionProgramConditionModel extends AbstractModel
{
    public function __construct(PromotionProgramCondition $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                // Update Promotion
                $param['id'] = $id;
                $promotion = PromotionProgramCondition::find($id);

                if (empty($promotion)) {
                    throw new \Exception(Message::get("promotions.not-exist", "#$id"));
                }

                $promotion->promotion_program_id = array_get($input, 'promotion_program_id',
                    $promotion->promotion_program_id);
                $promotion->condition_name = array_get($input, 'condition_name', $promotion->condition_name);
                if (!empty($input['item_id'])) {
                    $promotion->item_id = array_get($input, 'item_id');
                    $promotion->item_code = array_get($input, 'item_code');
                    $promotion->item_name = array_get($input, 'item_name');
                }
                //$promotion->to = date("Y-m-d H:i:s", strtotime(array_get($input, 'to', $promotion->to)));
                $promotion->condition_type = array_get($input, 'condition_type', $promotion->condition_type);
                $promotion->condition_type_name = array_get($input, 'condition_type_name',
                    $promotion->condition_type_name);
                $promotion->multiply_type = array_get($input, 'multiply_type',
                    $promotion->multiply_type);    
                $promotion->condition_include_parent = array_get($input, 'condition_include_parent',
                    $promotion->condition_include_parent);
                $promotion->condition_include_child = array_get($input, 'condition_include_child',
                    $promotion->condition_include_child);
                $promotion->condition_input = array_get($input, 'condition_input', $promotion->condition_input);
                $promotion->condition_limit = array_get($input, 'condition_limit', $promotion->condition_limit);
                $promotion->updated_at = date('Y-m-d H:i:s', time());
                $promotion->updated_by = TM::getCurrentUserId();
                $promotion->save();
            } else {
                // Create Promotion
                $param = [
                    'promotion_program_id'     => array_get($input, 'promotion_program_id', ''),
                    'condition_name'           => array_get($input, 'condition_name', ''),
                    'condition_type'           => array_get($input, 'condition_type', null),
                    'condition_type_name'      => array_get($input, 'condition_type_name', null),
                    'multiply_type'            => array_get($input, 'multiply_type', null),
                    'condition_include_parent' => array_get($input, 'condition_include_parent', 0),
                    'condition_include_child'  => !empty($input['condition_include_child']) ? $input['condition_include_child'] : 0,
                    'condition_input'          => array_get($input, 'condition_input', null),
                    'condition_limit'          => array_get($input, 'condition_limit', null),
                ];

                if (!empty($input['item_id'])) {
                    $param['item_id'] = array_get($input, 'item_id');
                    $param['item_code'] = array_get($input, 'item_code');
                    $param['item_name'] = array_get($input, 'item_name');
                }

                $this->refreshModel();
                $promotion = $this->create($param);
            }

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $promotion;
    }
}
