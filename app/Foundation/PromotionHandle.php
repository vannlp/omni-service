<?php

namespace App\Foundation;

use App\Cart;
use App\Coupon;
use App\CartDetail;
use App\Category;
use App\Order;
use App\OrderDetail;
use App\Setting;
use App\Product;
use App\Promocode;
use App\PromotionProgram;
use App\PromotionProgramCondition;
use App\PromotionTotal;
use App\Supports\DataUser;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\CartDetailModel;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Monolog\Handler\TelegramBotHandler;

class PromotionHandle
{
    const UNIT_FORMAT
    = [
        PROMOTION_TYPE_AUTO       => 'đ',
        PROMOTION_TYPE_CODE       => 'đ',
        PROMOTION_TYPE_COMMISSION => 'đ',
        PROMOTION_TYPE_DISCOUNT   => 'đ',
        PROMOTION_TYPE_POINT      => 'điểm',
        PROMOTION_TYPE_FLASH_SALE => 'đ',
    ];

    const TYPE_USING_PRODUCT
    = [
        'sale_off_all_products',
        'sale_off_on_products',
        'sale_off_on_categories',
        'sale_off_on_manufacturers',
    ];

    const TYPE_USING_ORDER
    = [
        'order_sale_off',
        'sale_off_cheapest',
        'sale_off_expensive',
        'sale_off_same_kind',
        'free_shipping',
        'add_product_cart',
        'order_discount',
        'accumulate_point',
        'add_product_cart',
        'buy_x_get_y',
        'combo',
        'last_buy'
    ];

    /**
     * @var $promotion
     */
    protected $promotion;

    /**
     * @var array $cartPromotionInfo
     */
    protected $cartPromotionInfo = [];

    /**
     * @var array $cartTotalInfo
     */
    protected $cartTotalInfo = [];


    protected $freeItem = [];

    /**
     * @var int $cartSubTotal
     */
    protected $cartSubTotal = 0;

    /**
     * @var int $cartTotal
     */
    protected $cartTotal = 0;


    protected $cartSubTotalTmp = 0;

    /**
     * Get promotion
     *
     * @return mixed
     */
    public function getPromotion()
    {
        return $this->promotion;
    }

    /**
     * Set promotion
     *
     * @param PromotionProgram $promotion
     */
    public function setPromotion(PromotionProgram $promotion)
    {
        $this->promotion = $promotion;
    }

    /**
     * Get promotion by act type
     *
     * @param array $actType
     * @param null $companyId
     * @return mixed
     */
    public function getPromotionByActType(array $actType, $companyId = null, $group_id = null)
    {
        $companyId = $companyId ?? TM::getCurrentCompanyId();
        $date      = date('Y-m-d H:i:s', time());
        return PromotionProgram::with('conditions')
            ->whereRaw("'{$date}' BETWEEN start_date AND end_date")
            ->where('status', 1)
            ->where('total_user', '>', 'used')
            ->where('company_id', $companyId)
            ->whereIn('act_type', $actType)
            ->orderBy('sort_order')

            ->where(function ($q) use ($group_id) {
                $q->where('group_customer', "[]");
                $q->orWhere(
                    [
                        [DB::raw("CONCAT(',',group_customer,',')"), 'like', '%"' . ($group_id ?? DataUser::getInstance()->groupId) . '"%'],
                    ]
                );
            })

            ->where(function ($q) {
                $q->doesntHave('productPromotion');
                $q->orWhereHas('productPromotion', function ($query) {
                    $query->whereNull('city_code_promotion');
                    $query->orWhere(function ($a) {
                        $a->where('city_code_promotion', ($this->cart->customer_city_code ?? TM::getCurrentCityCode()))->whereNull('district_code_promotion');
                    });
                    $query->orWhere(function ($b) {
                        $b->where('city_code_promotion', ($this->cart->customer_city_code ?? TM::getCurrentCityCode()))->where('district_code_promotion', ($this->cart->customer_district_code ?? TM::getCurrentDistrictCode()));
                    });
                });
            })

            ->get();
    }

    

    private function createPromotionInfo($promotion, $promotionValue)
    {
        return [
            'id'             => $promotion->id,
            'code'           => $promotion->code,
            'name'           => $promotion->name,
            'promotion_type' => $promotion->promotion_type,
            'act_approval'   => $promotion->act_approval,
            'act_type'       => $promotion->act_type,
            'act_sale_type'  => $promotion->act_sale_type,
            'act_price'      => $promotion->act_price,
            'value'          => $promotionValue
        ];
    }

    public function promotionClientApplyCart(Cart $cart, $company_id)
    {
        $this->cart = $cart;
        $cart->saving = null;
        $saving = 0;
        $cart->free_item = [];
        foreach ($cart->details as $key => $detail) {

            if (empty($cart->coupon_discount_code)) {
                $detail->coupon_apply = null;
            }

            $detail->promotion_check = null;
            $detail->promotion_price = 0;
            if (empty($detail->product)) {
                continue;
            }
            $productPrice = Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price);
            // $productPrice  = Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price);
            $detail->total = $detail->quantity * $productPrice;
            $detail->price = $productPrice;
            $detail->save();
        }
        $this->cartSubTotal = $cart->details->sum('total');

        $this->cartTotalInfo[] = [
            'code'  => 'sub_total',
            'title' => 'Tổng tiền hàng',
            'text'  => number_format(round($this->cartSubTotal)) . ' đ',
            'value' => round($this->cartSubTotal)
        ];
        if (!empty($cart->ship_fee)) {
            $this->cartSubTotal = $this->cartSubTotal + $cart->ship_fee;
            $this->cartTotalInfo[] = [
                'code'  => 'fee_ship',
                'title' => 'Phí vận chuyển',
                'text'  => number_format($cart->ship_fee) . ' đ',
                'value' => $cart->ship_fee ?? 0
            ];
        }
        

