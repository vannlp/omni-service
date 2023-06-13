<?php
/**
 * User: dai.ho
 * Date: 28/01/2021
 * Time: 3:21 PM
 */

namespace App\Sync\Models;


use App\PromotionProgram;
use App\PromotionProgramCondition;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserGroup;
use App\V1\Models\AbstractModel;
use App\V1\Models\PromotionProgramConditionModel;

/**
 * Class PromotionModel
 * @package App\Sync\Models
 */
class PromotionModel extends AbstractModel
{
    /**
     * PromotionModel constructor.
     * @param PromotionProgram|null $model
     */
    public function __construct(PromotionProgram $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            // Check total_use_customer <= total_user
            if ($input['total_use_customer'] > $input['total_user']) {
                throw new \Exception(Message::get("V013", "total_use_customer", "total_user"));
            }

            $id = !empty($input['id']) ? $input['id'] : 0;

            $actions = !empty($input['actions']) ? $input['actions'] : [];
            $conditions = !empty($input['conditions']) ? $input['conditions'] : [];

            if (!empty($actions)) {
                foreach ($actions as $key => $action) {
                    $input[$key] = $action;
                }
            }

            if (!empty($conditions)) {
                foreach ($conditions as $key => $condition) {
                    $input[$key] = $condition;
                }
            }

            if (!empty($input['group_customer'])) {
                $groups = UserGroup::model()->whereIn('code', $input['group_customer'])
                    ->where('company_id', TM::getIDP()->company_id)
                    ->get()->pluck('id')->toArray();
            }

            if ($id) {
                $param['id'] = $id;
                $promotion = PromotionProgram::find($id);

                PromotionProgramCondition::model()->where('promotion_program_id', $id)->delete();

                if (empty($promotion)) {
                    throw new \Exception(Message::get("promotions.not-exist", "#$id"));
                }


                $promotion->code = $input['code'];
                $promotion->name = array_get($input, 'name', $promotion->name);
                $promotion->description = array_get($input, 'description', $promotion->description);
                $promotion->store_id = array_get($input, 'store_id', $promotion->store_id);
                $promotion->general_settings = array_get($input, 'general_settings', $promotion->general_settings);
                //$promotion->to = date("Y-m-d H:i:s", strtotime(array_get($input, 'to', $promotion->to)));
                $promotion->status = array_get($input, 'status', 0);
                $promotion->stack_able = array_get($input, 'stack_able', $promotion->stack_able);
                $promotion->multiply = array_get($input, 'multiply', $promotion->multiply);
                $promotion->sort_order = array_get($input, 'sort_order', $promotion->sort_order);
                $promotion->start_date = !empty($input['start_date']) ? date("Y-m-d",
                    strtotime($input['start_date'])) : null;
                $promotion->end_date = !empty($input['end_date']) ? date("Y-m-d",
                    strtotime($input['end_date'])) : null;
                $promotion->total_user = array_get($input, 'total_user', $promotion->total_user);
                $promotion->total_use_customer = array_get($input, 'total_use_customer',
                    $promotion->total_use_customer);
                $promotion->promotion_type = array_get($input, 'promotion_type', $promotion->promotion_type);
                $promotion->need_login = array_get($input, 'need_login', $promotion->need_login);
                $promotion->condition_combine = array_get($input, 'condition_combine', $promotion->condition_combine);
                $promotion->condition_bool = array_get($input, 'condition_bool', $promotion->condition_bool);
                $promotion->act_type = array_get($input, 'act_type', $promotion->act_type);
                $promotion->act_sale_type = array_get($input, 'act_sale_type', $promotion->act_sale_type);
                $promotion->act_price = array_get($input, 'act_price', $promotion->act_price);
                $promotion->default_store = json_encode([TM::getIDP()->store_id]);
                $promotion->group_customer = json_encode($groups ?? []);
                $promotion->act_not_product_condition = array_get($input, 'act_not_product_condition',
                    $promotion->act_not_product_condition);
                $promotion->act_not_special_product = array_get($input, 'act_not_special_product',
                    $promotion->act_not_special_product);
                $promotion->act_max_quality = array_get($input, 'act_max_quality', $promotion->act_max_quality);
                $promotion->act_not_products = json_encode(array_get($input, 'act_not_products',
                    $promotion->act_not_products));
                $promotion->act_categories = json_encode(array_get($input, 'act_categories',
                    $promotion->act_categories));
                $promotion->act_products = json_encode(array_get($input, 'act_products',
                    $promotion->act_products));
                $promotion->act_quatity = array_get($input, 'act_quatity', $promotion->act_quatity);
                $promotion->act_quatity_sale = array_get($input, 'act_quatity_sale', $promotion->act_quatity_sale);
                $promotion->act_approval = !empty($input['act_approval']) ? $input['act_approval'] : null;
                $promotion->company_id = TM::getIDP()->company_id;
                $promotion->updated_at = date('Y-m-d H:i:s', time());
                $promotion->updated_by = TM::getIDP()->sync_name;
                $promotion->save();
            } else {
                // Create Promotion
                $param = [
                    'code'                      => $input['code'],
                    'name'                      => array_get($input, 'name', ''),
                    'description'               => array_get($input, 'description', null),
                    'general_settings'          => array_get($input, 'general_settings', ''),
                    'status'                    => array_get($input, 'status', 0),
                    'stack_able'                => array_get($input, 'stack_able', 0),
                    'multiply'                  => array_get($input, 'multiply', 0),
                    'sort_order'                => array_get($input, 'sort_order', null),
                    'start_date'                => !empty($input['start_date']) ? date("Y-m-d",
                        strtotime($input['start_date'])) : null,
                    'end_date'                  => !empty($input['end_date']) ? date("Y-m-d",
                        strtotime($input['end_date'])) : null,
                    'total_user'                => array_get($input, 'total_user', null),
                    'total_use_customer'        => array_get($input, 'total_use_customer', null),
                    'promotion_type'            => array_get($input, 'promotion_type', PROMOTION_TYPE_AUTO),
                    'need_login'                => array_get($input, 'need_login', 0),
                    'condition_combine'         => array_get($input, 'condition_combine', ''),
                    'condition_bool'            => array_get($input, 'condition_bool', null),
                    'act_type'                  => array_get($input, 'act_type', null),
                    'act_sale_type'             => array_get($input, 'act_sale_type', null),
                    'act_price'                 => array_get($input, 'act_price', null),
                    'default_store'             => json_encode([TM::getIDP()->store_id]),
                    'group_customer'            => json_encode($groups ?? []),
                    'act_not_product_condition' => array_get($input, 'act_not_product_condition', null),
                    'act_not_special_product'   => array_get($input, 'act_not_special_product', null),
                    'act_max_quality'           => array_get($input, 'act_max_quality', null),
                    'act_not_products'          => json_encode(array_get($input, 'act_not_products', null)),
                    'act_categories'            => json_encode(array_get($input, 'act_categories', null)),
                    'act_products'              => json_encode(array_get($input, 'act_products', null)),
                    'act_quatity_sale'          => array_get($input, 'act_quatity_sale', null),
                    'act_quatity'               => array_get($input, 'act_quatity', null),
                    'act_approval'              => !empty($input['act_approval']) ? $input['act_approval'] : null,
                    'company_id'                => TM::getIDP()->company_id,
                ];
                $promotion = $this->create($param);
            }

            if (!empty($conditions['details'])) {
                $conditionModel = new PromotionProgramConditionModel();

                foreach ($conditions['details'] as $detail) {
                    $detail['promotion_program_id'] = $promotion->id;
                    $conditionModel->upsert($detail);
                }
            }

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $promotion;
    }
}