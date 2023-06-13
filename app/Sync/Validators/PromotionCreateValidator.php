<?php
/**
 * User: dai.ho
 * Date: 5/02/2021
 * Time: 9:08 AM
 */

namespace App\Sync\Validators;


use App\Supports\Message;

class PromotionCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'code'                         => 'required',
            'name'                         => 'required|max:200',
            'general_settings'             => 'nullable|max:200',
            'stack_able'                   => 'required',
            'multiply'                     => 'required',
            'promotion_type'               => 'required|in:' . implode(",", [
                    PROMOTION_TYPE_AUTO,
                    PROMOTION_TYPE_CODE,
                    PROMOTION_TYPE_DISCOUNT,
                    PROMOTION_TYPE_COMMISSION,
                    PROMOTION_TYPE_POINT,
                ]),
            'start_date'                   => 'date_format:Y-m-d',
            'end_date'                     => 'date_format:Y-m-d',
            'total_user'                   => 'numeric',
            'total_use_customer'           => 'numeric',
            'conditions.condition_combine' => 'required|in:All,Some',
            'conditions.condition_bool'    => 'required|in:True,False',
            // 'actions'            => 'nullable|array',
            'actions.act_type'             => 'required|in:' . (implode(",", [
                    "order_sale_off",
                    "order_sale_off_range",
                    "sale_off_all_products",
                    "sale_off_on_products",
                    "sale_off_on_categories",
                    "sale_off_cheapest",
                    "sale_off_expensive",
                    "sale_off_same_kind",
                    "sale_off_products_from_conditions",
                    "free_shipping",
                    "add_product_cart",
                    "order_discount",
                    "accumulate_point"
                ])),
            'actions.act_sale_type'        => 'required|in:' . (implode(",", [
                    "fixed",
                    "percentage",
                    "fixed_price",
                ])),
            'actions.act_approval'         => 'nullable|in:AUTO,PENDING',
        ];
    }

    protected function attributes()
    {
        return [
            'name' => Message::get("alternative_name"),
        ];
    }
}