        //        $this->cartSubTotalTmp  = $this->cartSubTotal;
        $promotionsUsingProduct = $this->getPromotionByActType(self::TYPE_USING_PRODUCT, $company_id);
        if (!$promotionsUsingProduct->isEmpty()) {
            foreach ($cart->details as $detail) {
                if ($detail->coupon_apply == 1) {
                    continue;
                }
                if (empty($detail->product)) {
                    continue;
                }
                $promotionsProduct = $this->promotionApplyProduct($promotionsUsingProduct, $detail->product);

                if (!$promotionsProduct->isEmpty()) {

                    $type_promotion = array_pluck($promotionsProduct, 'promotion_type');
                    $check_flash_sale = array_search('FLASH_SALE', $type_promotion);

                    foreach ($promotionsProduct as $promotion) {
                        $qty_sale = 0;
                        if (is_numeric($check_flash_sale) && $promotion->promotion_type != 'FLASH_SALE') {
                            continue;
                        }
                        $this->setPromotion($promotion);
                        if ($promotion->promotion_type == 'FLASH_SALE') {
                            $order_sale = OrderDetail::model()
                                ->join('orders', 'orders.id', 'order_details.order_id')
                                ->whereRaw("order_details.created_at BETWEEN '$promotion->start_date' AND '$promotion->end_date'")
                                ->where('orders.status', '!=', 'CANCELED')
                                ->where('order_details.product_code', $detail->product->code)
                                ->groupBy('order_details.product_id')
                                ->sum('order_details.qty');
                            if ($order_sale >= ($detail->product->qty_flash_sale ?? 0)) {
                                $promotionValue = 0;
                            } else {
                                if ($promotion->discount_by == "product") {
                                    $promotionValue = $this->parsePriceByProducts($detail->product_code, Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                                }
                                if ($promotion->discount_by != "product") {
                                    $promotionValue = $this->parsePriceBySaleType(Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                                }

                                $saving += $promotionValue * $detail->quantity;
                            }
                            if (!empty($order_sale) <= ($detail->product->qty_flash_sale ?? 0)) {
                                $qty_sale_re = ($detail->product->qty_flash_sale ?? 0) -  $order_sale;
                            }
                            if (!empty($qty_sale_re)) {
                                if ($detail->quantity >= $qty_sale_re) {
                                    $qty_sale = $qty_sale_re;
                                    $detail->qty_sale_re = $qty_sale_re;
                                    $detail->qty_not_sale = $detail->quantity - $qty_sale_re;
                                }
                                if ($detail->quantity < $qty_sale_re) {
                                    $detail->qty_sale_re = null;
                                    $detail->qty_not_sale = null;
                                }
                            }
                        } else {
                            if ($promotion->discount_by == "product") {
                                $promotionValue = $this->parsePriceByProducts($detail->product_code, Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                            }
                            if ($promotion->discount_by != "product") {
                                $promotionValue = $this->parsePriceBySaleType(Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                            }
                            $saving += $promotionValue * $detail->quantity;
                        }
                        $detail->promotion_price = $promotionValue;
                        if (!empty($promotionValue) && $promotionValue > 0) {
                            $detail->promotion_check = 1;
                        }
                        $promotionValueTmp       = $promotionValue * ($qty_sale > 0 ? $qty_sale : $detail->quantity);
                        $detail->total           = $detail->total - $promotionValueTmp;
                        $detail->promotion_info  = $this->createPromotionInfo($promotion, $promotionValueTmp);
                        $detail->save();
                        $this->cartSubTotal -= $promotionValue * ($qty_sale > 0 ? $qty_sale : $detail->quantity);

                        $value = $promotionValueTmp;

                        if (empty($this->cartTotalInfo)) {
                            $this->cartTotalInfo[] = [
                                'code'     => $promotion->code,
                                'title'    => $promotion->name,
                                'act_type' => $promotion->act_type,
                                'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                'value'    => $value
                            ];
                        } else {
                            foreach ($this->cartTotalInfo as $key => $ctkm) {
                                if ($promotion->code == $ctkm['code']) {
                                    $check = 1;
                                    $this->cartTotalInfo[$key] = array_merge(
                                        $this->cartTotalInfo[$key],
                                        [
                                            'value' => $this->cartTotalInfo[$key]['value'] += $promotionValue * ($qty_sale > 0 ? $qty_sale : $detail->quantity),
                                            'text'        =>  '-' . number_format($this->cartTotalInfo[$key]['value']) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO]
                                        ]
                                    );
                                    break;
                                } else {
                                    $check = 0;
                                }
                            }
                            if ($check != 1) {
                                $this->cartTotalInfo[] = [
                                    'code'     => $promotion->code,
                                    'title'    => $promotion->name,
                                    'act_type' => $promotion->act_type,
                                    'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                    'value'    => $value
                                ];
                            }
                        }

                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo(
                                $promotion,
                                $promotionValueTmp
                            );
                        } else {
                            $this->cartPromotionInfo[$promotion->code] = array_merge(
                                $this->cartPromotionInfo[$promotion->code],
                                [
                                    'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $promotionValue * $detail->quantity
                                ]
                            );
                        }
                    }
                }
            }
        }
        $this->cartTotal = $this->cartSubTotal;
        $subtotal = $this->cartSubTotal;
        $promotions = $this->getPromotionByActType(self::TYPE_USING_ORDER);

        if ($promotions->isEmpty()) {

            $cart->saving = ($saving ?? 0);

            $this->cartTotalInfo[] = [
                'code'  => 'total',
                'title' => 'Tổng thanh toán',
                'text'  => number_format(round($subtotal)) . ' đ',
                'value' => round($subtotal)
            ];


            $cart->fill([
                'total_info'     => $this->cartTotalInfo,
                'promotion_info' => array_values($this->cartPromotionInfo)
            ]);
            $cart->save();

            return $promotions;
        }

        foreach ($promotions as $key => $promotion) {

            $this->setPromotion($promotion);

            if (!empty($promotion->group_customer)) {
                $groupCustomer = json_decode($promotion->group_customer, true);

                if (!empty($groupCustomer) && !in_array(DataUser::getInstance()->groupId, $groupCustomer)) {
                    unset($promotions[$key]);
                    continue;
                }
            }

            // if (!empty($promotion->area_ids) && !empty(TM::getCurrentCityCode())) {

            //     $areaGroup = json_decode($promotion->area_ids, true);

            //     if (!empty($groupCustomer) && !in_array(TM::getCurrentCityCode(), $areaGroup)) {
            //         unset($promotions[$key]);
            //         continue;
            //     }

            // }


            if (!empty($promotion->area_ids) && $promotion->area_ids != "[]" && !empty($cart->distributor_city_code)) {

                $areaGroup = json_decode($promotion->area_ids, true);

                if (!empty($groupCustomer) && !in_array($cart->distributor_city_code, $areaGroup)) {
                    unset($promotions[$key]);
                    continue;
                }
            }

            $conditionBool = strtolower($promotion->condition_bool) === 'true';

            if ($promotion->condition_combine == 'All') {
                if (!empty($promotion->conditions) && !$promotion->conditions->isEmpty()) {
                    foreach ($promotion->conditions as $condition) {
                        if ($conditionBool !== $this->checkConditionApplyCart($cart, $condition)) {
                            unset($promotions[$key]);
                            break;
                        }
                    }
                }
            } else {
                if (!empty($promotion->conditions) && !$promotion->conditions->isEmpty()) {
                    $flag = false;
                    foreach ($promotion->conditions as $condition) {
                        if ($conditionBool == $this->checkConditionApplyCart($cart, $condition)) {
                            $flag = true;
                            break;
                        }
                    }

                    if ($flag == false) {
                        unset($promotions[$key]);
                    }
                }
            }
        }
        if (!$promotions->isEmpty()) {
            foreach ($promotions as $key => $promotion) {
                $this->promotion = $promotion;

                switch ($promotion->act_price) {
                    case 'free_shipping':
                        $this->cartTotalInfo[] = [
                            'code'     => $promotion->code,
                            'title'    => $promotion->name,
                            'act_type' => $promotion->act_type,
                            'text'     => 'Miễn phí ship',
                            'value'    => ''
                        ];
                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, '');
                        }
                        break;
                    default:
                        switch ($promotion->act_type) {
                            case 'accumulate_point':
                                $value                 = $this->parsePriceBySaleType($this->cartSubTotal);
                                $this->cartTotalInfo[] = [
                                    'code'     => $promotion->code,
                                    'title'    => $promotion->name,
                                    'act_type' => $promotion->act_type,
                                    'text'     => number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                    'value'    => $value
                                ];
                                if (empty($this->cartPromotionInfo[$promotion->code])) {
                                    $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                } else {
                                    $this->cartPromotionInfo[$promotion->code] = array_merge(
                                        $this->cartPromotionInfo[$promotion->code],
                                        [
                                            'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                        ]
                                    );
                                }
                                break;
                            case 'buy_x_get_y':
                                foreach ($cart->details as $detail) {

                                    // if ($detail->exchange == "yes") {
                                    //     $value                 = $promotion->act_price;
                                    //     $this->cartTotal       -= $value;
                                    //     $this->cartTotalInfo[] = [
                                    //         'code'     => $promotion->code,
                                    //         'title'    => $promotion->name,
                                    //         'act_type' => $promotion->act_type,
                                    //         'text'     => number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                    //         'value'    => $value
                                    //     ];
                                    //     if (empty($this->cartPromotionInfo[$promotion->code])) {
                                    //         $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                    //     } else {
                                    //         $this->cartPromotionInfo[$promotion->code] = array_merge(
                                    //             $this->cartPromotionInfo[$promotion->code],
                                    //             [
                                    //                 'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                    //             ]
                                    //         );
                                    //     }
                                    // }
                                    if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                        continue;
                                    }

                                    $total_product_multipy = 0;
                                    $total_quantity = 0;
                                    if ($promotion->multiply == 'yes') {

                                        foreach ($promotion->conditions as $i => $condition) {
                                            foreach ($cart->details as $detail) {
                                                if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                                    continue;
                                                }
                                                if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                    $detail->promotion_check = 1;
                                                    $total_quantity += $detail->quantity;
                                                    $total_product_multipy += $detail->total;
                                                }
                                            }
                                            $conditionInput = !empty($condition->condition_input) ? $condition->condition_input : 1;
                                            if ($condition->multiply_type == 'quantity') {
                                                $conditionInput = $conditionInput == 0 ? 1 : $conditionInput;
                                                $tmp            = $total_quantity / $conditionInput;
                                                break;
                                            }

                                            if ($condition->multiply_type == 'total') {
                                                $tmp            = $total_product_multipy / $conditionInput;
                                                break;
                                            }
                                        }
                                        if (!empty($promotion->act_products_gift) && $promotion->act_products_gift != "[]") {
                                            if (empty($this->freeItem)) {
                                                $prod = json_decode($promotion->act_products_gift);
                                                $this->freeItem[] = [
                                                    'is_exchange' => $promotion->is_exchange,
                                                    'code'        => $promotion->code,
                                                    'title'       => $promotion->name,
                                                    'act_type'    => $promotion->act_type,
                                                    'text'        => $prod,
                                                    'value'       => !empty($tmp) ? floor($tmp) : 1
                                                ];
                                                break;
                                            } else {
                                                foreach ($this->freeItem as $key => $value) {
                                                    if ($promotion->code == $value['code']) {
                                                        $check = 1;
                                                        break;
                                                    } else {
                                                        $check = 0;
                                                    }
                                                }
                                                if ($check != 1) {
                                                    $prod = json_decode($promotion->act_products_gift);
                                                    $this->freeItem[] = [
                                                        'is_exchange' => $promotion->is_exchange,
                                                        'code'        => $promotion->code,
                                                        'title'       => $promotion->name,
                                                        'act_type'    => $promotion->act_type,
                                                        'text'        => $prod,
                                                        'value'       => !empty($tmp) ? floor($tmp) : 1
                                                    ];
                                                }
                                            }
                                        }
                                    } else {
                                        if (!empty($promotion->act_products_gift) && $promotion->act_products_gift != "[]") {
                                            foreach ($cart->details as $detail) {
                                                if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                                    continue;
                                                }
                                                if (!empty($condition)) {
                                                    if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                        $detail->promotion_check = 1;
                                                    }
                                                }
                                            }
                                            if (empty($this->freeItem)) {
                                                $prod = json_decode($promotion->act_products_gift);
                                                $this->freeItem[] = [
                                                    'is_exchange' => $promotion->is_exchange,
                                                    'code'        => $promotion->code,
                                                    'title'       => $promotion->name,
                                                    'act_type'    => $promotion->act_type,
                                                    'text'        => $prod,
                                                    'value'       => 1
                                                ];
                                                break;
                                            } else {
                                                foreach ($this->freeItem as $key => $value) {
                                                    if ($promotion->code == $value['code']) {
                                                        $check = 1;
                                                        break;
                                                    } else {
                                                        $check = 0;
                                                    }
                                                }
                                                if ($check != 1) {
                                                    $prod = json_decode($promotion->act_products_gift);
                                                    $this->freeItem[] = [
                                                        'is_exchange' => $promotion->is_exchange,
                                                        'code'        => $promotion->code,
                                                        'title'       => $promotion->name,
                                                        'act_type'    => $promotion->act_type,
                                                        'text'        => $prod,
                                                        'value'       => 1
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                                // if (empty($this->cartPromotionInfo[$promotion->code])) {
                                //     $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, 0);
                                // } else {
                                //     $this->cartPromotionInfo[$promotion->code] = array_merge(
                                //         $this->cartPromotionInfo[$promotion->code],
                                //         [
                                //             'value' => $this->cartPromotionInfo[$promotion->code]['value'] += 0
                                //         ]
                                //     );
                                // }
                                if (!empty($promotion->act_price)) {
                                    $value = $this->parsePriceBySaleType($this->cartSubTotal);
                                    if ($value > 0) {
                                        $this->cartTotal       -= $value;
                                        if ($value > $subtotal) {
                                            $value = $subtotal;
                                        }
                                        $subtotal = $subtotal - $value;
                                        $this->cartTotalInfo[] = [
                                            'code'     => $promotion->code,
                                            'title'    => $promotion->name,
                                            'act_type' => $promotion->act_type,
                                            'text'     => '-' . number_format($value * floor($tmp ?? 1)) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                            'value'    => $value * floor($tmp ?? 1)
                                        ];
                                        $saving                += $value;
                                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value * floor($tmp ?? 1));
                                        } else {
                                            $this->cartPromotionInfo[$promotion->code] = array_merge(
                                                $this->cartPromotionInfo[$promotion->code],
                                                [
                                                    'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value * floor($tmp ?? 1)
                                                ]
                                            );
                                        }
                                    }
                                }
                            default:
                                //                                $value                 = $this->parsePriceBySaleType($this->cartSubTotal);
                                $total = 0;
                                $value = 0;
                                $total_promotion_price = 0;
                                if ($promotion->multiply == 'yes') {
                                    foreach ($promotion->conditions as $i => $condition) {
                                        foreach ($cart->details as $detail) {
                                            if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                $total_promotion_price = $detail->promotion_price > 0 ? ($detail->price - $detail->promotion_price) * $detail->quantity : $detail->total;
                                                $conditionInput = !empty($condition->condition_input) ? $condition->condition_input : 1;
                                                $conditionLimit = !empty($condition->condition_limit) ? $condition->condition_limit : $detail->quantity + 1;
                                                if ($detail->quantity >= $conditionLimit) {
                                                    $conditionInput = $conditionInput == 0 ? 1 : $conditionInput;
                                                    $tmp            = $conditionLimit / $conditionInput;
                                                    if ($promotion->act_sale_type == 'percentage') {
                                                        $tmpPrice =  $total_promotion_price * (floor($tmp)) * ($promotion->act_price / 100);
                                                    } else {
                                                        $tmpPrice = $promotion->act_price * (floor($tmp));
                                                    }
                                                    $total += $tmpPrice;
                                                } else {
                                                    if ($detail->quantity > $conditionInput) {
                                                        $conditionInput = $conditionInput == 0 ? 1 : $conditionInput;
                                                        $tmp            = $detail->quantity / $conditionInput;
                                                        if ($promotion->act_sale_type == 'percentage') {
                                                            $tmpPrice =  $total_promotion_price * (floor($tmp)) * ($promotion->act_price / 100);
                                                        } else {
                                                            $tmpPrice = $promotion->act_price * (floor($tmp));
                                                        }
                                                        $total += $tmpPrice;
                                                    }
                                                }
                                                if ($detail->quantity == $conditionInput) {
                                                    if ($promotion->act_sale_type == 'percentage') {
                                                        $total +=  $total_promotion_price * ($promotion->act_price / 100);
                                                    } else {
                                                        $total += $promotion->act_price;
                                                    }
                                                }
                                            }
                                            // else {
                                            //     $value = $this->parsePriceBySaleType($this->cartSubTotal);
                                            // }
                                        }
                                        $value += $total;
                                        $total = 0;
                                    }
                                } else {
                                    $value = $this->parsePriceBySaleType($this->cartSubTotal);
                                }
                                $this->cartTotal       -= $value;
                                $this->cartTotalInfo[] = [
                                    'code'     => $promotion->code,
                                    'title'    => $promotion->name,
                                    'act_type' => $promotion->act_type,
                                    'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                    'value'    => $value
                                ];
                                $saving                += $value;
                                if (empty($this->cartPromotionInfo[$promotion->code])) {
                                    $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                } else {
                                    $this->cartPromotionInfo[$promotion->code] = array_merge(
                                        $this->cartPromotionInfo[$promotion->code],
                                        [
                                            'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                        ]
                                    );
                                }

                                break;
                        }
                }
            }
        }

        $cart->saving = ($saving ?? 0);
        if ($subtotal < 0) {
            $subtotal = 0;
        }
        $this->cartTotalInfo[] = [
            'code'  => 'total',
            'title' => 'Tổng thanh toán',
            'text'  => number_format(round($subtotal)) . ' đ',
            'value' => round($subtotal)
        ];

        //        dd(array_values($this->cartPromotionInfo));
        $cart->fill([
            'free_item'      => $this->freeItem,
            'total_info'     => $this->cartTotalInfo,
            'promotion_info' => array_values($this->cartPromotionInfo)
        ]);
        $cart->save();

