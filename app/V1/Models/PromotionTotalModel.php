<?php
/**
 * User: dai.ho
 * Date: 9/07/2020
 * Time: 10:05 AM
 */

namespace App\V1\Models;


use App\Cart;
use App\Order;
use App\PromotionProgram;
use App\PromotionTotal;
use App\TM;
use Illuminate\Support\Facades\DB;

class PromotionTotalModel extends AbstractModel
{
    public function __construct(PromotionTotal $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);

        $this->sortBuilder($query, $input);
        $full_columns = DB::getSchemaBuilder()->getColumnListing($this->getTable());

        if (!empty($input['from'])) {
            $query->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime($input['from'])));
        }

        if (!empty($input['to'])) {
            $query->where('created_at', '<=', date('Y-m-d 23:59:59', strtotime($input['to'])));
        }
        if (!empty($input['group_id'])) {
            $groupId = explode(",", $input['group_id']);
            $query->whereHas('customer', function ($q) use ($groupId) {
                $q->orWhereIn('group_id', $groupId);
            });
        }
        $input = array_intersect_key($input, array_flip($full_columns));

        foreach ($input as $field => $value) {
            if ($value === "") {
                continue;
            }
            if (is_array($value)) {
                $query->where(function ($q) use ($field, $value) {
                    foreach ($value as $action => $data) {
                        $action = strtoupper($action);
                        if ($data === "") {
                            continue;
                        }
                        switch ($action) {
                            case "LIKE":
                                $q->orWhere(DB::raw($field), "like", "%$data%");
                                break;
                            case "IN":
                                $q->orWhereIn(DB::raw($field), $data);
                                break;
                            case "NOT IN":
                                $q->orWhereNotIn(DB::raw($field), $data);
                                break;
                            case "NULL":
                                $q->orWhereNull(DB::raw($field));
                                break;
                            case "NOT NULL":
                                $q->orWhereNotNull(DB::raw($field));
                                break;
                            case "BETWEEN":
                                $q->orWhereBetween(DB::raw($field), $value);
                                break;
                            default:
                                $q->orWhere($field, $action, $data);
                                break;
                        }
                    }
                });
            } else {
                $query->where(DB::raw($field), $value);
            }
        }
        $query->where(function ($q) {
            $q->orWhere('promotion_act_type', 'free_shipping')
                ->orWhere('value', '>', '0');
        })->where('company_id', TM::getCurrentCompanyId());

        if (!empty($input['store_id'])) {
            $query->where('store_id', $input['store_id']);
        }


        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function updatePromotionOrder(Order $order, Cart $cart)
    {
        /** @var array $promotions */
        $promotions = !empty($cart->promotion_info) ? json_decode($cart->promotion_info, true) : [];
        if (empty($promotions)) {
            return false;
        }

//        $pro_codes = array_column($promotions, 'promotion_code');
//        $pro_info = PromotionProgram::model()->whereIn('code', $pro_codes)->get()->pluck(null, 'code')->toArray();
        foreach ($promotions as $promotion) {
            $promotionTotal                         = new PromotionTotal();
            $promotionTotal->cart_id                = $cart->id;
            $promotionTotal->order_id               = $order->id;
            $promotionTotal->order_code             = $order->code;
            $promotionTotal->order_customer_id      = object_get($order, 'customer.id');
            $promotionTotal->order_customer_code    = object_get($order, 'customer.code');
            $promotionTotal->order_customer_name    = object_get($order, 'customer.name');
            $promotionTotal->promotion_code         = $promotion['promotion_code'];
            $promotionTotal->promotion_name         = $promotion['promotion_name'];
            $promotionTotal->promotion_type         = $promotion['promotion_type'];
            $promotionTotal->promotion_act_approval = $promotion['act_approval'];
            $promotionTotal->promotion_act_type     = $promotion['act_type'];
            $promotionTotal->promotion_info         = json_encode($promotion['promotion_info']);
            $promotionTotal->value                  = $promotion['value'];
            $promotionTotal->company_id             = TM::getCurrentCompanyId();
            $promotionTotal->store_id               = $order->store_id;
            $promotionTotal->created_at             = date('Y-m-d H:i:s');
            $promotionTotal->created_by             = TM::getCurrentUserId();
            $promotionTotal->save();

            // Update Free Ship
            if ($promotion['act_type'] == 'free_shipping') {
                $order->is_freeship = 1;
                $order->save();
            }

              // Update last_buy
              if ($promotion['act_type'] == 'last_buy') {
                $last_order = Order::model()->where('customer_id', object_get($order, 'customer.id'))->where('status', "COMPLETED")->orderBy('created_at', 'desc')->first();
                if(!empty($last_order)) {
                    $usedOrderLastBuy   = PromotionProgram::where('id', $promotion['promotion_id'])->value('order_used');
                    $usedOrderLastBuy   = !empty($usedOrderLastBuy) ? explode(",", $usedOrderLastBuy) : [];
                    $usedOrderLastBuy[] = $last_order->id;
                    PromotionProgram::where('id', $promotion['promotion_id'])->update([
                        'order_used' => !empty($usedOrderLastBuy) ? implode(",", $usedOrderLastBuy) : null
                    ]);
                }
          }
            //   if ($promotion['act_type'] == 'last_buy') {
            //     $last_order = Order::model()->where('customer_id', object_get($order, 'customer.id'))->where('status', "COMPLETED")->orderBy('created_at', 'desc')->first();
            //     PromotionProgram::where('id', $promotion['promotion_id'])->update([
            //         'order_used' => implode(",", $last_order)
            //     ]);
            // }

            // Update Used
            $promoProgram             = PromotionProgram::model()->where('id', $promotion['promotion_id'])->first();
            $promoProgram->used       += 1;
            $oldUsed                  = !empty($promoProgram->used_order) ? explode(",", $promoProgram->used_order) : [];
            $oldUsed[]                = $order->code;
            $promoProgram->used_order = implode(",", $oldUsed);
            $promoProgram->save();
        }

        return true;
    }
}