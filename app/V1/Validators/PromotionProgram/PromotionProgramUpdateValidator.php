<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:59 PM
 */

namespace App\V1\Validators\PromotionProgram;


use App\Http\Validators\ValidatorBase;
use App\Promotion;
use App\Supports\Message;
use Illuminate\Http\Request;

class PromotionProgramUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'id'                           => 'exists:promotion_programs,id,deleted_at,NULL',
            'code'                         => 'required',
            'name'                         => 'required|max:200',
            'general_settings'             => 'nullable|max:200',
            'status'                       => 'required|in:0,1',
            'stack_able'                   => 'required',
            'multiply'                     => 'required',
            'sort_order'                   => 'required',
            'promotion_type'               => 'required|in:' . (implode(",", [
                    PROMOTION_TYPE_AUTO,
                    PROMOTION_TYPE_CODE,
                    PROMOTION_TYPE_DISCOUNT,
                    PROMOTION_TYPE_COMMISSION,
                    PROMOTION_TYPE_POINT,
                    PROMOTION_TYPE_FLASH_SALE,
                    PROMOTION_TYPE_GIFT,
                ])),
            'start_date'                   => 'date_format:Y-m-d H:i:s',
            'end_date'                     => 'date_format:Y-m-d H:i:s',
            'total_user'                   => 'numeric',
            'total_use_customer'           => 'numeric',
            'conditions.condition_combine' => 'required|in:All,Some',
            'conditions.condition_bool'    => 'required|in:True,False',
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
                    "accumulate_point",
                    "buy_x_get_y",
                    "combo",
                    "last_buy"
                ])),
            'actions.act_sale_type'        => 'nullable|in:' . (implode(",", [
                    "fixed",
                    "percentage",
                    "config",
                    "fixed_price",
                ])),
            'actions.act_approval'         => 'nullable|in:AUTO,PENDING',
        ];
    }

    protected function attributes()
    {
        return [
            'name' => Message::get("name"),
        ];
    }
}
