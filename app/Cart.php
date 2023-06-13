<?php


namespace App;

use App\TM;
use http\Message;

use Illuminate\Support\Arr;

/**
 * Class Cart
 * @package App
 */
class Cart extends BaseModel
{
    public static $current;
    public static $currentClient;

    protected $table = 'carts';

    protected $fillable
    = [
        'id',
        'user_id',
        'address',
        'session_id',
        'ship_address_latlong',
        'shipping_address_id',
        'description',
        'phone',
        'payment_method',
        'shipping_method',
        'receiving_time',
        'voucher_discount_code',
        'voucher_code',
        'voucher_value',
        'voucher_value_use',
        'voucher_title',
        'voucher_discount',
        'promotion_info',
        'point',
        'ex_change_point',
        'point_use',
        'free_item',
        'promotion',
        'is_freeship',
        'total_info',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'coupon_discount_code',
        'coupon_code',
        'coupon_price',
        'coupon_name',
        'delivery_discount_code',
        'coupon_delivery_code',
        'coupon_delivery_price',
        'coupon_delivery_name',
        'promocode_code',
        'promocode_price',
        'promocode_name',
        'full_name',
        'email',
        'distributor_id',
        'distributor_code',
        'distributor_name',
        'distributor_phone',
        'order_type',
        'distributor_city_code',
        'distributor_district_code',
        'distributor_ward_code',
        'distributor_city_name',
        'distributor_district_name',
        'distributor_ward_name',
        'customer_city_code',
        'customer_district_code',
        'customer_ward_code',
        'customer_city_name',
        'customer_district_name',
        'customer_ward_name',
        'customer_full_address',
        'street_address',
        'seller_id',
        'seller_code',
        'seller_name',
        'cart_info',
        'order_channel',
        'shipping_method_code',
        'shipping_method_name',
        'shipping_service',
        'service_name',
        'extra_service',
        'ship_fee',
        'ship_fee_down',
        'estimated_deliver_time',
        'lading_method',
        'store_id',
        'company_id',
        'log_confirm_order',
        'log_payment',
        'shipping_note',
        'shipping_diff',
        'shopee_reference_id',
        'qr_scan',
        'ship_fee_start',
        'total_weight',
        'address_window_id',
        'intersection_distance',
        'access_trade_id',
        'access_trade_click_id',
        'order_source',
        'shipping_error',
        'log_quote_grab',
        'log_quote_response_grab',
        'discount_admin_input',
        'discount_admin_input_type',
        'coupon_admin'
    ];

    protected $casts
    = [
        'promotion'      => 'json',
        'free_item'      => 'json',
        'promotion_info' => 'json',
        'total_info'     => 'json',
        'cart_info'      => 'json'
    ];

    public function getWard()
    {
        return $this->hasOne(__NAMESPACE__ . '\Ward', 'code', 'customer_ward_code');
    }

