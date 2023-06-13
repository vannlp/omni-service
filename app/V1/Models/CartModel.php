<?php


namespace App\V1\Models;


use App\Cart;
use App\CartDetail;
use App\Category;
use App\ConfigShipping;
use App\Order;
use App\Product;
use App\PromotionProgramCondition;
use App\PromotionTotal;
use App\Supports\Message;
use App\TM;
use App\User;
use App\WarehouseDetail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class CartModel extends AbstractModel
{
    /**
     * CartModel constructor.
     * @param Cart|null $model
     */
    public function __construct(Cart $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $cart = Cart::find($id);
            if (empty($cart)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $cart->address = array_get($input, 'address', $cart->address);
            $cart->ship_address_latlong = array_get($input, 'ship_address_latlong', $cart->ship_address_latlong);
            $cart->description = array_get($input, 'description', $cart->description);
            $cart->phone = array_get($input, 'phone', $cart->phone);
            $cart->payment_method = array_get($input, 'payment_method', $cart->payment_method);
            $cart->receiving_time = !empty($input['receiving_time']) ? date("Y-m-d H:i:s",
                strtotime($input['receiving_time'])) : $cart->receiving_time;
            $cart->updated_at = date("Y-m-d H:i:s", time());
            $cart->updated_by = TM::getCurrentUserId();
            $cart->save();
        }

        #Create|Update Cart Detail
        if (!empty($input['details'])) {
            $allCartDetail = CartDetail::model()->where('cart_id', $id)->get()->toArray();
            $allCartDetail = array_pluck($allCartDetail, 'id', 'id');
            $allCartDetailDelete = $allCartDetail;

            $now = date("Y-m-d H:i:s");
            foreach ($input['details'] as $detail) {
                $product = Product::find($detail['product_id']);
                $quantity = array_get($detail, 'quantity', 1);

                if (!empty($product->price_down)) {
                    if (!empty($product->down_from) && !empty($product->down_to)) {
                        if ($product->down_from <= $now && $product->down_to >= $now) {
                            $price = $product->price_down;
                        } else {
                            $price = $product->price;
                        }
                    } else {
                        $price = $product->price_down;
                    }
                } else {
                    $price = $product->price;
                }
                $total = $price * $quantity;
                if (empty($allOrderDetail[$detail['id']])) {
                    $cartDetail = new CartDetailModel();
                    $paramDetail = [
                        'cart_id'             => $cart->id,
                        'product_id'          => $product->id,
                        'product_code'        => $product->code,
                        'product_name'        => $product->name,
                        'product_description' => $product->description,
                        'price'               => $price,
                        'old_product_price'   => $product->price,
                        'product_thumb'       => $product->thumbnail,
                        'quantity'            => $quantity,
                        'total'               => $total,
                    ];

                    $cartDetail->create($paramDetail);
                    continue;
                }
                // Update
                unset($allCartDetailDelete[$detail['id']]);
                $cartDetail = CartDetail::find($detail['id']);
                $cartDetail->quantity = $quantity;
                $cartDetail->total = $total;
                $cartDetail->price = $price;
                $cartDetail->updated_at = date('Y-m-d H:i:s', time());
                $cartDetail->updated_by = TM::getCurrentUserId();
                $cartDetail->save();
            }
            if (!empty($allCartDetailDelete)) {
                // Delete Order Detail
                CartDetail::model()->whereIn('id', array_values($allCartDetailDelete))->delete();
            }
        }
        return $cart;
    }

    public function applyPromotion(Cart $cart)
    {
        $cart = Cart::model()->with(['details'])->where('id', $cart->id)->first();
        $promotion_program_model = new PromotionProgramModel();
        $allPromotionDetail = $promotion_program_model->model->select([
            $promotion_program_model->getTable() . '.id as pro_id',
            $promotion_program_model->getTable() . '.sort_order as pro_order',
            $promotion_program_model->getTable() . '.code as pro_code',
            $promotion_program_model->getTable() . '.name as pro_name',
            $promotion_program_model->getTable() . '.status as pro_status',
            $promotion_program_model->getTable() . '.start_date',
            $promotion_program_model->getTable() . '.end_date',
            $promotion_program_model->getTable() . '.promotion_type',
            $promotion_program_model->getTable() . '.default_store',
            $promotion_program_model->getTable() . '.condition_combine',
            $promotion_program_model->getTable() . '.condition_bool',
            $promotion_program_model->getTable() . '.act_type',
            $promotion_program_model->getTable() . '.act_sale_type',
            $promotion_program_model->getTable() . '.act_price',
            $promotion_program_model->getTable() . '.act_approval',
            $promotion_program_model->getTable() . '.group_customer',
            $promotion_program_model->getTable() . '.general_settings',
            $promotion_program_model->getTable() . '.stack_able',
            $promotion_program_model->getTable() . '.multiply',
            $promotion_program_model->getTable() . '.total_user',
            $promotion_program_model->getTable() . '.total_use_customer',
            $promotion_program_model->getTable() . '.coupon_code',
            $promotion_program_model->getTable() . '.need_login',
            $promotion_program_model->getTable() . '.act_not_product_condition',
            $promotion_program_model->getTable() . '.act_not_special_product',
            $promotion_program_model->getTable() . '.act_max_quality',
            $promotion_program_model->getTable() . '.act_not_products',
            $promotion_program_model->getTable() . '.act_categories',
            $promotion_program_model->getTable() . '.act_products',
            $promotion_program_model->getTable() . '.act_quatity',
            $promotion_program_model->getTable() . '.act_quatity_sale',
            $promotion_program_model->getTable() . '.company_id',
            'pc.condition_name',
            'pc.condition_type',
            'pc.condition_include_parent',
            'pc.condition_include_child',
            'pc.condition_input',
            'pc.condition_type_name',
            'pc.multiply_type',
            'pc.item_id',
            'pc.item_code',
        ])
            ->join('promotion_program_conditions as pc', 'pc.promotion_program_id', '=', 'promotion_programs.id')
            ->whereNull('pc.deleted_at')
            ->whereNull($promotion_program_model->getTable() . '.deleted_at')
            ->where($promotion_program_model->getTable() . '.default_store', 'like',
                '%"' . TM::getCurrentStoreId() . '"%')
            ->where($promotion_program_model->getTable() . '.start_date', '<=', date("Y-m-d"))
            ->where($promotion_program_model->getTable() . '.end_date', '>=', date("Y-m-d"))
            ->where($promotion_program_model->getTable() . '.status', '1')
            ->whereIn($promotion_program_model->getTable() . '.promotion_type', [
                PROMOTION_TYPE_AUTO,
                PROMOTION_TYPE_DISCOUNT,
                PROMOTION_TYPE_COMMISSION,
                PROMOTION_TYPE_POINT,
            ])->where('total_user', '>', 'used')
            ->get()->toArray();

        $details = [];
        foreach ($allPromotionDetail as $key => $detail) {
            // Check Group
            $group = !empty($detail['group_customer']) ? json_decode($detail['group_customer'], true) : [];
            if ($group && !in_array(TM::getCurrentGroupId(), $group)) {
                continue;
            }

            // Check total_use_customer >= total_use
            $totalUse = PromotionTotal::model()->select([DB::raw('count(id) as total')])
                ->where('order_customer_id', TM::getCurrentUserId())
                ->where('value', '>', 0)
                ->where('promotion_code', $detail['pro_code'])
                ->where('store_id', TM::getCurrentStoreId())
                ->first();
            if ($totalUse && $totalUse->total >= $detail['total_use_customer']) {
                continue;
            }

            $details[$detail['pro_id']] = $details[$detail['pro_id']] ?? [
                    'pro_id'     => $detail['pro_id'],
                    'pro_order'  => $detail['pro_order'],
                    'pro_code'   => $detail['pro_code'],
                    'pro_name'   => $detail['pro_name'],
                    'pro_status' => $detail['pro_status'],
                    'pro_type'   => $detail['promotion_type'],
                    'start_date' => $detail['start_date'],
                    'end_date'   => $detail['end_date'],

                    'default_store'     => $detail['default_store'],
                    'condition_combine' => $detail['condition_combine'],
                    'condition_bool'    => $detail['condition_bool'],
                    'act_type'          => $detail['act_type'],
                    'act_sale_type'     => $detail['act_sale_type'],
                    'act_price'         => $detail['act_price'],
                    'act_approval'      => $detail['act_approval'],

                    'group_customer'            => $detail['group_customer'],
                    'general_settings'          => $detail['general_settings'],
                    'stack_able'                => $detail['stack_able'],
                    'multiply'                  => $detail['multiply'],
                    'total_user'                => $detail['total_user'],
                    'total_use_customer'        => $detail['total_use_customer'],
                    'coupon_code'               => $detail['coupon_code'],
                    'need_login'                => $detail['need_login'],
                    'act_not_product_condition' => $detail['act_not_product_condition'],
                    'act_not_special_product'   => $detail['act_not_special_product'],
                    'act_max_quality'           => $detail['act_max_quality'],
                    'act_not_products'          => $detail['act_not_products'],
                    'act_categories'            => $detail['act_categories'],
                    'act_products'              => $detail['act_products'],
                    'act_quatity'               => $detail['act_quatity'],
                    'act_quatity_sale'          => $detail['act_quatity_sale'],
                    'company_id'                => $detail['company_id'],
                ];
            $details[$detail['pro_id']]['details'][] = [
                'condition_name'           => $detail['condition_name'],
                'condition_type'           => $detail['condition_type'],
                'condition_include_parent' => $detail['condition_include_parent'],
                'condition_include_child'  => $detail['condition_include_child'],
                'condition_input'          => $detail['condition_input'],
                'condition_type_name'      => $detail['condition_type_name'],
                'multiply_type'            => $detail['multiply_type'],
                'item_id'                  => $detail['item_id'],
                'item_code'                => $detail['item_code'],
            ];
        }

        $promotions = [];
        foreach ($details as $promo) {
            $condition_bool = strtolower($promo['condition_bool']) === 'true' ? true : false;
            if ($promo['condition_combine'] == 'All') {
                $promotions[$promo['pro_id']] = $promo;
                foreach ($promo['details'] as $condition) {
                    // If one condition is not condition bool --> unset promotion
                    $compare_result = $this->getCompareResult($cart, $condition, $promo['pro_id']);
                    if ($compare_result !== $condition_bool) {
                        unset($promotions[$promo['pro_id']]);
                        break;
                    }
                }
            } else {
                foreach ($promo['details'] as $condition) {
                    $compare_result = $this->getCompareResult($cart, $condition);
                    if ($compare_result === $condition_bool) {
                        unset($promotions[$promo['pro_id']]);
                        break;
                    }
                }
            }
        }

        $action_type = [
            "order_sale_off"                    => "Giảm giá giỏ hàng",
            "order_sale_off_range"              => "giảm giá tổng giỏ hàng với phạm vi",
            "sale_off_all_products"             => "Giảm giá tất cả các sản phẩm",
            "sale_off_on_products"              => "Giảm giá sản phẩm cụ thể",
            "sale_off_on_categories"            => "Giảm giá cho tất cả các sản phẩm trong danh mục",
            "sale_off_cheapest"                 => "Giảm giá sản phẩm rẻ nhất",
            "sale_off_expensive"                => "Giảm giá sản phẩm đắt nhất",
            "sale_off_same_kind"                => "Giảm giá sản phẩm cùng loại",
            "sale_off_products_from_conditions" => "Giảm giá các sản phẩm từ \"Tab điều kiện\", nếu bất kỳ",
            "free_shipping"                     => "Áp dụng miễn phí vận chuyển",
            "add_product_cart"                  => "Thêm sản phẩm vào giỏ hàng và áp dụng giảm giá",
            "order_discount"                    => "Chiết khấu trên đơn hàng",
        ];

        $action_sale_type = [
            "fixed"       => "Số tiền cố định",
            "percentage"  => "Tỷ lệ phần trăm",
            "fixed_price" => "Giá cố định (thấp hơn thực tế)",
        ];

        $action_price = "số tiền giảm";
        $totals = $this->usePromotions($cart, $promotions);

        return $totals;
    }

    private function getCompareResult(Cart $cart, $condition, $pro_id = null)
    {
        $condition_type = $condition['condition_type'];
        $condition_name = $condition['condition_name'];
        $condition_input = !empty($condition['condition_input']) ? $condition['condition_input'] : 0;

        $result = null;
        switch ($condition_name) {
            case 'cart_quantity':
                $value = CartDetail::model()->select(DB::raw('sum(quantity) as qty'))->where('cart_id',
                    $cart->id)->first();
                $value = $value->qty ?? 0;
                $result = $this->compare($value, $condition_type, $condition_input);
                break;
            case 'cart_total':
                $value = CartDetail::model()->select(DB::raw('sum(total) as price'))->where('cart_id',
                    $cart->id)->first();
                $value = $value->price ?? 0;
                $result = $this->compare($value, $condition_type, $condition_input);
                break;
            case 'cart_weight':
                // Update soon
                break;
            case 'product_name':
                $value = CartDetail::model()->select(DB::raw('sum(quantity) as qty'))
                    ->where(['cart_id' => $cart->id, 'product_code' => $condition['item_code']])->first();
                $value = $value->qty ?? 0;
                $result = $this->compare($value, $condition_type, $condition_input);
                break;
            case 'customer_name':
                $value = $cart->user_id ?? 0;
                $result = $this->compare($value, 'eq', $condition['item_id']);
                break;
            case 'customer_group':
                $user = User::model()->where('id', $cart->user_id)->first();
                $value = $user->group_id ?? 0;
                $result = $this->compare($value, 'eq', $condition['item_id']);
                break;
            case 'customer_order':
                $order = Order::model()->select(DB::raw('sum(total_price)'))
                    ->where(['customer_id' => $cart->user_id, 'status' => ORDER_STATUS_COMPLETED])->first();
                $value = $order->total_price ?? 0;
                $result = $this->compare($value, $condition_type, $condition_input);
                break;
            case 'customer_reg_date':
                $user = User::model()->where('id', $cart->user_id)->first();
                $value = $user->created_at ?? 0;
                $value = $value ? strtotime(date('Y-m-d', strtotime($value))) : 0;
                $result = $this->compare($value, 'eq', strtotime($condition_input));
                break;
            case 'category_name':
            case 'not_in_category_name':
                $category_ids = [$condition['item_id']];
                $parent = Category::model()->where('id', $category_ids[0])->first();
                $parent_id = $parent->parent_id ?? null;
                $child_ids = Category::model()->where('parent_id', $category_ids[0])->get()->pluck('id')->toArray();

                if ($condition['condition_include_parent']) {
                    $category_ids[] = $parent_id;
                }
                if ($condition['condition_include_child']) {
                    $category_ids = array_merge(array_values($category_ids), $child_ids);
                }

                $cart_detail_model = new CartDetailModel();
                $value = $cart_detail_model->model->select(DB::raw("sum({$cart_detail_model->getTable()}.quantity) as qty"))
                    ->join('products as p', 'p.id', $cart_detail_model->getTable() . ".product_id")
                    ->whereNull('p.deleted_at')
                    ->whereNull($cart_detail_model->getTable() . '.deleted_at')
                    ->where(function ($q) use ($category_ids) {
                        foreach ($category_ids as $category_id) {
                            $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$category_id,%");
                        //   $q->orWhere(DB::raw('concat(\',\', p.category_ids, \',\''), 'like', "%,$category_id,%");
                        }
                    })
                    ->where('cart_id', $cart->id)->first();
                $value = $value->qty ?? 0;
                if ($condition_name == 'not_in_category_name') {
                    $all_value = CartDetail::model()->select(DB::raw('sum(quantity) as qty'))->where('cart_id',
                        $cart->id)->first();
                    $value = ($all_value->qty) ?? 0 - $value;
                }

                $result = $this->compare($value, $condition_type, $condition_input);
                break;
            case 'day':
                $value = (int)date('N');
                $result = $this->compare($value, $condition_type, (int)date('N', strtotime($condition_input)));
                break;
            default:
                throw new \Exception(Message::get("V002", Message::get("type") . " [$condition_name]"));
                break;
        }

        return $result;
    }

    private function compare($value, $condition_type, $condition_input)
    {
        $result = null;
        switch ($condition_type) {
            case "eq": // Equal
                $result = $value === $condition_input;
                break;
            case "neq": // Not Equal
                $result = $value !== $condition_input;
                break;
            case "gtr": // Greater Than
                $result = $value > $condition_input;
                break;
            case "lth": // Less Than
                $result = $value < $condition_input;
                break;
            default:
                throw new \Exception(Message::get("V002", Message::get("type") . " [$condition_type]"));
                break;
        }

        return $result;
    }

    private function usePromotions(Cart $cart, $promotions)
    {
        $unitFormat = [
            PROMOTION_TYPE_AUTO       => 'đ',
            PROMOTION_TYPE_COMMISSION => 'đ',
            PROMOTION_TYPE_DISCOUNT   => 'đ',
            PROMOTION_TYPE_POINT      => 'Diem',
        ];
        $outputTmp = ['code' => 'sub_total', 'title' => 'Tạm tính', 'promotions' => null, 'text' => '', 'value' => 0];
        $outputPromotion = [];
        $outputTotal = ['code' => 'total', 'title' => 'Thành tiền', 'promotions' => null, 'text' => '', 'value' => 0];

        $this->sortPromotion($promotions);
        $cart_details = CartDetail::model()->where('cart_id', $cart->id)->get();
        foreach ($cart_details as $key => $cart_detail) {
            /** @var CartDetail $tmpCart */
            $tmpCart = $cart_detail;
            $tmpCart->promotion_price = 0;
            $tmpCart->promotion_info = json_encode([]);
            $cart_details[$key] = $tmpCart;
            $outputTmp['value'] += $cart_detail->total;
        }

        $promotion_info = [];
        foreach ($promotions as $promotion) {
            $totalReducePride = $this->getReducePriceFromPromotion($cart, $promotion);
            $outputPromotion[] = [
                'code'      => 'promotion',
                'title'     => $promotion['pro_name'],
                'promotion' => [
                    'code' => $promotion['pro_code'],
                ],
                'text'      => number_format($totalReducePride) . " " . $unitFormat[$promotion['pro_type']],
                'value'     => $totalReducePride,
            ];

            $promotion_info[] = [
                'promotion_id'   => $promotion['pro_id'],
                'promotion_code' => $promotion['pro_code'],
                'promotion_name' => $promotion['pro_name'],
                'promotion_type' => $promotion['pro_type'],
                'value'          => $totalReducePride,
                'act_approval'   => $promotion['act_approval'],
                'act_type'       => $promotion['act_type'],
                'promotion_info' => $promotion,
            ];
            if (!in_array($promotion['pro_type'], [
                PROMOTION_TYPE_POINT,
                PROMOTION_TYPE_COMMISSION,
            ])) {
                $outputTotal['value'] += $totalReducePride;
            }
        }

        $cartDetail = CartDetail::model()->where('cart_id', $cart->id)->first();
        if ($cartDetail) {
            $cartDetail->promotion_info = !empty($promotion_info) ? json_encode($promotion_info) : null;
            $cartDetail->promotion_price = $outputTotal['value'] ?? 0;
            $cartDetail->total = !empty($cartDetail->promotion_price) ? ($cartDetail->price - $cartDetail->promotion_price) * $cartDetail->quantity : $cartDetail->price * $cartDetail->quantity;
            $cartDetail->save();

        }

        $outputTotal['value'] = $outputTmp['value'] - $outputTotal['value'];
        $outputTmp['text'] = number_format($outputTmp['value']) . " đ";
        $outputTotal['text'] = number_format($outputTotal['value']) . " đ";
        $output = array_merge([$outputTmp], $outputPromotion, [$outputTotal]);
        return array_values($output);
    }

    private function sortPromotion(&$promotions)
    {
        $afterSort = [];
        foreach ($promotions as $item) {
            $afterSort[$item['pro_order'] . "-" . $item['pro_id']] = $item;
        }
        ksort($afterSort);
        $promotions = array_values($afterSort);
    }

    private function getReducePriceFromPromotion(Cart &$cart, $promotion)
    {
        $arrCart = $cart->toArray();
        $total_price = array_sum(array_column($arrCart['details'], 'total'));
        $reduce_price = 0;
        switch ($promotion['act_type']) {
            case "order_sale_off":
            case "sale_off_all_products":
            case "add_product_cart":
                switch ($promotion['act_sale_type']) {
                    case "fixed":
                        $reduce_price = $promotion['act_price'];
                        break;
                    case "percentage":
                        $reduce_price = $promotion['act_price'] * $total_price / 100;
                        break;
                    case "fixed_price":
                        // $reduce_price = $total_price - $promotion['act_price'];
                        $reduce_price = $promotion['act_price'];
                        break;
                    default:
                        throw new \Exception(Message::get("V002",
                            Message::get("type") . " [{$promotion['act_type']}]"));
                        break;
                }
                break;
            case "order_sale_off_range":

                break;
            case "sale_off_on_products":

                break;
            case "sale_off_on_categories":

                break;
            case "sale_off_cheapest":

                break;
            case "sale_off_expensive":

                break;
            case "sale_off_same_kind":

                break;
            case "sale_off_products_from_conditions":

                break;
            case "free_shipping":
                return 0;
                break;
            case "order_discount":
                switch ($promotion['act_sale_type']) {
                    case "fixed":
                        $reduce_price = $promotion['act_price'];
                        break;
                    case "percentage":
                        $reduce_price = $promotion['act_price'] * $total_price / 100;
                        break;
                    case "fixed_price":
                    //    $reduce_price = $total_price - $promotion['act_price'];
                        $reduce_price = $promotion['act_price'];
                        break;
                    default:
                        throw new \Exception(Message::get("V002",
                            Message::get("type") . " [{$promotion['act_type']}]"));
                        break;
                }
                break;
            case "accumulate_point":
            case "order_commission":
                switch ($promotion['act_sale_type']) {
                    case "fixed":
                        $reduce_price = $promotion['act_price'];
                        break;
                    case "percentage":
                        $reduce_price = $promotion['act_price'] * $total_price / 100;
                        break;
                    default:
                        throw new \Exception(Message::get("V002",
                            Message::get("type") . " [{$promotion['act_type']}]"));
                        break;
                }
                break;
        }

        return $reduce_price;
    }

    public function checkWarehouse($cart)
    {
        $cartDetails = $cart->details;
        if (!empty($cartDetails)) {
            foreach ($cartDetails as $detail) {

                $warehouse = WarehouseDetail::model()->where('product_id', $detail->product_id)->first();
                $product = Product::model()->where('id', $detail->product_id)->first();
                if (empty($warehouse) || empty($product)) {
                    CartDetail::model()->where('id', $detail->id)->delete();
                }
            }
        }
        $cart = Cart::with('details')->where(['id' => $cart->id])->first();

        return $cart;
    }


    public function getSubTotal(Cart $cart) {
        // dd($cart->total_info);
        $total_info = $cart->total_info?? null;
        foreach($total_info as $item) {
            if($item['code'] == 'sub_total') {
                return $item['value'];
            }
        }
    }

    public function updateCart(array $data)
    {
        $cart = Cart::with('details')->findOrFail($data['id']);

        if(!empty($data['coupon_admin'])) {
            if(!empty($data['coupon_admin']['code'])) {
                if($data['coupon_admin']['type'] == 'P' && $data['coupon_admin']['value'] >= 100){
                    throw new \Exception("Giá trị mã voucher không hợp lệ", 400);
                }
                $total_info = $cart->total_info;
                $sub_total = 0;
                foreach($total_info as $ti) {
                    if($ti['code'] == 'sub_total') {
                        $sub_total = $ti['value'];
                        break;
                    }
                }
                if($total_info){
                    if($data['coupon_admin']['type'] == 'F' && $data['coupon_admin']['value'] > $sub_total){
                        throw new \Exception("Giá trị mã voucher không hợp lệ", 400);
                    }
                }
            }      
        }

        $shipping_method_name = ConfigShipping::where('code', $cart->shipping_method_code)->first();
            // dd($shipping_method_name);
        $cart->user_id = $data['user_id'] ?? $cart->user_id;
        $cart->distributor_city_code = $data['distributor_city_code'] ?? $cart->distributor_city_code;
        $cart->distributor_city_name = $data['distributor_city_name'] ?? $cart->distributor_city_name;
        $cart->customer_city_code = $data['customer_city_code'] ?? $cart->customer_city_code;
        $cart->customer_city_name = $data['customer_city_name'] ?? $cart->customer_city_name;
        $cart->distributor_district_code = $data['distributor_district_code'] ?? $cart->distributor_district_code;
        $cart->distributor_district_name = $data['distributor_district_name'] ?? $cart->distributor_district_name;
        $cart->customer_district_code = $data['customer_district_code'] ?? $cart->customer_district_code;
        $cart->customer_district_name = $data['customer_district_name'] ?? $cart->customer_district_name;
        $cart->distributor_ward_code = $data['distributor_ward_code'] ?? $cart->distributor_ward_code;
        $cart->distributor_ward_name = $data['distributor_ward_name'] ?? $cart->distributor_ward_name;
        $cart->customer_ward_code = $data['customer_ward_code'] ?? $cart->customer_ward_code;
        $cart->customer_ward_name = $data['customer_ward_name'] ?? $cart->customer_ward_name;
        $cart->ship_address_latlong = $data['ship_address_latlong'] ?? $cart->ship_address_latlong;
        $cart->description = $data['description'] ?? $cart->description;
        $cart->phone = $data['phone'] ?? $cart->phone;
        $cart->payment_method = $data['payment_method'] ?? $cart->payment_method;
        $cart->payment_method = $data['payment_method'] ?? $cart->payment_method;
        $cart->distributor_id = $data['distributor_id'] ?? $cart->distributor_id;
        $cart->distributor_name = $data['distributor_name'] ?? $cart->distributor_name;
        $cart->distributor_code = $data['distributor_code'] ?? $cart->distributor_code;
        $cart->distributor_phone = $data['distributor_phone'] ?? $cart->distributor_phone;
        $cart->distributor_postcode = $data['distributor_postcode'] ?? $cart->distributor_postcode;
        $cart->distributor_long = $data['distributor_long'] ?? $cart->distributor_long;
        $cart->distributor_lat = $data['distributor_lat'] ?? $cart->distributor_lat;
        // $cart->distributor_full_address = $data['distributor_full_address'] ?? $cart->distributor_full_address;
        
        $cart->receiving_time = $data['receiving_time'] ?? $cart->receiving_time;
        $cart->voucher_discount_code = $data['voucher_discount_code'] ?? $cart->voucher_discount_code;
        $cart->voucher_code = $data['voucher_code'] ?? $cart->voucher_code;
        $cart->voucher_code = $data['voucher_code'] ?? $cart->voucher_code;
        $cart->voucher_value = $data['voucher_value'] ?? $cart->voucher_value;
        $cart->voucher_title = $data['voucher_title'] ?? $cart->voucher_title;
        $cart->voucher_discount = $data['voucher_discount'] ?? $cart->voucher_discount;
        $cart->voucher_value_use = $data['voucher_value_use'] ?? $cart->voucher_value_use;
        $cart->coupon_discount_code = $data['coupon_discount_code'] ?? $cart->coupon_discount_code;
        $cart->coupon_code = $data['coupon_code'] ?? $cart->coupon_code;
        $cart->coupon_price = $data['coupon_price'] ?? $cart->coupon_price;
        $cart->coupon_name = $data['coupon_name'] ?? $cart->coupon_name;
        $cart->delivery_discount_code = $data['delivery_discount_code'] ?? $cart->delivery_discount_code;
        $cart->coupon_delivery_code = $data['coupon_delivery_code'] ?? $cart->coupon_delivery_code;
        $cart->coupon_delivery_price = $data['coupon_delivery_price'] ?? $cart->coupon_delivery_price;
        $cart->coupon_delivery_name = $data['coupon_delivery_name'] ?? $cart->coupon_delivery_name;
        $cart->promocode_code = $data['promocode_code'] ?? $cart->promocode_code;
        $cart->promocode_price = $data['promocode_price'] ?? $cart->promocode_price;
        $cart->promocode_name = $data['promocode_name'] ?? $cart->promocode_name;
        $cart->shipping_address_id = $data['shipping_address_id'] ?? $cart->shipping_address_id;
        $cart->shipping_method = $data['shipping_method'] ?? $cart->shipping_method;
        $cart->shipping_method_code = $data['shipping_method_code'] ?? $cart->shipping_method_code;
        $cart->shipping_method_name = $data['shipping_method_name'] ?? ($cart->shipping_method_name ?? ($shipping_method_name->shipping_partner_name ?? null ));
        // $cart->shipping_service = $data['shipping_service'] ? $data['shipping_service'] : ($shipping_method_name->shipping_partner_code == 'VIETTELPOST' ? "NCOD":  ($shipping_method_name->shipping_partner_code == 'GRAB' ? 'INSTANT' : null));
        $cart->shipping_service = !empty($shipping_method_name) ?  $shipping_method_name->shipping_partner_code == 'VIETTELPOST' ? "NCOD":  ($shipping_method_name->shipping_partner_code == 'GRAB' ? 'INSTANT' :  $cart->shipping_service) : null;
        $cart->shipping_diff = $data['shipping_diff'] ?? $cart->shipping_diff;
        $cart->service_name = $data['service_name'] ?? $cart->service_name;
        $cart->extra_service = $data['extra_service'] ?? $cart->extra_service;
        $cart->ship_fee = $data['ship_fee'] ?? $cart->ship_fee;
        $cart->ship_fee_down = $data['ship_fee_down'] ?? $cart->ship_fee_down;
        $cart->is_freeship = $data['is_freeship'] ?? $cart->is_freeship;
        $cart->estimated_deliver_time = $data['estimated_deliver_time'] ?? $cart->estimated_deliver_time;
        $cart->lading_method = $data['lading_method'] ?? $cart->lading_method;
        $cart->full_name = $data['full_name'] ?? $cart->full_name;
        $cart->order_source = $data['order_source'] ?? $cart->order_source;
        $cart->seller_code = $data['seller_code'] ?? $cart->seller_code;
        $cart->seller_id = $data['seller_id'] ?? $cart->seller_id;
        $cart->seller_name = $data['seller_name'] ?? $cart->seller_name;
        $cart->street_address = $data['street_address'] ?? $cart->seller_name;
        $address                     = !empty($data['street_address']) ? $data['street_address']. ", " . $data['customer_ward_name'] . ", " . $data['customer_district_name'] . ", " . $data['customer_city_name'] : null;
        $cart->address               = $address;

        $cart->discount_admin_input_type = $data['discount_admin_input_type'] ?? $cart->discount_admin_input_type;
        $cart->discount_admin_input = $data['discount_admin_input'] ?? $cart->discount_admin_input;

        if(!empty($data['coupon_admin'])) {
            if(!empty($data['coupon_admin']['code'])) {
                $cart->coupon_admin = json_encode($data['coupon_admin']);
            }else{
                $cart->coupon_admin = "{}";
            }        
        }
        if(!empty($data['details'])){
            foreach($data['details'] as $detail){
                $cart_detail = CartDetail::findOrFail($detail['id']);
                $cart_detail->item_type =  $detail['item_type'];
                $cart_detail->item_value =  $detail['item_value']; 
                $total_dis = 0;
                $price_product = $cart_detail->price;
                if($cart_detail->item_type == 'percentage'){
                    if($cart_detail->item_value > 100){
                        throw new \Exception("Giá trị giảm không hợp lệ");
                    }
                    $price_discount = ($price_product * $cart_detail->item_value) / 100;
                    $total_dis = $price_product - $price_discount;
                }
                if($cart_detail->item_type == 'money'){
                    if($cart_detail->item_value > $price_product){
                        throw new \Exception("Giá trị giảm không hợp lệ");
                    }
                    $price_discount = $cart_detail->item_value;
                    $total_dis = $price_product - $price_discount;
                }
                $cart_detail->total = $total_dis;
                $cart_detail->discount_admin_value = $price_discount;
                $cart_detail->save();   
            }
        }
        $cart->save();
    }
}