        return $cart;
    }

    public function promotionApplyCart(Cart $cart)
    {
        $this->cart = $cart;
        $cart->saving = null;
        $saving = 0;
        $cart->free_item = [];

        foreach ($cart->details as $key => $detail) {

            if (empty($cart->coupon_discount_code)) {
                $detail->coupon_apply = null;
            }

            $detail->promotion_check = null;
            $detail->promotion_price = 0;
            if (empty($detail->product)) {
                continue;
            }
            $productPrice  = Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price);
            $proPrice      = $detail->promotion_price ?? 0;

            // if($detail->discount_admin_value){
            //     $detail->total = ($detail->quantity * $productPrice ) - $detail->discount_admin_value;
            // }else{
                $detail->total = $detail->quantity * $productPrice;
            // }
            $detail->price = $productPrice;
            $detail->save();
        }
        $this->cartSubTotal = $cart->details->sum('total');

        $this->cartTotalInfo[] = [
            'code'  => 'sub_total',
            'title' => 'Tổng tiền hàng',
            'text'  => number_format(round($this->cartSubTotal)) . ' đ',
            'value' => round($this->cartSubTotal)
        ];
        if (!empty($cart->ship_fee)) {
            $this->cartSubTotal = $this->cartSubTotal + $cart->ship_fee;
            $this->cartTotalInfo[] = [
                'code'  => 'fee_ship',
                'title' => 'Phí vận chuyển',
                'text'  => number_format($cart->ship_fee) . ' đ',
                'value' => $cart->ship_fee ?? 0
            ];
        }
        if(!empty($cart->discount_admin_input_type) && $cart->discount_admin_input > 0) {
            if($cart->discount_admin_input_type == DISCOUNT_ADMIN_TYPE_MONEY) {
                $this->cartSubTotal = $this->cartSubTotal - $cart->discount_admin_input;
                $this->cartTotalInfo[] = [
                    'code'  => 'money',
                    'title' => 'Giảm tiền cố định',
                    'text'  => number_format($cart->discount_admin_input) . ' đ',
                    'value' => $cart->discount_admin_input ?? 0
                ];
            }
            if($cart->discount_admin_input_type == DISCOUNT_ADMIN_TYPE_PERCENTAGE) {
                $money_percentage = $this->cartSubTotal * ($cart->discount_admin_input / 100);
                $this->cartSubTotal = $this->cartSubTotal - $money_percentage;
                $this->cartTotalInfo[] = [
                    'code'  => 'percentage',
                    'title' => 'Giảm phần trăm theo đơn hàng',
                    'text'  => number_format($money_percentage) . ' đ',
                    'value' => $money_percentage ?? 0
                ];
            }
        }

        $cart_details = $cart->details;
        $value_product_admin = 0;
        $check = false;
        foreach($cart_details as $cart_detail){
            $total_price_discount = 0;
            if($cart_detail->discount_admin_value){
                $check = true;
                $total_price_discount += $cart_detail->discount_admin_value;
                $value_product_admin += $total_price_discount;     
               
            }
        }

        if($check == true){
            $this->cartSubTotal -= $value_product_admin;
            $this->cartTotalInfo[] = [
                'code'  => 'discount_product_admin',
                'title' => 'Giá trị giảm sản phẩm admin',
                'text'  => number_format($value_product_admin) . ' đ',
                'value' => $value_product_admin
            ];
        }

        $coupon_admin = $cart->coupon_admin ? json_decode($cart->coupon_admin, true): null;
        if($coupon_admin) {
            if(count($coupon_admin) > 0) {
                if($coupon_admin['type'] == 'P'){
                    $price_coupon =  $this->cartSubTotal * ($coupon_admin['value'] / 100);
                    $this->cartSubTotal = $this->cartSubTotal - $price_coupon;
                    $this->cartTotalInfo[] = [
                        'code'  => 'coupon_admin',
                        'title' => 'Giảm giá voucher trên admin',
                        'text'  => number_format($price_coupon) . ' đ',
                        'value' => $price_coupon ?? 0
                    ];
                }
                if($coupon_admin['type'] == 'F'){
                    $price_coupon =  $coupon_admin['value'];
                    $this->cartSubTotal = $this->cartSubTotal - $price_coupon;
                    $this->cartTotalInfo[] = [
                        'code'  => 'coupon_admin',
                        'title' => 'Giảm giá voucher trên admin',
                        'text'  => number_format($price_coupon) . ' đ',
                        'value' => $price_coupon ?? 0
                    ];
                }
            }
        }
        //        $this->cartSubTotalTmp  = $this->cartSubTotal;
        // 
        
        $promotionsUsingProduct = $this->getPromotionByActType(self::TYPE_USING_PRODUCT);
        if($cart->order_channel == "ADMIN") {
            $userCart = User::where('id', $cart->user_id)->first();
            if($userCart){
                $group_id = $userCart->group_id;
                $company_id =  $userCart->company_id;
            }
            $promotionsUsingProduct = $this->getPromotionByActType(self::TYPE_USING_PRODUCT, $company_id, $group_id); 
        }else{
            $promotionsUsingProduct = $this->getPromotionByActType(self::TYPE_USING_PRODUCT); 
        }
        // dd($promotionsUsingProduct->toArray());
        if (!$promotionsUsingProduct->isEmpty()) {
            foreach ($cart->details as $detail) {
                if ($detail->coupon_apply == 1) {
                    continue;
                }
                if (empty($detail->product)) {
                    continue;
                }

                // Check xem sản phẩm nguồn ở đâu (admin, client)
                if($cart->order_channel == "ADMIN") {
                    $userCart = User::where('id', $cart->user_id)->first();
                    if($userCart){
                        $group_id = $userCart->group_id;
                        $company_id =  $userCart->company_id;
                    }
                    $promotionsProduct = $this->promotionApplyProduct($promotionsUsingProduct, $detail->product, $company_id);
                }else{
                    $promotionsProduct = $this->promotionApplyProduct($promotionsUsingProduct, $detail->product);
                }
                if (!$promotionsProduct->isEmpty()) {

                    $type_promotion = array_pluck($promotionsProduct, 'promotion_type');
                    $check_flash_sale = array_search('FLASH_SALE', $type_promotion);

                    foreach ($promotionsProduct as $promotion) {
                        $qty_sale = 0;
                        if (is_numeric($check_flash_sale) && $promotion->promotion_type != 'FLASH_SALE') {
                            continue;
                        }
                        $this->setPromotion($promotion);
                        if ($promotion->promotion_type == 'FLASH_SALE') {
                            $order_sale = OrderDetail::model()
                                ->join('orders', 'orders.id', 'order_details.order_id')
                                ->whereRaw("order_details.created_at BETWEEN '$promotion->start_date' AND '$promotion->end_date'")
                                ->where('orders.status', '!=', 'CANCELED')
                                ->where('order_details.product_code', $detail->product->code)
                                ->groupBy('order_details.product_id')
                                ->sum('order_details.qty');
                            if ($order_sale >= ($detail->product->qty_flash_sale ?? 0)) {
                                $promotionValue = 0;
                            } else {
                                if ($promotion->discount_by == "product") {
                                    $promotionValue = $this->parsePriceByProducts($detail->product_code, Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                                } else {
                                    $promotionValue = $this->parsePriceBySaleType(Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                                }
                                $saving += $promotionValue * $detail->quantity;
                            }
                            if (!empty($order_sale) <= ($detail->product->qty_flash_sale ?? 0)) {
                                $qty_sale_re = ($detail->product->qty_flash_sale ?? 0) -  $order_sale;
                            }
                            if (!empty($qty_sale_re)) {
                                if ($detail->quantity >= $qty_sale_re) {
                                    $qty_sale = $qty_sale_re;
                                    $detail->qty_sale_re = $qty_sale_re;
                                    $detail->qty_not_sale = $detail->quantity - $qty_sale_re;
                                }
                                if ($detail->quantity < $qty_sale_re) {
                                    $detail->qty_sale_re = null;
                                    $detail->qty_not_sale = null;
                                }
                            }
                        } else {
                            if ($promotion->discount_by == "product") {
                                $promotionValue = $this->parsePriceByProducts($detail->product_code, Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                            } else {
                                $promotionValue = $this->parsePriceBySaleType(Arr::get($detail->product->priceDetail($detail->product), 'price', $detail->product->price));
                            }
                            $saving += $promotionValue * $detail->quantity;
                        }
                        $detail->promotion_price = $promotionValue;
                        if (!empty($promotionValue) && $promotionValue > 0) {
                            $detail->promotion_check = 1;
                        }
                        $promotionValueTmp       = $promotionValue * ($qty_sale > 0 ? $qty_sale : $detail->quantity);
                        $detail->total           = $detail->total - $promotionValueTmp;
                        $detail->promotion_info  = $this->createPromotionInfo($promotion, $promotionValueTmp);
                        $detail->save();
                        $this->cartSubTotal -= $promotionValue * ($qty_sale > 0 ? $qty_sale : $detail->quantity);

                        $value = $promotionValueTmp;

                        if (empty($this->cartTotalInfo) && $value > 0) {
                            $this->cartTotalInfo[] = [
                                'code'     => $promotion->code,
                                'title'    => $promotion->name,
                                'type'     => 'promotion_by_product',
                                'act_type' => $promotion->act_type,
                                'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                'value'    => $value
                            ];
                        } else {
                            foreach ($this->cartTotalInfo as $key => $ctkm) {
                                if ($promotion->code == $ctkm['code']) {
                                    $check = 1;
                                    $this->cartTotalInfo[$key] = array_merge(
                                        $this->cartTotalInfo[$key],
                                        [
                                            'value' => $this->cartTotalInfo[$key]['value'] += $promotionValue * ($qty_sale > 0 ? $qty_sale : $detail->quantity),
                                            'text'        =>  '-' . number_format($this->cartTotalInfo[$key]['value']) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO]
                                        ]
                                    );
                                    break;
                                } else {
                                    $check = 0;
                                }
                            }
                            if ($check != 1) {
                                $this->cartTotalInfo[] = [
                                    'code'     => $promotion->code,
                                    'title'    => $promotion->name, 
                                    'type'     => 'promotion_by_product',
                                    'act_type' => $promotion->act_type,
                                    'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                    'value'    => $value
                                ];
                            }
                        }

                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo(
                                $promotion,
                                $promotionValueTmp
                            );
                        } else {
                            $this->cartPromotionInfo[$promotion->code] = array_merge(
                                $this->cartPromotionInfo[$promotion->code],
                                [
                                    'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $promotionValue * $detail->quantity
                                ]
                            );
                        }
                    }
                }
            }
        }
        $this->cartTotal = $this->cartSubTotal;
        $subtotal = $this->cartSubTotal;

        // Check xem sản phẩm nguồn ở đâu (admin, client)
        if($cart->order_channel == "ADMIN") {
            $userCart = User::where('id', $cart->user_id)->first();
            if($userCart){
                $group_id = $userCart->group_id;
                $company_id =  $userCart->company_id;
            }
            $promotions = $this->getPromotionByActType(self::TYPE_USING_ORDER, $company_id ?? null, $group_id ?? null);

        }else{
            // $promotionsProduct = $this->promotionApplyProduct($promotionsUsingProduct, $detail->product);
            $promotions = $this->getPromotionByActType(self::TYPE_USING_ORDER);

        }

        if ($cart->details->isEmpty()) {
            $cart->removeCoupon();
            $cart->removeCouponDelivery();
            $cart->removeVoucher();
        };

        if (empty($cart->ship_fee)) {
            $cart->removeCouponDelivery();
        }

        $this->validate($cart);

        $now = date('Y-m-d H:i:s');
        if (!empty($cart->coupon_discount_code) || !empty($cart->delivery_discount_code) || !empty($cart->voucher_discount_code)) {
            $this->coupon = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                ->whereIn('coupon_codes.code', [$cart->coupon_discount_code, $cart->delivery_discount_code, $cart->voucher_discount_code])
                ->whereRaw("'{$now}' BETWEEN coupons.date_start AND coupons.date_end")
                ->where('coupons.status', 1)
                ->where(function ($q) use ($now) {
                    $q->whereNull('coupon_codes.user_code')->whereNull('coupon_codes.start_date')->whereNull('coupon_codes.end_date');
                    $q->orWhereNull('coupon_codes.user_code')->whereRaw("'{$now}' BETWEEN coupon_codes.start_date AND coupon_codes.end_date");
                    $q->orWhere('coupon_codes.user_code', TM::getCurrentUserCode())->whereNull('coupon_codes.start_date')->whereNull('coupon_codes.end_date');
                    $q->orWhere('coupon_codes.user_code', TM::getCurrentUserCode())->whereRaw("'{$now}' BETWEEN coupon_codes.start_date AND coupon_codes.end_date");
                })
                ->where('coupon_codes.is_active', 0)
                ->select(
                    'coupon_codes.is_active',
                    'coupons.type_apply',
                    'coupons.apply_discount',
                    'coupons.code',
                    'coupons.name',
                    'coupons.condition',
                    'coupons.id',
                    'coupons.mintotal',
                    'coupons.maxtotal',
                    'coupons.type_discount',
                    'coupon_codes.discount',
                    'coupons.product_ids',
                    'coupons.category_ids',
                    'coupons.category_except_ids',
                    'coupons.product_except_ids',
                    'coupons.uses_total',
                    'coupons.uses_customer'
                )
                ->get();

            $cart->addCoupon($this->coupon);
        }


        if (!empty($cart->coupon_delivery_code)) {

            #Đạt
            try {
                $coupon = Coupon::model()->where(['code'=>$cart->coupon_delivery_code,'type_discount' => 'shipping'])->first();

                if ($coupon) {
                    $discountPrice = 0;
                    if ($coupon->type_discount == 'shipping') {
                        $cart->coupon_delivery_code = $coupon->code;
                        $cart->coupon_delivery_name = $coupon->name;

                        if ($coupon->free_shipping == 1) {
                            $cart->coupon_delivery_price = $cart->ship_fee;
                            $cart->is_freeship = 1;
                        } else {
                            if ($coupon->type === 'P') {
                                $discountPrice = $this->ship_fee * ($coupon->discount / 100);
                                if (!empty($coupon->limit_discount) && $discountPrice >= $coupon->limit_discount) {
                                    $discountPrice = $coupon->limit_discount;
                                    if ($discountPrice >= $cart->ship_fee) {
                                        $discountPrice = $cart->ship_fee;
                                    }
                                }
                            }
                            if ($coupon['type'] != 'P') {
                                $discountPrice = $coupon->discount;
                                if ($discountPrice >= $cart->ship_fee) {
                                    $discountPrice = $cart->ship_fee;
                                }
                            }
                            $cart->coupon_delivery_price = $discountPrice;
                            $cart->is_freeship = 0;
                        }
                    }
    //                    $mess = "[NTF] ID Cart: $cart->id - Giá trị trên cart: $cart->coupon_delivery_price - Giá trị discountPrice: $discountPrice - Giá trị ship_free_down: (ship_fee: $cart->ship_fee - coupon_delivery_price: $cart->coupon_delivery_price) - Thông tin coupon: $coupon->code";
    //                    TM::sendMessage($mess);
                }
            } catch (\Exception $exception){
                TM_Error::handle($exception);
            }

            $cart->ship_fee_down = ($cart->ship_fee ?? 0) - ($cart->coupon_delivery_price ?? 0);
            $subtotal = $subtotal - $cart->coupon_delivery_price;
            $this->cartTotalInfo[] = [
                'code'  => 'coupon_delivery',
                'title' => $cart->coupon_delivery_name,
                'text'  => $cart->coupon_delivery_price != 0 ? '-' . number_format(round($cart->coupon_delivery_price)) . ' đ' : '0 đ',
                'value' => round($cart->coupon_delivery_price)
            ];
        } else {
            $cart->ship_fee_down = ($cart->ship_fee ?? 0);
        }

        if (!empty($cart->coupon_code) && $subtotal > 0) {
            if ($cart->coupon_price > $subtotal) {
                $coupon_price = $subtotal;
            }
            // $cart->coupon_code_use = ($coupon_code ?? $cart->coupon_code);
            $checkTypeCoupon = DB::table('coupon_codes')->where('code',$cart->coupon_code)->first();
            // dd($checkTypeCoupon);
            // dd($checkTypeCoupon);
            if(!empty($checkTypeCoupon) && $checkTypeCoupon->type == "P"){
                $coupon_price                = $subtotal * ($cart->coupon_price /100);
                $subtotal = $subtotal - $coupon_price;
            }
            if(!empty($checkTypeCoupon) &&  $checkTypeCoupon->type == "F"){
                $subtotal                = $subtotal - ($coupon_price ?? $cart->coupon_price);
            }
            $this->cartTotalInfo[]   = [
                'code'  => $cart->coupon_code,
              // 'code'  => 'coupon',
                'title' => $cart->coupon_name,
                'text'  => '-' . number_format(round(($coupon_price ?? $cart->coupon_price))) . ' đ',
                'value' => round(($coupon_price ?? $cart->coupon_price))
            ];
        } else if (!empty($cart->coupon_code) && $subtotal < 0) {
            $cart->removeCoupon();
        }

        if (!empty($cart->voucher_code) && $subtotal > 0) {
            if ($cart->voucher_value > $subtotal) {
                $voucher_value = $subtotal;
            }
            $cart->voucher_value_use = ($voucher_value ?? $cart->voucher_value);
            $checkTypeCoupon = DB::table('coupon_codes')->where('code',$cart->voucher_discount_code)->first();
            if($checkTypeCoupon->type == "P"){
                $discountVoucher                = $subtotal * ($voucher_value ?? ($cart->voucher_value /100));
                $subtotal = $subtotal - $discountVoucher;
            }
            if($checkTypeCoupon->type == "F"){
                $subtotal                = $subtotal - ($voucher_value ?? $cart->voucher_value);
            }
            $this->cartTotalInfo[]   = [
                'code'  => 'voucher',
                'title' => $cart->voucher_title,
                'text'  => '-' . number_format(($voucher_value ?? $discountVoucher)) . ' đ',
                'value' => ($voucher_value ?? $discountVoucher)
            ];
        } else if (!empty($cart->voucher_code) && $subtotal < 0) {
            $cart->removeVoucher();
        }


        if ($promotions->isEmpty()) {
            $cart->saving = ($cart->coupon_price ?? 0) + ($cart->coupon_delivery_price ?? 0) + ($saving ?? 0) + ($voucher_value ?? 0);
            if (!empty($input['usepoint']) === 1) {
                $exchangepoint = Setting::model()->where('code', 'EXCHANGEPOINT')->first();
                $data          = json_decode($exchangepoint->data);
                $valueexchange = $data[0]->key; // lay ti le quy doi, diem thanh tien
                $totalpoint    = ($cart->User->point ?? 0) * $valueexchange; // doi diem thanh tien
                if ($totalpoint > $subtotal) {
                    $totalpoint = $subtotal;
                } // lay du so diem can dung
                $subtotal = $subtotal - $totalpoint;
                $cart->point           = $totalpoint / $valueexchange;
                $cart->ex_change_point = $totalpoint;
                $cart->point_use       = $totalpoint;
                $this->cartTotalInfo[] = [
                    'code'  => 'point',
                    'title' => 'Điểm sử dụng',
                    'text'  => number_format($cart->point) . ' đ',
                    'value' => $cart->point
                ];
                if ($subtotal < 0) {
                    $subtotal = 0;
                }
                $this->cartTotalInfo[] = [
                    'code'  => 'total',
                    'title' => 'Tổng thanh toán',
                    'text'  => number_format(round($subtotal)) . ' đ',
                    'value' => round($subtotal)
                ];
            } else {
                if ($subtotal < 0) {
                    $subtotal = 0;
                }
                $cart->point           = 0;
                $cart->ex_change_point = 0;
                $cart->point_use       = 0;
                $this->cartTotalInfo[] = [
                    'code'  => 'total',
                    'title' => 'Tổng thanh toán',
                    'text'  => number_format(round($subtotal )) . ' đ',
                    'value' => round($subtotal )
                ];
            }



            $cart->fill([
                'total_info'     => $this->cartTotalInfo,
                'promotion_info' => array_values($this->cartPromotionInfo)
            ]);
            $cart->save();

            return $promotions;
        }

        foreach ($promotions as $key => $promotion) {

            $this->setPromotion($promotion);
            if (!empty($promotion->group_customer)) {
                $groupCustomer = json_decode($promotion->group_customer, true);

                if (!empty($groupCustomer) && !in_array(DataUser::getInstance()->groupId, $groupCustomer)) {
                    unset($promotions[$key]);
                    continue;
                }
            }

            $user_used = PromotionTotal::model()->where('order_customer_id', TM::getCurrentUserId())->where('promotion_id', $promotion->id)->count();
            if (!empty($user_used) && $promotion->total_user > 0) {
                if ($user_used >= $promotion->total_use_customer) {
                    unset($promotions[$key]);
                    continue;
                }
            }


            if (!empty($promotion->area) && $promotion->area != "[]" && !empty($cart->customer_city_code)) {
                $areas = json_decode($promotion->area, true);
                $flag = false;
                $city = array_pluck($areas, 'code');
                $search_city = array_search($cart->customer_city_code, $city);
                if (is_numeric($search_city)) {
                    if (empty($areas[$search_city]['districts'])) {
                        $flag = true;
                    }
                    if (!empty($areas[$search_city]['districts'] && !empty($cart->customer_district_code))) {
                        if (!empty($areas[$search_city]['districts']['wards']) && !empty($cart->customer_ward_code)) {
                            $ward = array_pluck($areas[$search_city]['districts']['wards'], 'code');
                            $search_ward = array_search($cart->customer_ward_code, $ward);
                            if (is_numeric($search_ward)) {
                                $flag = true;
                            }
                        }
                        if (empty($areas[$search_city]['districts']['wards'])) {
                            $district = array_pluck($areas[$search_city]['districts'], 'code');
                            $search_district = array_search($cart->customer_district_code, $district);
                            if (is_numeric($search_district)) {
                                $flag = true;
                            }
                        }
                    }
                }
                if (!$flag) {
                    unset($promotions[$key]);
                    continue;
                }
            }
            if (!empty($promotion->area) && $promotion->area != "[]" && !empty(TM::getCurrentCityCode())) {
                $areas = json_decode($promotion->area, true);
                $flag = false;
                $city = array_pluck($areas, 'code');
                $search_city = array_search(TM::getCurrentCityCode(), $city);
                if (is_numeric($search_city)) {
                    if (empty($areas[$search_city]['districts'])) {
                        $flag = true;
                    }
                    if (!empty($areas[$search_city]['districts']) && !empty(TM::getCurrentDistrictCode())) {
                        if (!empty($areas[$search_city]['districts']['wards']) && !empty($this->cart->customer_ward_code)) {
                            $ward = array_pluck($areas[$search_city]['districts'], 'code');
                            $search_ward = array_search(TM::getCurrentWardCode(), $ward);
                            if (is_numeric($search_ward)) {
                                $flag = true;
                            }
                        }
                        if (empty($areas[$search_city]['districts']['wards'])) {
                            $district = array_pluck($areas[$search_city]['districts'], 'code');
                            $search_district = array_search(TM::getCurrentDistrictCode(), $district);
                            if (is_numeric($search_district)) {
                                $flag = true;
                            }
                        }
                    }
                }
                if (!$flag) {
                    unset($promotions[$key]);
                    continue;
                }
            }
            if (!empty($promotion->area) && $promotion->area != "[]" && empty($this->cart->customer_city_code) && empty(TM::getCurrentCityCode())) {
                $areas = json_decode($promotion->area, true);
                $areas_code = array_pluck($areas, 'code');
                $flag = false;
                if (!in_array(TM::getCurrentCityCode() ?? 79, $areas_code)) {
                    $flag = true;
                }
                if ($flag) {
                    unset($promotions[$key]);
                    continue;
                }
            }




            $conditionBool = strtolower($promotion->condition_bool) === 'true';

            if ($promotion->condition_combine == 'All') {
                if (!empty($promotion->conditions) && !$promotion->conditions->isEmpty()) {
                    foreach ($promotion->conditions as $i => $condition) {
                        if ($conditionBool != $this->checkConditionApplyCart($cart, $condition)) {
                            unset($promotions[$key]);
                            break;
                        }
                    }
                }
            } else {
                if (!empty($promotion->conditions) && !$promotion->conditions->isEmpty()) {
                    $flag = false;
                    foreach ($promotion->conditions as $condition) {
                        if ($conditionBool == $this->checkConditionApplyCart($cart, $condition)) {
                            $flag = true;
                            break;
                        }
                    }
                    if ($flag == false) {
                        unset($promotions[$key]);
                    }
                }
            }
        }

        if (!$promotions->isEmpty()) {
            $promotions = $this->checkStackAble($promotions);
          foreach ($promotions as $key => $promotion) {
                $this->promotion = $promotion;
                switch ($promotion->act_price) {
                    case 'free_shipping':
                        $this->cartTotalInfo[] = [
                            'code'     => $promotion->code,
                            'title'    => $promotion->name,
                            'act_type' => $promotion->act_type,
                            'text'     => 'Miễn phí ship',
                            'value'    => ''
                        ];
                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, '');
                        }
                        break;
                    default:
                        switch ($promotion->act_type) {
                            case 'accumulate_point':
                                // $value                 = $this->parsePriceBySaleType($this->cartSubTotal);
                                $value                 = $this->parsePriceBySaleType(($subtotal ?? $this->cartSubTotal));
                                $this->cartTotalInfo[] = [
                                    'code'     => $promotion->code,
                                    'title'    => $promotion->name,
                                    'act_type' => $promotion->act_type,
                                    'text'     => number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                    'value'    => $value
                                ];
                                if (empty($this->cartPromotionInfo[$promotion->code])) {
                                    $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                } else {
                                    $this->cartPromotionInfo[$promotion->code] = array_merge(
                                        $this->cartPromotionInfo[$promotion->code],
                                        [
                                            'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                        ]
                                    );
                                }
                                break;
                            case 'buy_x_get_y':

                                foreach ($cart->details as $detail) {
                                    // if ($detail->exchange == "yes") {
                                    //     $value                 = $promotion->act_price;
                                    //     $this->cartTotal       -= $value;
                                    //     $this->cartTotalInfo[] = [
                                    //         'code'     => $promotion->code,
                                    //         'title'    => $promotion->name,
                                    //         'act_type' => $promotion->act_type,
                                    //         'text'     => number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                    //         'value'    => $value
                                    //     ];
                                    //     if (empty($this->cartPromotionInfo[$promotion->code])) {
                                    //         $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                    //     } else {
                                    //         $this->cartPromotionInfo[$promotion->code] = array_merge(
                                    //             $this->cartPromotionInfo[$promotion->code],
                                    //             [
                                    //                 'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                    //             ]
                                    //         );
                                    //     }
                                    // }
                                    if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                        continue;
                                    }

                                    $total_product_multipy = 0;
                                    $total_quantity = 0;
                                    if ($promotion->multiply == 'yes') {

                                        foreach ($promotion->conditions as $i => $condition) {
                                            foreach ($cart->details as $detail) {
                                                if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                                    continue;
                                                }
                                                if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                    $detail->promotion_check = 1;
                                                    $total_quantity += $detail->quantity;
                                                    $total_product_multipy += $detail->total;
                                                }
                                            }
                                            $conditionInput = !empty($condition->condition_input) ? $condition->condition_input : 1;
                                            if ($condition->multiply_type == 'quantity') {
                                                $conditionInput = $conditionInput == 0 ? 1 : $conditionInput;
                                                $tmp            = $total_quantity / $conditionInput;
                                                break;
                                            }

                                            if ($condition->multiply_type == 'total') {
                                                $tmp            = $total_product_multipy / $conditionInput;
                                                break;
                                            }
                                        }
                                        if (!empty($promotion->act_products_gift) && $promotion->act_products_gift != "[]") {
                                            if (empty($this->freeItem)) {
                                                $prod = json_decode($promotion->act_products_gift);
                                                $this->freeItem[] = [
                                                    'is_exchange' => $promotion->is_exchange,
                                                    'code'        => $promotion->code,
                                                    'title'       => $promotion->name,
                                                    'act_type'    => $promotion->act_type,
                                                    'text'        => $prod,
                                                    'value'       => !empty($tmp) ? floor($tmp) : 1
                                                ];
                                                break;
                                            } else {
                                                foreach ($this->freeItem as $key => $value) {
                                                    if ($promotion->code == $value['code']) {
                                                        $check = 1;
                                                        break;
                                                    } else {
                                                        $check = 0;
                                                    }
                                                }
                                                if ($check != 1) {
                                                    $prod = json_decode($promotion->act_products_gift);
                                                    $this->freeItem[] = [
                                                        'is_exchange' => $promotion->is_exchange,
                                                        'code'        => $promotion->code,
                                                        'title'       => $promotion->name,
                                                        'act_type'    => $promotion->act_type,
                                                        'text'        => $prod,
                                                        'value'       => !empty($tmp) ? floor($tmp) : 1
                                                    ];
                                                }
                                            }
                                        }
                                    } else {
                                        if (!empty($promotion->act_products_gift) && $promotion->act_products_gift != "[]") {
                                            foreach ($cart->details as $detail) {
                                                if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                                    continue;
                                                }
                                                if (!empty($condition)) {
                                                    if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                        $detail->promotion_check = 1;
                                                    }
                                                }
                                            }
                                            if (empty($this->freeItem)) {
                                                $prod = json_decode($promotion->act_products_gift);
                                                $this->freeItem[] = [
                                                    'is_exchange' => $promotion->is_exchange,
                                                    'code'        => $promotion->code,
                                                    'title'       => $promotion->name,
                                                    'act_type'    => $promotion->act_type,
                                                    'text'        => $prod,
                                                    'value'       => 1
                                                ];
                                                break;
                                            } else {
                                                foreach ($this->freeItem as $key => $value) {
                                                    if ($promotion->code == $value['code']) {
                                                        $check = 1;
                                                        break;
                                                    } else {
                                                        $check = 0;
                                                    }
                                                }
                                                if ($check != 1) {
                                                    $prod = json_decode($promotion->act_products_gift);
                                                    $this->freeItem[] = [
                                                        'is_exchange' => $promotion->is_exchange,
                                                        'code'        => $promotion->code,
                                                        'title'       => $promotion->name,
                                                        'act_type'    => $promotion->act_type,
                                                        'text'        => $prod,
                                                        'value'       => 1
                                                    ];
                                                }
                                            }
                                        }
                                    }
                                }
                                if (!empty($promotion->act_price)) {
                                    $value = $this->parsePriceBySaleType($this->cartSubTotal);
                                    if ($value > 0) {
                                        $this->cartTotal       -= $value;
                                        if ($value > $subtotal) {
                                            $value = $subtotal;
                                        }
                                        $subtotal = $subtotal - $value;
                                        $this->cartTotalInfo[] = [
                                            'code'     => $promotion->code,
                                            'title'    => $promotion->name,
                                            'act_type' => $promotion->act_type,
                                            'text'     => '-' . number_format($value * floor($tmp ?? 1)) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                            'value'    => $value * floor($tmp ?? 1)
                                        ];
                                        $saving                += $value;
                                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value * floor($tmp ?? 1));
                                        } else {
                                            $this->cartPromotionInfo[$promotion->code] = array_merge(
                                                $this->cartPromotionInfo[$promotion->code],
                                                [
                                                    'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value * floor($tmp ?? 1)
                                                ]
                                            );
                                        }
                                    }
                                }
                                break;
                            case 'combo':
                                foreach (json_decode($promotion->act_gift) as $gift) {
                                    foreach ($cart->details as $detail) {
                                        if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                            continue;
                                        }
                                        //TODO:
                                        $check = 0;
                                        if ($detail->product_code == $gift->product) {
                                            $detail->promotion_check = 1;
                                            if ($promotion->multiply == 'yes') {
                                                $conditionInput = !empty($gift->condition_input) ? $gift->condition_input : 1;
                                                $tmp            = floor($detail->quantity / $conditionInput);
                                                if ($gift->condition == 'quantity') {
                                                    if ($gift->condition_type == 'eq') {
                                                        if ($detail->quantity >= $gift->condition_input) {
                                                            $tmp            = floor($detail->quantity / $conditionInput);
                                                            if($tmp > 0){
                                                                $check = 1;
                                                            }
                                                        }
                                                    }
                                                }
                                            
                                                if ($gift->condition == 'total') {
                                                    if ($detail->total >= $gift->condition_input) {
                                                        $tmp            = floor($detail->total / $conditionInput);
                                                        if ($tmp > 0) {
                                                            $check = 1;
                                                        }
                                                    }
                                                }
                                            } else {
                                                if ($gift->condition == 'quantity') {
                                                    if ($gift->condition_type == 'eq') {
                                                        if ($detail->quantity == $gift->condition_input) {
                                                            $check = 1;
                                                            $tmp = $gift->qty_gift ?? 1;
                                                        }
                                                    }
                                                    if ($gift->condition_type == 'gtr') {
                                                        if ($detail->quantity >= $gift->condition_input) {
                                                            $check = 1;
                                                            $tmp = $gift->qty_gift ?? 1;
                                                        }
                                                    }
                                                }
                                                if ($gift->condition == 'total') {
                                                    if ($detail->total >= $gift->condition_input) {
                                                        $check = 1;
                                                        $tmp = $gift->qty_gift ?? 1;
                                                    }
                                                }
                                            }
                                            if ($check == 1) {
                                                if (!empty($gift->gift) && $gift->gift != "[]") {
                                                    $this->freeItem[] = [
                                                        'is_exchange' => $promotion->is_exchange,
                                                        'code'        => $promotion->code,
                                                        'title'       => $promotion->name,
                                                        'act_type'    => $promotion->act_type,
                                                        'text'        => $gift->gift,
                                                        'value'       => $tmp ?? 1,
                                                    ];
                                                    // dd($this->freeItem,$detail->quantity,$gift->condition_input);
                                                }
                                                if (!empty($gift->act_price)) {
                                                    if ($gift->act_sale_type == "fixed_price") {
                                                        $value = $gift->act_price;
                                                    }
                                                    if ($gift->act_sale_type == "percentage") {
                                                        $value = $detail->total * ($gift->act_price / 100);
                                                    }
                                                    if ($value > 0) {
                                                        $this->cartTotal       -= $value;
                                                        if ($value > $subtotal) {
                                                            $value = $subtotal;
                                                        }
                                                        $subtotal = $subtotal - $value;
                                                        if ($subtotal > 0) {
                                                            $this->cartTotalInfo[] = [
                                                                'code'     => $promotion->code,
                                                                'title'    => $promotion->name,
                                                                'act_type' => $promotion->act_type,
                                                                'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                                                'value'    => $value
                                                            ];
                                                        }
                                                        $saving                += $value;
                                                        if (empty($this->cartPromotionInfo[$promotion->code])) {
                                                            $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                                        } else {
                                                            $this->cartPromotionInfo[$promotion->code] = array_merge(
                                                                $this->cartPromotionInfo[$promotion->code],
                                                                [
                                                                    'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                                                ]
                                                            );
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            case 'last_buy':
                                if (!empty($promotion->act_price)) {
                                    $now          = date('Y-m-d H:i:s');
                                    $last_order = Order::model()->where('customer_id', TM::getCurrentUserId())->where('status', "COMPLETED")->orderBy('created_at', 'desc')->first();
                                    if (!empty($last_order)) {
                                        $check_used = PromotionProgram::model()->where('id', $promotion->id)
                                            ->where(DB::raw("CONCAT(',',order_used,',')"), 'like', "%,$last_order->id,%")->first();

                                        if (empty($check_used)) {
                                            $date_end = date('Y-m-d', strtotime($last_order->created_at . ' + ' . $promotion->act_time . ' days'));

                                            if ($now <= $date_end && !empty($promotion->act_time)) {

                                                if ($promotion->act_sale_type == "fixed_price") {
                                                    $value = $promotion->act_price;
                                                }
                                                if ($promotion->act_sale_type == "percentage") {
                                                    $value = $last_order->total_price * ($promotion->act_price / 100);
                                                }

                                                $this->cartTotal       -= $value;
                                                if ($value > $subtotal) {
                                                    $value = $subtotal;
                                                }
                                                $subtotal = $subtotal - $value;
                                                $this->cartTotalInfo[] = [
                                                    'code'     => $promotion->code,
                                                    'title'    => $promotion->name,
                                                    'act_type' => $promotion->act_type,
                                                    'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[PROMOTION_TYPE_AUTO],
                                                    'value'    => $value
                                                ];
                                                $saving                += $value;
                                                if (empty($this->cartPromotionInfo[$promotion->code])) {
                                                    $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                                } else {
                                                    $this->cartPromotionInfo[$promotion->code] = array_merge(
                                                        $this->cartPromotionInfo[$promotion->code],
                                                        [
                                                            'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                                        ]
                                                    );
                                                }
                                            }
                                        }
                                    }
                                }
                                break;
                            default:
                                $total = 0;
                                $value = 0;
                                $total_promotion_price = 0;
                                $total_product_multipy = 0;
                                $total_quantity = 0;
                                if ($promotion->multiply == 'yes') {
                                    foreach ($promotion->conditions as $i => $condition) {
                                        foreach ($cart->details as $detail) {
                                            if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                                continue;
                                            }
                                            if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                $detail->promotion_check = 1;
                                                $total_product_multipy += $detail->total;
                                                $total_quantity += $detail->quantity;
                                            }
                                        }
                                        foreach ($cart->details as $detail) {
                                            if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                                continue;
                                            }
                                            if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                                $total_promotion_price = $detail->promotion_price > 0 ? ($detail->price - $detail->promotion_price) * $detail->quantity : $detail->total;
                                                $conditionInput = !empty($condition->condition_input) ? $condition->condition_input : 1;
                                                $conditionLimit = !empty($condition->condition_limit) ? $condition->condition_limit : $detail->quantity + 1;
                                                if ($condition->multiply_type == 'quantity') {

                                                    $conditionInput = $conditionInput == 0 ? 1 : $conditionInput;
                                                    if ($total_quantity >= $conditionLimit) {
                                                        $tmp            = $conditionLimit / $conditionInput;
                                                        if ($promotion->act_sale_type == 'percentage') {
                                                            $total = $total_promotion_price * (floor($tmp)) * ($promotion->act_price / 100);
                                                        } else {
                                                            $total = $promotion->act_price * (floor($tmp));
                                                        }
                                                        break;
                                                    } else if ($total_quantity > $conditionInput) {
                                                        $tmp            = $total_quantity / $conditionInput;
                                                        if ($promotion->act_sale_type == 'percentage') {
                                                            $total = $total_promotion_price * (floor($tmp)) * ($promotion->act_price / 100);
                                                        } else {
                                                            $total = $promotion->act_price * (floor($tmp));
                                                        }
                                                        break;
                                                    }
                                                    if ($total_quantity == $conditionInput) {
                                                        if ($promotion->act_sale_type == 'percentage') {
                                                            $total +=  $total_promotion_price * ($promotion->act_price / 100);
                                                        } else {
                                                            $total += $promotion->act_price;
                                                        }
                                                        break;
                                                    }
                                                }
                                                if ($condition->multiply_type == 'total') {
                                                    if ($total_product_multipy >= $conditionInput) {
                                                        $tmp            = $total_product_multipy / $conditionInput;
                                                        if ($promotion->act_sale_type == 'percentage') {
                                                            $total =  $total_promotion_price * (floor($tmp)) * ($promotion->act_price / 100);
                                                        } else {
                                                            $total = $promotion->act_price * floor($tmp);
                                                        }
                                                        break;
                                                    }
                                                }
                                            }
                                            // else {
                                            //     $value = $this->parsePriceBySaleType($this->cartSubTotal);
                                            // }
                                        }
                                        $value += $total;
                                        $total = 0;
                                    }
                                } else {
                                    foreach ($cart->details as $detail) {
                                        if (!$this->checkflashSale($detail->product_id, $detail->product_category)) {
                                            continue;
                                        }
                                        if ($detail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $detail->product_category))) {
                                            $detail->promotion_check = 1;
                                        }
                                    }
                                    $value = $this->parsePriceBySaleType($this->cartSubTotal);
                                }
                                if ($value > 0) {
                                    $this->cartTotal       -= $value;
                                    if ($value > $subtotal) {
                                        $value = $subtotal;
                                    }
                                    $subtotal = $subtotal - $value;
                                    // if ($subtotal > 0) {
                                    //     $this->cartTotalInfo[] = [
                                    //         'code'     => $promotion->code,
                                    //         'title'    => $promotion->name,
                                    //         'act_type' => $promotion->act_type,
                                    //         'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                    //         'value'    => $value
                                    //     ];
                                    // }

                                    $this->cartTotalInfo[] = [
                                        'code'     => $promotion->code,
                                        'title'    => $promotion->name,
                                        'act_type' => $promotion->act_type,
                                        'text'     => '-' . number_format($value) . " " . self::UNIT_FORMAT[$promotion->promotion_type ?? PROMOTION_TYPE_AUTO],
                                        'value'    => $value
                                    ];

                                    $saving                += $value;
                                    if (empty($this->cartPromotionInfo[$promotion->code])) {
                                        $this->cartPromotionInfo[$promotion->code] = $this->createPromotionInfo($promotion, $value);
                                    } else {
                                        $this->cartPromotionInfo[$promotion->code] = array_merge(
                                            $this->cartPromotionInfo[$promotion->code],
                                            [
                                                'value' => $this->cartPromotionInfo[$promotion->code]['value'] += $value
                                            ]
                                        );
                                    }
                                }
                                break;
                        }
                }
            }
        }

        $cart->saving = ($cart->coupon_price ?? 0) + ($cart->coupon_delivery_price ?? 0) + ($saving ?? 0) + ($voucher_value ?? 0);
        $proCateArr   = [];
        foreach (array_values($this->cartPromotionInfo) as $cartPromotionInfo) {
            $promotion_progarams = PromotionProgram::model()->where('id', $cartPromotionInfo['id'])->first();
            $promotionsapplys    = !empty($promotion_progarams->act_not_categories) ? json_decode($promotion_progarams->act_not_categories) : [];
            if (empty($promotionsapplys)) {
                continue;
            }
            $proCates = array_pluck($promotionsapplys, 'category_id', 'category_id');
            foreach ($proCates as $item) {
                $proCateArr[$item] = $item;
            }
            foreach ($cart->details as $key => $detail) {

                $category_product = explode(',', $detail->product_category);
                $check_category   = array_intersect($proCateArr, $category_product);
                if (!empty($check_category)) {
                    $detail->promotion_check = 0;
                } else {
                    $detail->promotion_check = 1;
                }
            }
        }
        //        $this->cartTotalInfo[] = [
        //            'code'  => 'total_discount',
        //            'title' => 'Tổng chiết khấu',
        //            'text'  => number_format($this->cartSubTotal - $this->cartTotal) . ' đ',
        //            'value' => $this->cartSubTotal - $this->cartTotal
        //        ];

        if (!empty($input['usepoint']) === 1) {
            $exchangepoint = Setting::model()->where('code', 'EXCHANGEPOINT')->first();
            $data          = json_decode($exchangepoint->data);
            $valueexchange = $data[0]->key; // lay ti le quy doi, diem thanh tien
            $totalpoint    = ($cart->User->point ?? 0) * $valueexchange; // doi diem thanh tien
            if ($totalpoint > $subtotal) {
                $totalpoint = $subtotal;
            } // lay du so diem can dung
            $subtotal = $subtotal - $totalpoint;
            $cart->point           = $totalpoint / $valueexchange;
            $cart->ex_change_point = $totalpoint;
            $cart->point_use       = $totalpoint;
            $this->cartTotalInfo[] = [
                'code'  => 'point',
                'title' => 'Điểm sử dụng',
                'text'  => number_format($cart->point) . ' đ',
                'value' => $cart->point
            ];
            if ($subtotal < 0) {
                $subtotal = 0;
            }
            $this->cartTotalInfo[] = [
                'code'  => 'total',
                'title' => 'Tổng thanh toán',
                'text'  => number_format(round($subtotal)) . ' đ',
                'value' => round($subtotal)
            ];
        } else {
            if ($subtotal < 0) {
                $subtotal = 0;
            }
            $cart->point           = 0;
            $cart->ex_change_point = 0;
            $cart->point_use       = 0;
            $this->cartTotalInfo[] = [
                'code'  => 'total',
                'title' => 'Tổng thanh toán',
                'text'  => number_format(round($subtotal)) . ' đ',
                'value' => round($subtotal)
            ];
        }

        $cart->fill([
            'free_item'      => $this->freeItem,
            'total_info'     => $this->cartTotalInfo,
            'promotion_info' => array_values($this->cartPromotionInfo)
        ]);


        $cart->save();

        return true;
    }

    
    private function validate($cart)
    {
        $this->cart = Cart::current();
        $now          = date('Y-m-d H:i:s');

        $this->coupon = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
            ->whereIn('coupon_codes.code', [$cart->coupon_discount_code, $cart->delivery_discount_code, $cart->voucher_discount_code])
            // ->whereRaw("'{$now}' BETWEEN coupons.date_start AND coupons.date_end")
            // ->where(function ($q) use ($now) {
            //     $q->whereNull('coupon_codes.user_code')->whereNull('coupon_codes.start_date')->whereNull('coupon_codes.end_date');
            //     $q->orWhere('coupon_codes.user_code')->whereRaw("'{$now}' BETWEEN coupon_codes.start_date AND coupon_codes.end_date");
            //     $q->orWhere('coupon_codes.user_code', TM::getCurrentUserId())->whereNull('coupon_codes.start_date')->whereNull('coupon_codes.end_date');
            //     $q->orWhere('coupon_codes.user_code', TM::getCurrentUserId())->whereRaw("'{$now}' BETWEEN coupon_codes.start_date AND coupon_codes.end_date");
            // })
            ->select(
                'coupon_codes.is_active',
                'coupons.type_apply',
                'coupons.apply_discount',
                'coupons.date_start',
                'coupons.date_end',
                'coupons.code',
                'coupons.name',
                'coupons.status',
                'coupons.condition',
                'coupons.id',
                'coupons.mintotal',
                'coupons.maxtotal',
                'coupons.type_discount',
                'coupon_codes.discount',
                'coupons.product_ids',
                'coupons.category_ids',
                'coupons.category_except_ids',
                'coupons.product_except_ids',
                'coupons.uses_total',
                'coupons.uses_customer',
                'coupon_codes.start_date',
                'coupon_codes.end_date'
            )
            ->get();

        $this->customer = User::where('type', 'CUSTOMER')
            ->find(TM::getCurrentUserId());

        foreach ($this->coupon as $key => $thiscoupon) {

            if((!empty($thiscoupon->start_date) && $thiscoupon->start_date >= $now) || (!empty($thiscoupon->end_date) && $thiscoupon->end_date <= $now)){
                $cart->removeCouponHandle($thiscoupon->type_discount);
            }

            if((!empty($thiscoupon->date_start) && $thiscoupon->date_start >= $now) || (!empty($thiscoupon->date_end) && $thiscoupon->date_end <= $now)){
                $cart->removeCouponHandle($thiscoupon->type_discount);
            }

            // neu chuong trinh tat
            if ($thiscoupon->status == 0) {
                $cart->removeCouponHandle($thiscoupon->type_discount);
            }

            if ($thiscoupon->is_active == 1) {
                $cart->removeCouponHandle($thiscoupon->type_discount);
            }

            if (!$this->cart || $this->cart->details->isEmpty() || !$thiscoupon || !$this->customer || (!empty($thiscoupon->mintotal) && $this->cart->sumCartDetailsPrice($thiscoupon) < $thiscoupon->mintotal) || (!empty($thiscoupon->maxtotal) && $this->cart->sumCartDetailsPrice($thiscoupon) > $thiscoupon->maxtotal) || $thiscoupon->product_ids && !$this->cart->isContaintsCouponProducts($thiscoupon) || $thiscoupon->category_ids && !$this->cart->isContaintsCouponCategories($thiscoupon) || $thiscoupon->product_except_ids && !$this->cart->isContaintsCouponProductsExcept($thiscoupon) || $thiscoupon->category_except_ids && !$this->cart->isContaintsCouponCategoriesExcept($thiscoupon)) {
                $cart->removeCouponHandle($thiscoupon->type_discount);
            }
        }

        return true;
    }

    public function checkflashSale($product_id, $product_category)
    {
        if (!empty($this->product_flashsale_category) || !empty($this->product_flashsale_id)) {

            $CategoryIds =  array_pluck(json_decode($this->product_flashsale_category), 'category_id');
            $ProductIds = array_pluck(json_decode($this->product_flashsale_id), 'product_id');

            $check = array_intersect($CategoryIds, explode(',', $product_category));
            if (in_array($product_id, $ProductIds) || !empty($check)) {
                $product = Product::model()->where('id', $product_id)->first();
                $start = $this->promotion->start_date;
                $end = $this->promotion->end_date;
                $order_sale = OrderDetail::model()
                    ->join('orders', 'orders.id', 'order_details.order_id')
                    ->whereRaw("order_details.created_at BETWEEN '$start' AND '$end'")
                    ->where('orders.status', '!=', 'CANCELED')
                    ->where('order_details.product_code', $product->code)
                    ->groupBy('order_details.product_id')
                    ->sum('order_details.qty');

                if ($order_sale <= $product->qty_flash_sale) {
                    return false;
                }
            }
        }
        return true;
    }


    public function checkStackAble($promotions)
    {
        foreach ($promotions as $promotion) {
            if ($promotion->stack_able == 'no') {
                $id = $promotion->id;
                $check = 1;
                break;
            }
        }
        if (!empty($check) == 1) {
            foreach ($promotions as $key => $promotion) {
                if ($promotion->stack_able == 'yes') {
                    unset($promotions[$key]);
                }
            }
            //     if(count($promotions) >= 2){
            //     $promotions_collect = collect($promotions);
            //     $sort = $promotions_collect->sortBy('sort_order');
            //     foreach ($promotions as $key => $promotion) {
            //         if ($promotion->id != $sort[0]->id) {
            //             unset($promotions[$key]);
            //         }
            //     }
            // }
            foreach ($promotions as $key => $promotion) {
                if ($promotion->id != ($id ?? null)) {
                    unset($promotions[$key]);
                }
            }
        }

        return $promotions;
    }

    public function promotionApplyProduct($promotionsUsingProduct, Product $product, $groupId = null)
    {
        if (!$promotionsUsingProduct instanceof Collection || $promotionsUsingProduct->isEmpty()) {
            return false;
        }

        $result = new Collection();

        $promotionsUsingProduct->each(function ($item) use ($result) {
            $result->push($item);
        });

        foreach ($result as $key => $promotion) {
            if (!empty($promotion->group_customer)) {
                $groupCustomer = json_decode($promotion->group_customer, true);

                if (!empty($groupCustomer) && !in_array($groupId ?? DataUser::getInstance()->groupId, $groupCustomer)) {
                    unset($result[$key]);
                    continue;
                }
            }

            if (!empty($promotion->area) && $promotion->area != "[]" && !empty($this->cart->customer_city_code)) {
                $areas = json_decode($promotion->area, true);
                $flag = false;
                $city = array_pluck($areas, 'code');
                $search_city = array_search($this->cart->customer_city_code, $city);
                if (is_numeric($search_city)) {
                    if (empty($areas[$search_city]['districts'])) {
                        $flag = true;
                    }
                    if (!empty($areas[$search_city]['districts'] && !empty($this->cart->customer_district_code))) {
                        if (!empty($areas[$search_city]['districts']['wards']) && !empty($this->cart->customer_ward_code)) {
                            $ward = array_pluck($areas[$search_city]['districts']['wards'], 'code');
                            $search_ward = array_search($this->cart->customer_ward_code, $ward);
                            if (is_numeric($search_ward)) {
                                $flag = true;
                            }
                        }
                        if (empty($areas[$search_city]['districts']['wards'])) {
                            $district = array_pluck($areas[$search_city]['districts'], 'code');
                            $search_district = array_search($this->cart->customer_district_code, $district);
                            if (is_numeric($search_district)) {
                                $flag = true;
                            }
                        }
                    }
                }
                if (!$flag) {
                    unset($result[$key]);
                    continue;
                }
            }

            if (!empty($promotion->area) && $promotion->area != "[]" && !empty(TM::getCurrentCityCode())) {
                $areas = json_decode($promotion->area, true);
                $flag = false;
                $city = array_pluck($areas, 'code');
                $search_city = array_search(TM::getCurrentCityCode(), $city);
                if (is_numeric($search_city)) {
                    if (empty($areas[$search_city]['districts'])) {
                        $flag = true;
                    }
                    if (!empty($areas[$search_city]['districts']) && !empty(TM::getCurrentDistrictCode())) {
                        if (!empty($areas[$search_city]['districts']['wards']) && !empty($this->cart->customer_ward_code)) {
                            $ward = array_pluck($areas[$search_city]['districts'], 'code');
                            $search_ward = array_search(TM::getCurrentWardCode(), $ward);
                            if (is_numeric($search_ward)) {
                                $flag = true;
                            }
                        }
                        if (empty($areas[$search_city]['districts']['wards'])) {
                            $district = array_pluck($areas[$search_city]['districts'], 'code');
                            $search_district = array_search(TM::getCurrentDistrictCode(), $district);
                            if (is_numeric($search_district)) {
                                $flag = true;
                            }
                        }
                    }
                }
                if (!$flag) {
                    unset($result[$key]);
                    continue;
                }
            }
            if (!empty($promotion->area) && $promotion->area != "[]" && empty($this->cart->customer_city_code) && empty(TM::getCurrentCityCode())) {
                $areas = json_decode($promotion->area, true);
                $areas_code = array_pluck($areas, 'code');
                $flag = false;
                if (!in_array(TM::getCurrentCityCode() ?? 79, $areas_code)) {
                    $flag = true;
                }
                if ($flag) {
                    unset($result[$key]);
                    continue;
                }
            }

            if ($promotion->promotion_type == 'FLASH_SALE') {
                $this->product_flashsale_id = $promotion->act_products ?? [];
                $this->product_flashsale_category = $promotion->act_categories ?? [];
            }

            $conditionBool = strtolower($promotion->condition_bool) === 'true';
            if ($promotion->condition_combine == 'All') {
                if (!empty($promotion->conditions) && !$promotion->conditions->isEmpty()) {
                    foreach ($promotion->conditions as $condition) {
                        if ($conditionBool !== $this->checkConditionApplyProduct($product, $condition)) {
                            unset($result[$key]);
                            break;
                        }
                    }
                }
            }
            if ($promotion->condition_combine != 'All') {
                if (!empty($promotion->conditions) && !$promotion->conditions->isEmpty()) {
                    $flag = false;
                    foreach ($promotion->conditions as $condition) {
                        if ($conditionBool == $this->checkConditionApplyProduct($product, $condition)) {
                            $flag = true;
                            break;
                        }
                    }

                    if ($flag == false) {
                        unset($result[$key]);
                    }
                }
            }
        }

        return $result;
    }

    private function checkConditionApplyProduct(Product $product, $condition)
    {
        $conditionType  = $condition->condition_type;
        $conditionName  = $condition->condition_name;
        $conditionInput = !empty($condition->condition_input) ? $condition->condition_input : 0;

        $result = false;
        switch ($conditionName) {
            case 'product_name':
                $result = $this->compareConditionType($product->id, $conditionType, $condition->item_id);
                break;
            case 'product_group':
                $promotion = PromotionProgram::findOrFail($condition->promotion_program_id);
                $flag = false;
                if ($promotion->act_type == "sale_off_on_products") {
                    $actProd = $promotion->act_products;
                    $prods         = !empty($actProd) ? json_decode($actProd, true) : [];
                    $product_ids = !empty($prods) ? array_pluck($prods, 'product_id') : [];
                    if (in_array($product->id, $product_ids)) {
                        $flag = true;
                    }
                }
                if ($promotion->act_type == "sale_off_on_categories") {
                    $actcate = $promotion->act_categories;
                    $cate         = !empty($actcate) ? json_decode($actcate, true) : [];
                    $category_ids = !empty($cate) ? array_pluck($cate, 'category_id') : [];
                    $check = array_intersect($category_ids, explode(',', $product->category_ids));
                    if (!empty($check)) {
                        $flag = true;
                    }
                }
                $result = $flag;
                break;
            case 'customer_name':
                $value  = TM::getCurrentUserId() ?? 0;
                $result = $this->compareConditionType($value, 'eq', $condition->item_id);
                break;
            case 'customer_group':
                $value  = DataUser::getInstance()->groupId;
                $result = $this->compareConditionType($value ?? 0, 'eq', $condition->item_id);
                break;
            case 'customer_order':
                if (TM::getCurrentUserId()) {
                    $value  = Order::where('customer_id', TM::getCurrentUserId())
                        ->where('status', ORDER_STATUS_COMPLETED)
                        ->sum('total_price');
                    $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                }
                break;
            case 'customer_reg_date':
                if (TM::getCurrentUserId()) {
                    $value  = User::where('id', TM::getCurrentUserId())->value('created_at');
                    $value  = $value ? strtotime(date('Y-m-d', strtotime($value))) : 0;
                    $result = $this->compareConditionType($value, 'eq', strtotime($conditionInput));
                }
                break;
            case 'category_name':
                $categoryId = $condition->item_id;
                if (in_array($categoryId, explode(",", $product->category_ids))) {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
            case 'not_in_category_name':
                $categoryId = $condition->item_id;
                if (in_array($categoryId, explode(",", $product->category_ids))) {
                    $result = false;
                } else {
                    $result = true;
                }
                break;
                // $categoryIds = [$condition->item_id];
                // if ($condition->condition_include_child) {
                //     $categoryGrandChildren = Category::where('id', $condition->item_id)->with('grandChildren')->get();
                //     $categoryIds           = array_merge($categoryIds,
                //         $this->parseCategoryIDWithGrandChildren($categoryGrandChildren));
                // }

                // if (empty($categoryIds)) {
                //     break;
                // }
                // if (
                // $this->checkProductCategoryInPromotionCategory($product, $categoryIds,
                //     $conditionName == 'not_in_category_name')
                // ) {
                //     $result = $this->compareConditionType(Arr::get($product, 'priceDetail.price', $product->price),
                //         $conditionType, $conditionInput);
                // }
                // break;
            case 'day':
                $result = $this->compareConditionType(
                    (int)date('N'),
                    $conditionType,
                    (int)date('N', strtotime($conditionInput))
                );
                break;
            default:
                break;
        }

        return $result;
    }

    private function checkConditionApplyCart(Cart $cart, $condition)
    {
        $conditionType  = $condition->condition_type;
        $conditionName  = $condition->condition_name;
        $conditionInput = !empty($condition->condition_input) ? $condition->condition_input : 0;
        $result         = false;

        $CategoryExceptIds = !empty($this->promotion->act_not_categories) ? array_pluck(json_decode($this->promotion->act_not_categories), 'category_id') : [];
        $ProductExceptIds = !empty($this->promotion->act_not_products) ? array_pluck(json_decode($this->promotion->act_not_products), 'product_id') : [];
        $CategoryIds = !empty($this->promotion->act_categories) ? array_pluck(json_decode($this->promotion->act_categories), 'category_id') : [];
        $ProductIds = !empty($this->promotion->act_products) ? array_pluck(json_decode($this->promotion->act_products), 'product_id') : [];

        switch ($conditionName) {
            case 'cart_quantity':
                $qty = 0;
                foreach ($cart->details as $cartDetail) {
                    $check = array_intersect($CategoryExceptIds, explode(',', $cartDetail->product_category));
                    if (in_array($cartDetail->product_id, $ProductExceptIds) || !empty($check)) {
                    } else {
                        if (!empty($this->promotion->act_categories) && $this->promotion->act_categories != "[]" || !empty($this->promotion->act_products) && $this->promotion->act_products != "[]") {
                            $check = array_intersect($CategoryIds, explode(',', $cartDetail->product_category));
                            if (in_array($cartDetail->product_id, $ProductIds) || !empty($check)) {
                                $qty += $cartDetail->quantity;
                            } else {
                                $qty += $cartDetail->quantity;
                            }
                        } else {
                            $qty += $cartDetail->quantity;
                        }
                    }
                }
                $value  = $qty;
                $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                break;
            case 'cart_total':
                $total = 0;
                foreach ($cart->details as $cartDetail) {
                    if (!$this->checkflashSale($cartDetail->product_id, $cartDetail->product_category)) {
                        continue;
                    }
                    $check = array_intersect($CategoryExceptIds, explode(',', $cartDetail->product_category));
                    if (in_array($cartDetail->product_id, $ProductExceptIds) || !empty($check)) {
                    } else {
                        $total += $cartDetail->total;
                    }
                }
                $value  = $total;
                $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                break;
                // $actCate = $this->promotion->act_not_categories;
                // if (!empty($actCate) && $actCate != "[]") {
                //     $cats         = !empty($actCate) ? json_decode($actCate, true) : [];
                //     $category_ids = !empty($cats) ? array_pluck($cats, 'category_id', 'category_id') : [];
                //     $product      = Product::model()->where(function ($q) use ($category_ids) {
                //         foreach ($category_ids as $item) {
                //             $q->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%,$item,%");
                //         }
                //     });

                //     $productIds = $product->pluck('id')->toArray();
                //     $value      = $cart->details->whereNotIn('product_id', $productIds)->sum('total');
                //     $result     = $this->compareConditionType($value, $conditionType, $conditionInput);
                // } else {
                //     $value  = $cart->details->sum('total');
                //     $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                // }
                // break;
            case 'product_name':
                $total = 0;
                $promotion = PromotionProgram::findOrFail($condition->promotion_program_id);
                if ($conditionType == "eq") {
                    $flag = false;
                    foreach ($cart->details as $key => $detail) {
                        $arr = [];
                        $arr[] = $detail->product_code;
                        if (in_array($condition->item_code, $arr) == 0) {
                            $arr = [];
                        }
                        if (in_array($condition->item_code, $arr)) {
                            if ($detail->quantity > $conditionInput && $promotion->multiply == 'yes') {
                                $flag = true;
                                break;
                            }
                            if ($detail->quantity == $conditionInput) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                    $result = $flag;
                } else {
                    if ($condition->multiply_type == "quantity") {
                        $value  = $cart->details->keyBy('product_code')[$condition->item_code]->quantity ?? 0;
                        $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                    }
                    if ($condition->multiply_type == "total") {
                        $flag = false;
                        foreach ($cart->details as $key => $detail) {
                            $arr = [];
                            $arr[] = $detail->product_code;
                            if (in_array($condition->item_code, $arr) == 0) {
                                $arr = [];
                            }
                            if (in_array($condition->item_code, $arr)) {
                                if ($detail->total > $conditionInput) {
                                    $flag = true;
                                }
                            }
                        }
                        $result = $flag;
                    }
                    if (empty($condition->multiply_type)) {
                        $value  = $cart->details->keyBy('product_code')[$condition->item_code]->quantity ?? 0;
                        $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                    }
                }
                break;
            case 'product_group':
                $promotion = PromotionProgram::findOrFail($condition->promotion_program_id);
                $actProd = $promotion->act_products;
                $prods         = !empty($actProd) ? json_decode($actProd, true) : [];
                $product_ids = !empty($prods) ? array_pluck($prods, 'product_id') : [];
                foreach ($cart->details as $key => $detail) {
                    if (in_array($detail->product_id, $product_ids)) {
                        $is_apply = 1;
                        break;
                    } else {
                        $is_apply = 0;
                    }
                }
                if (!empty($is_apply) == 1) {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
            case 'customer_name':
                $value  = $cart->user_id ?? 0;
                $result = $this->compareConditionType($value, 'eq', $condition->item_id);
                break;
            case 'customer_group':
                $value  = User::where('id', $cart->user_id)->value('group_id');
                $result = $this->compareConditionType($value ?? 0, 'eq', $condition->item_id);
                break;
            case 'customer_order':
                $value  = Order::where('customer_id', $cart->user_id)
                    ->where('status', ORDER_STATUS_COMPLETED)
                    ->sum('total_price');
                $result = $this->compareConditionType($value, $conditionType, $conditionInput);
                break;
            case 'customer_reg_date':
                $value  = User::where('id', $cart->user_id)->value('created_at');
                $value  = $value ? strtotime(date('Y-m-d', strtotime($value))) : 0;
                $result = $this->compareConditionType($value, 'eq', strtotime($conditionInput));
                break;
            case 'category_name':
                $total = 0;
                $quantity = 0;
                $flag = false;
                $categoryId = $condition->item_id;
                foreach ($cart->details as $cartDetail) {
                    if (!$this->checkflashSale($cartDetail->product_id, $cartDetail->product_category)) {
                        continue;
                    }
                    if (in_array($categoryId, explode(",", $cartDetail->product_category))) {
                        $total += $cartDetail->total;
                        $quantity += $cartDetail->quantity;
                    }
                }
                if ($condition->multiply_type == 'total') {
                    foreach ($cart->details as $cartDetail) {
                        if (!$this->checkflashSale($cartDetail->product_id, $cartDetail->product_category)) {
                            continue;
                        }
                        if (in_array($categoryId, explode(",", $cartDetail->product_category))) {
                            if ($total >= $conditionInput) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if ($condition->multiply_type == 'quantity') {
                    foreach ($cart->details as $cartDetail) {
                        if (!$this->checkflashSale($cartDetail->product_id, $cartDetail->product_category)) {
                            continue;
                        }
                        if (in_array($categoryId, explode(",", $cartDetail->product_category))) {
                            if ($quantity >= $conditionInput) {
                                $flag = true;
                                break;
                            }
                        }
                    }
                }
                if (empty($condition->multiply_type)) {
                    foreach ($cart->details as $cartDetail) {
                        if (!$this->checkflashSale($cartDetail->product_id, $cartDetail->product_category)) {
                            continue;
                        }
                        if (in_array($categoryId, explode(",", $cartDetail->product_category))) {
                            $flag = true;
                            break;
                        }
                    }
                }
                $result = $flag;
                break;
            case 'not_in_category_name':
                $is_apply = 0;
                $categoryId = $condition->item_id;
                foreach ($cart->details as $cartDetail) {
                    if (in_array($categoryId, explode(",", $cartDetail->product_category))) {
                        $is_apply = 1;
                    } else {
                        $is_apply = 0;
                        break;
                    }
                }
                if ($is_apply == 0) {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
            case 'day':
                $result = $this->compareConditionType(
                    (int)date('N'),
                    $conditionType,
                    (int)date('N', strtotime($conditionInput))
                );
                break;
            case 'apply_app':
                if (get_device() == 'APP') {
                    $result = true;
                } else {
                    $result = false;
                }
                break;
            default:
                break;
        }

        return $result;
    }

    public function parsePriceBySaleType($price, $promotion = null)
    {
        if (empty($promotion)) {
            $promotion = $this->getPromotion();
        }

        $price_promotion = $price;
        $proCateArr      = [];
        $proidArr        = [];

        if (!empty($this->cart->details)) {
            if ($promotion->promotion_type == 'DISCOUNT' || $promotion->promotion_type == 'GIFT' && $promotion->act_type == 'buy_x_get_y') {
                $price_promotion = 0;
                $item = [];
                foreach ($promotion->conditions as $i => $condition) {
                    if ($condition->condition_name == "cart_total") {
                        $price_promotion = $price;
                        break;
                    }
                    foreach ($this->cart->details as $cartDetail) {
                        if ($condition->condition_name == "product_name") {
                            $check_dub_prod = array_intersect($item, explode(',', $cartDetail->product_category));
                            if (!empty($check_dub_prod)) {
                                continue;
                            }
                        }
                        if ($cartDetail->product_code == $condition->item_code || in_array($condition->item_id, explode(',', $cartDetail->product_category))) {
                            $price_promotion += $cartDetail->total;
                            $item[] = $condition->item_id;
                        }
                    }
                }
            }
        }

        if (empty($promotion)) {
            return $price;
        }

        if ($promotion->act_sale_type == 'percentage') {
            return $price_promotion * ($promotion->act_price / 100);
        }

        if ($promotion->act_sale_type == 'config') {
            return round(($price_promotion * $promotion->act_point) / $promotion->act_exchange);
        }

        return $promotion->act_price ?? 0;
    }


    public function parsePriceByProducts($product_code, $price, $promotion = null)
    {
        if (empty($promotion)) {
            $promotion = $this->getPromotion();
        }

        $product = Product::model()->where('code', $product_code)->first();

        if ($promotion->act_type == 'sale_off_on_products') {
            if (!empty($promotion->act_products) && $promotion->act_products != "[]") {
                $act_products = json_decode($promotion->act_products);

                $promo_prod = array_pluck($act_products, 'product_code');
                $check_prod = array_search($product->code, $promo_prod);
                if (is_numeric($check_prod)) {
                    if ($promotion->act_sale_type == 'percentage') {
                        if (!empty($act_products[$check_prod]->discount)) {
                            return $price * ($act_products[$check_prod]->discount / 100);
                        }
                        if (empty($act_products[$check_prod]->discount)) {
                            return 0;
                        }
                    }
                    return $act_products[$check_prod]->discount ?? 0;
                }
            }
        }

        if ($promotion->act_type == 'sale_off_on_categories') {
            if (!empty($promotion->act_categories) && $promotion->act_categories != "[]") {
                foreach (json_decode($promotion->act_categories) as $act_category) {
                    $check = array_intersect(explode(',', $act_category->category_id), explode(',', $product->category_ids));
                    if (!empty($check)) {
                        if ($promotion->act_sale_type == 'percentage') {
                            if (!empty($act_category->discount)) {
                                return $price * ($act_category->discount / 100);
                            } else {
                                return 0;
                            }
                        }
                        return $act_category->discount ?? 0;
                    }
                }
            }
        }

        if (empty($promotion)) {
            return $price;
        }
    }

    public function checkProductCategoryInPromotionCategory(Product $product, $promotionCategoryIds, $notIn = false)
    {
        $productCategoryIds = explode(",", $product->category_ids);
        $flag               = $notIn;

        foreach ($promotionCategoryIds as $id) {
            if (in_array((int)$id, $productCategoryIds)) {
                if ($notIn == false) {
                    $flag = true;
                    break;
                } else {
                    $flag = false;
                    break;
                }
            }
        }

        return $flag;
    }

    /**
     * Parse category id with grand children
     *
     * @param $categoryGrandchildren
     * @return array
     */
    public function parseCategoryIDWithGrandChildren($categoryGrandchildren)
    {
        $result = [];
        foreach ($categoryGrandchildren as $items) {
            $result = array_merge($result, [$items->id]);
            if (!empty($items->grandChildren)) {
                $result = array_merge($result, $items->grandChildren->pluck('id')->toArray());

                $result = array_merge($result, $this->parseCategoryIDWithGrandChildren($items->grandChildren));
            }
        }

        return $result;
    }

    /**
     * Compare condition type
     *
     * @param $value
     * @param $conditionType
     * @param $conditionInput
     * @return bool
     */
    private function compareConditionType($value, $conditionType, $conditionInput)
    {
        $result = false;

        switch ($conditionType) {
            case "eq":
                $result = $value == $conditionInput;
                break;
            case "neq":
                $result = $value != $conditionInput;
                break;
            case "gtr":
                $result = $value >= $conditionInput;
                break;
            case "lth":
                $result = $value < $conditionInput;
                break;
            default:
                break;
        }
        return $result;
    }

    private static function sendMessage($message) {
        try {
            $timeout = 1;
            $token = env('TELEGRAM_BOT_TOKEN');
            $chat_id = env('TELEGRAM_CHAT_ID');
            $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
            $client = new Client(['timeout' => $timeout]);
            $client->request('POST', $url, [
                'form_params' => [
                    'chat_id' => $chat_id,
                    'text' => $message,
                ]
            ]);
        } catch (\Exception $e) {
        }
    }
}