    public function getDistrict()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'customer_district_code');
    }

    public function getCity()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'customer_city_code');
    }

    public function getDistrictDistributor()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'distributor_district_code');
    }

    public function getWardDistributor()
    {
        return $this->hasOne(__NAMESPACE__ . '\Ward', 'code', 'distributor_ward_code');
    }

    public function getCityDistributor()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'distributor_city_code');
    }
    public function getUserDistributor()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'code', 'distributor_code');
    }

    public function getShippingMethod()
    {
        return $this->hasOne(__NAMESPACE__ . '\ShippingMethod', 'id', 'shipping_method');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function User()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }


    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function getShippingAddress()
    {
        return $this->hasOne(ShippingAddress::class, 'id', 'shipping_address_id');
    }

    public function details()
    {
        return $this->hasMany(CartDetail::class, 'cart_id', 'id');
    }

    public static function current()
    {
        if (!self::$current) {
            $userId = TM::getCurrentUserId();

            self::$current = self::with('details')
                ->where('user_id', $userId)
                ->first();
        }

        return self::$current;
    }

    public static function currentClient($sessionId)
    {
        if (!self::$current) {
            self::$current = self::with('details')
                ->where('session_id', $sessionId)
                ->first();
        }

        return self::$current;
    }

    public function addCoupon($coupons, $code = null, $delivery = null)
    {
        foreach ($coupons as $coupon) {
            $discountPrice = 0;
            if ($coupon['type_discount'] == 'shipping') {
                $this->delivery_discount_code = !empty($delivery) ? $delivery : $this->delivery_discount_code;
                $this->coupon_delivery_code = $coupon['code'];
                $this->coupon_delivery_name = $coupon['name'];

                if ($coupon['free_shipping'] == 1) {
                    $this->coupon_delivery_price = $this->ship_fee;
                    $this->is_freeship = 1;
                } else {
                    if ($coupon['type'] === 'P') {
                        $discountPrice = $this->ship_fee * $coupon['discount'] / 100;
                        if (!empty($coupon['limit_discount']) && $discountPrice >= $coupon['limit_discount']) {
                            $discountPrice = $coupon['limit_discount'];
                            if ($discountPrice >= $this->ship_fee) {
                                $discountPrice = $this->ship_fee;
                            }
                        }
                    }
                    if ($coupon['type'] != 'P') {
                        $discountPrice = $coupon['discount'];
                        if ($discountPrice >= $this->ship_fee) {
                            $discountPrice = $this->ship_fee;
                        }
                    }
                    $this->coupon_delivery_price = $discountPrice;
                    $this->is_freeship = 0;
                }
            }
            if ($coupon['type_discount'] == 'cart' || $coupon['type_discount'] == 'promocode') {
                $this->coupon_discount_code = !empty($code) ? $code : $this->coupon_discount_code;

                $this->coupon_code = $coupon['code'];
                $this->coupon_name = $coupon['name'];

                $discountPrice = 0;
                if ($coupon['type'] === 'P') {
                    $discountPrice = $this->sumCartDetailsPriceCoupon($coupon) * $coupon['discount'] / 100;
                    if (!empty($coupon['limit_discount']) && $discountPrice >= $coupon['limit_discount']) {
                        $discountPrice = $coupon['limit_discount'];
                    }
                }
                if ($coupon['type'] != 'P') {
                    $discountPrice = $coupon['discount'];
                }
                $this->coupon_price = $discountPrice;
            }

            if ($coupon['type_discount'] == 'voucher') {
                $this->voucher_discount_code = !empty($code) ? $code : $this->voucher_discount_code;
                $this->voucher_code  = $coupon['code'];
                $this->voucher_title = $coupon['name'];

                $discountPrice = 0;
                if ($coupon['type'] === 'P') {
                    $discountPrice = $this->sumCartDetailsPriceCoupon($coupon) * $coupon['discount'] / 100;
                    if (!empty($coupon['limit_discount']) && $discountPrice >= $coupon['limit_discount']) {
                        $discountPrice = $coupon['limit_discount'];
                    }
                }
                if ($coupon['type'] != 'P') {
                    $discountPrice = $coupon['discount'];
                }

                $this->voucher_value = $discountPrice;
            }

            $this->save();
        }
    }


    // public function addPromocode($coupon)
    // {
    //     if ($coupon->type_discount == 'promocode') {
    //         $this->coupon_code = $coupon['code'];
    //         $this->coupon_name = $coupon['name'];

    //         $discountPrice = 0;
    //         if ($coupon['type'] === 'P') {
    //             $discountPrice = $this->sumCartDetailsPrice() * $coupon['discount'] / 100;
    //         } else {
    //             if ($coupon['type'] === 'F') {
    //                 $discountPrice = $coupon['discount'];
    //             }
    //         }

    //         $this->coupon_price = $discountPrice;

    //     }

    //     $this->save();
    // }

    // public function addVoucher($voucher)
    // {
    //     if ($voucher->type_discount == 'voucher') {
    //         $this->voucher_code  = $voucher->code;
    //         $this->voucher_title = $voucher->name;
    //         $this->voucher_discount = $voucher->discount;

    //         $discountPrice = 0;
    //         if ($voucher->type === 'P') {
    //             $discountPrice = $this->sumCartDetailsPrice() * $voucher->discount / 100;
    //             if($discountPrice >= ($voucher['limit_price'] ?? 0)){
    //                 $discountPrice = $voucher['limit_price'];
    //             }
    //         } else {
    //             if ($voucher->type === 'F') {
    //                 $discountPrice = $voucher->discount;
    //             }
    //         }

    //         $this->voucher_value = $discountPrice;
    //     }

    //     $this->save();
    // }

    public function sumCartDetailsPrice($coupon = null)
    {
        if (!empty($coupon)) {
            $total = 0;
            $couponProductIds = explode(',', $coupon->product_ids);
            $couponCategorys = explode(',', $coupon->category_ids);

            $couponProductExceptIds = explode(',', $coupon->product_except_ids);
            $couponCategoryExceptIds = explode(',', $coupon->category_except_ids);

            if ($coupon->type_apply == "apply_products") {
                foreach ($this->details as $cartDetail) {
                    $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategoryExceptIds);
                    if (in_array($cartDetail->product_id, $couponProductExceptIds) || !empty($check)) {
                    } else {
                        if (!empty($couponProductIds) || !empty($couponCategorys)) {
                            $check = array_intersect($couponCategorys, explode(',', $cartDetail->product_category));
                            if (in_array($cartDetail->product_id, $couponProductIds) || !empty($check)) {
                                $total += $cartDetail->total;
                            }
                        }
                    }
                }
            }
            if ($coupon->type_apply == "apply_cart" || empty($coupon->type_apply)) {
                foreach ($this->details as $cartDetail) {
                    $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategoryExceptIds);
                    if (in_array($cartDetail->product_id, $couponProductExceptIds) || !empty($check)) {
                    } else {
                        $total += $cartDetail->total;
                    }
                }
            }
            return $total;
        }

        return $this->details->sum('total');
    }

    public function sumCartDetailsPriceCoupon($coupon = null)
    {
        if (!empty($coupon)) {
            $total = 0;
            $couponProductIds = explode(',', $coupon->product_ids);
            $couponCategorys = explode(',', $coupon->category_ids);

            $couponProductExceptIds = explode(',', $coupon->product_except_ids);
            $couponCategoryExceptIds = explode(',', $coupon->category_except_ids);

            if ($coupon->apply_discount == "product") {
                foreach ($this->details as $cartDetail) {
                    $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategoryExceptIds);
                    if (in_array($cartDetail->product_id, $couponProductExceptIds) || !empty($check)) {
                    } else {
                        if (!empty($couponProductIds) || !empty($couponCategorys)) {
                            $check = array_intersect($couponCategorys, explode(',', $cartDetail->product_category));
                            if (in_array($cartDetail->product_id, $couponProductIds) || !empty($check)) {
                                $total += $cartDetail->total;
                            }
                        }
                    }
                }
            }
            if ($coupon->apply_discount == "cart" || empty($coupon->type_apply)) {
                foreach ($this->details as $cartDetail) {
                    $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategoryExceptIds);
                    if (in_array($cartDetail->product_id, $couponProductExceptIds) || !empty($check)) {
                    } else {
                        $total += $cartDetail->total;
                    }
                }
            }
            return $total;
        }
        return $this->details->sum('total');
    }

    public function isContaintsCouponProducts(Coupon $coupon)
    {
        $couponProductIds = explode(',', $coupon->product_ids);

        foreach ($this->details as $cartDetail) {
            if (!empty($couponProductIds)) {
                if (in_array($cartDetail->product_id, $couponProductIds)) {
                    if($coupon->stack_able == 1){
                        $cartDetail->coupon_apply = 1;
                        $cartDetail->save();
                    }
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    public function isContaintsCouponCategories(Coupon $coupon)
    {
        $couponCategoryIds = explode(',', $coupon->category_ids);

        foreach ($this->details as $cartDetail) {
            if (!$cartDetail->product_id) {
                continue;
            }
            $productCategories = Arr::get($cartDetail, 'product.category_ids');
            $productCategories = explode(',', $productCategories);
            if (!$productCategories) {
                continue;
            }
            $dataFor = count($productCategories) > count($couponCategoryIds) ? $productCategories : $couponCategoryIds;
            foreach ($dataFor as $key => $item) {
                if (!empty($couponCategoryIds)) {
                    if (in_array($item, $couponCategoryIds)) {
                        if($coupon->stack_able == 1){
                            $cartDetail->coupon_apply = 1;
                            $cartDetail->save();
                        }
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    public function isContaintsCouponProductsExcept(Coupon $coupon)
    {
        $couponProductExceptIds = explode(',', $coupon->product_except_ids);
        $is_apply = 0;
        foreach ($this->details as $cartDetail) {
            if (in_array($cartDetail->product_id, $couponProductExceptIds)) {
                $is_apply = 0;
            } else {
                $is_apply = 1;
                break;
            }
        }
        if ($is_apply == 1) {
            return true;
        } else {
            return false;
        }

        return true;
    }

    public function isContaintsCouponCategoriesExcept(Coupon $coupon)
    {

        $is_apply = 0;
        $couponCategoryExceptIds = explode(',', $coupon->category_except_ids);
        foreach ($this->details as $cartDetail) {
            $product_category = explode(',', $cartDetail->product_category);
            $check = array_intersect($product_category, $couponCategoryExceptIds);
            if (!empty($check)) {
                $is_apply = 0;
            } else {
                $is_apply = 1;
                break;
            }
        }
        if ($is_apply == 1) {
            return true;
        } else {
            return false;
        }
        return true;
    }

    public function getCartPrice()
    {
        $couponPrice = $this->coupon_price ?? 0;

        return $this->sumCartDetailsPrice() - $couponPrice;
    }


    public function removeCoupon()
    {
        $this->update([
            'coupon_discount_code'  => null,
            'coupon_code'  => null,
            'coupon_name'  => null,
            'coupon_price' => null,
        ]);
    }

    public function removeCouponHandle($type = null)
    {
        if($type == 'shipping'){
            $this->update([
                'delivery_discount_code'  => null,
                'coupon_delivery_code'  => null,
                'coupon_delivery_name'  => null,
                'coupon_delivery_price' => null,
                'is_freeship' => 0,
            ]);
        }
        if ($type == 'cart' || $type == 'promocode') {
            $this->update([
                'coupon_discount_code'  => null,
                'coupon_code'  => null,
                'coupon_name'  => null,
                'coupon_price' => null,
            ]);
        }
        if($type == 'voucher'){
            $this->update([
                'voucher_discount_code'  => null,
                'voucher_code'  => null,
                'voucher_title'  => null,
                'voucher_value' => null,
                'voucher_value_use' => null,
            ]);
        }
    }


    // public function removePromocode()
    // {
    //     $this->update([
    //         'coupon_code'  => null,
    //         'coupon_name'  => null,
    //         'coupon_price' => null,
    //     ]);
    // }

    public function removeVoucher()
    {
        $this->update([
            'voucher_discount_code'  => null,
            'voucher_code'  => null,
            'voucher_title'  => null,
            'voucher_value' => null,
            'voucher_value_use' => null,
        ]);
    }

    public function removeCouponDelivery()
    {
        $this->update([
            'delivery_discount_code'  => null,
            'coupon_delivery_code'  => null,
            'coupon_delivery_name'  => null,
            'coupon_delivery_price' => null,
            'is_freeship' => 0,
        ]);
    }

    public function getOption($input)
    {
        if (empty($input)) {
            return null;
        }
        $result = ProductAttribute::with('attribute:id,name,value')
            ->whereIn('id', $input)->get();
        $data   = [];
        if (!empty($result)) {
            foreach ($result as $item) {
                $data[] = $item->attribute;
            }
        }
        return $data;
    }

    public function updateTotalDetail($id, $total)
    {
        $cartDetail = CartDetail::find($id);
        if (!$cartDetail) {
            throw new \Exception(\App\Supports\Message::get('V003', \App\Supports\Message::get('carts')));
        }
        $cartDetail->total = $total;
        $cartDetail->save();
        return true;
    }

    public function checklimitproductHub($distributor_id, $product_code, $qty, $product_name)
    {

        $user = User::model()->where('id', $distributor_id)->first();

        $now          = date('Y-m-d');
        $orders = Order::model()->with('details')->where('distributor_id', $distributor_id)->whereDate('created_at', $now)->get();
        $order_detail = 0;
        if (!empty($user)) {
            if ($user->group_code == "DISTRIBUTOR") {
                $distributor = ProductHub::with('user')->where('user_id', $distributor_id)->where('product_code', $product_code)->first();
                if (!empty($distributor)) {
                    foreach ($orders as $order) {
                        $order_detail += OrderDetail::model()->where('product_code', $product_code)->where('order_id', $order->id)->sum('qty');
                    }
                    if ($order_detail >= $distributor->limit_date) {
                        return "Sản phẩm hiện đang tạm hết hàng";
                    }
                    if (($qty + $order_detail) > $distributor->limit_date) {
                        $qty_re = $distributor->limit_date - $order_detail;
                        return "Hạn mức mua trên ngày còn $qty_re sản phẩm";
                    }
                }
            }
            if ($user->group_code == "HUB") {
                $center = ProductHub::with('user')->where('user_id', $user->distributor_center_id)->where('product_code', $product_code)->first();
                if (!empty($center)) {
                    foreach ($orders as $order) {
                        $order_detail += OrderDetail::model()->where('product_code', $product_code)->where('order_id', $order->id)->sum('qty');
                    }
                    if ($order_detail >= $center->limit_date) {
                        return "Sản phẩm hiện đang tạm hết hàng";
                    }
                    if (($qty + $order_detail) > $center->limit_date) {
                        $qty_re = $center->limit_date - $order_detail;
                        return "Hạn mức mua trên ngày còn $qty_re sản phẩm";
                    }
                }
            }
        }
    }

    public function checkAddress($saleArea, $city_code, $district_code, $ward_code)
    {
        if (!$saleArea) {
            return null;
        }
        $saleArea = json_decode($saleArea, true);
        if ($city_code) {
            $city_code_before_name = City::where('code', $city_code)->first();
            $key      = array_search($city_code, array_column($saleArea, 'code'));
            if (!is_numeric($key) && !empty($city_code_before_name)) {
                // return "Sản phẩm không được hỗ trợ giao ở " . $city_code_before_name->full_name;
                return "Hiện tại khu vực của Quý Khách chưa được hỗ trợ giao hàng";
            }
        }
        if ($district_code && $city_code) {
            $district_code_before_name = District::where('code', $district_code)->first();
            $city_key = array_search($city_code, array_column($saleArea, 'code'));
            if (!empty($saleArea[$city_key]['districts'])) {
                $key      = array_search($district_code, array_column($saleArea[$city_key]['districts'], 'code'));
                if (!is_numeric($key) && !empty($district_code_before_name)) {
                    // return "Sản phẩm không được hỗ trợ giao ở " . $district_code_before_name->full_name;
                    return "Hiện tại khu vực của Quý Khách chưa được hỗ trợ giao hàng";
                }
            }
        }
        if ($district_code && $city_code && $ward_code) {
            $ward_code_before_name = Ward::where('code', $ward_code)->first();
            $city_key = array_search($city_code, array_column($saleArea, 'code'));
            if (!empty($saleArea[$city_key]['districts'])) {
                $district_key = array_search($district_code, array_column($saleArea[$city_key]['districts'], 'code'));
                if (!empty($saleArea[$city_key]['districts'][$district_key]['wards'])) {
                    $key      = array_search($ward_code, array_column($saleArea[$city_key]['districts'][$district_key]['wards'], 'code'));
                    if (!is_numeric($key) && !empty($ward_code_before_name)) {
                        // return "Sản phẩm không được hỗ trợ giao ở " . $ward_code_before_name->full_name;
                        return "Hiện tại khu vực của Quý Khách chưa được hỗ trợ giao hàng";
                    }
                }
            }
        }
    }
}
