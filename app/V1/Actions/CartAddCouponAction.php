<?php

namespace App\V1\Actions;

use App\TM;
use App\Cart;
use App\User;
use App\Order;
use App\Coupon;
use App\RotationDetail;
use Illuminate\Http\Request;

class CartAddCouponAction extends Action
{
    protected $card;
    protected $coupon;
    protected $customer;

    public function doIt()
    {
        $this->addCouponToCart()->success();
    }

    public function validate()
    {
        $this->cart = Cart::current();

        if (!$this->cart) {
            return $this->addError('Giỏ hàng không tồn tại!');
        }

        if ($this->cart->details->isEmpty()) {
            return $this->addError('Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi sử dụng phiếu giảm giá!');
        }

        $now = date('Y-m-d H:i:s');

        $this->coupon = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
            ->WhereIn('coupon_codes.code', [$this->request->input('coupon_code'), $this->request->input('coupon_delivery_code')])
            ->whereRaw("'{$now}' BETWEEN coupons.date_start AND coupons.date_end")
            ->where('coupons.status', 1)
            ->where(function ($q) use ($now) {
                $q->whereNull('coupon_codes.user_code')->whereNull('coupon_codes.start_date')->whereNull('coupon_codes.end_date');
                $q->orWhereNull('coupon_codes.user_code')->whereRaw("'{$now}' BETWEEN coupon_codes.start_date AND coupon_codes.end_date");
                $q->orWhere('coupon_codes.user_code', TM::getCurrentUserCode())->whereNull('coupon_codes.start_date')->whereNull('coupon_codes.end_date');
                $q->orWhere('coupon_codes.user_code', TM::getCurrentUserCode())->whereRaw("'{$now}' BETWEEN coupon_codes.start_date AND coupon_codes.end_date");
            })
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
            // ->where('coupon_codes.is_active', 0)
            ->get();
                

        if (!empty($this->coupon)) {
            $this->code = $this->request->input('coupon_code');
            $this->delivery = $this->request->input('coupon_delivery_code');
        }


        if (empty($this->coupon->toArray())) {
            return $this->addError('Mã phiếu giảm giá không tồn tại!');
        }

        foreach ($this->coupon as $thiscoupon) {

            if (empty($this->request->input('type_discount'))) {
                if ($thiscoupon->is_active == 1) {
                    return $this->addError('Đã hết lượt sử dụng mã giảm giá');
                }
            }

            $this->customer = User::whereIn('type', ['CUSTOMER','USER'])
                ->find(TM::getCurrentUserId());

            if (!empty($thiscoupon->condition)) {
                switch ($thiscoupon->condition) {
                    case 'first_order_app':
                        $order_first_check = Order::where('customer_id', TM::getCurrentUserId())->where('order_channel', 'MOBILE')->first();
                        if (!empty($order_first_check) || get_device() == 'APP') {
                            return $this->addError('Không đủ điều kiện áp dụng!');
                        }
                        break;
                    case 'first_order_web':
                        $order_first_check = Order::where('customer_id', TM::getCurrentUserId())->whereIn('order_channel', ['WEB', 'MWEB'])->first();
                        if (!empty($order_first_check) || get_device() == 'APP') {
                            return $this->addError('Không đủ điều kiện áp dụng!');
                        }
                        break;
                    case 'apply_app':
                        if (get_device() != 'APP') {
                            return $this->addError('Phiếu giảm giá này chỉ áp dụng cho app!');
                        }
                        break;
                    case 'apply_web':
                        if (get_device() == 'APP') {
                            return $this->addError('Phiếu giảm giá này chỉ áp dụng cho web!');
                        }
                        break;
                    case 'first_order':
                        $order_first_check = Order::where('customer_id', TM::getCurrentUserId())->first();
                        if (!empty($order_first_check)) {
                            return $this->addError('Không đủ điều kiện áp dụng!');
                        }
                        break;
                    case 'rotation':
                        $detail = RotationDetail::join('rotation_results as rr', 'rr.code', 'rotation_details.rotation_code')
                            ->where('rr.coupon_id', $thiscoupon->id)
                            ->where('user_id', TM::getCurrentUserId())->first();
                        if (empty($detail)) {
                            return $this->addError('Không có voucher này!');
                        }
                        break;
                    default:
                        break;
                }
            }
            if (!$this->customer) {
                return $this->addError('Khách hàng không tồn tại!');
            }

            if ($thiscoupon->type_discount == "voucher") {
                if ($thiscoupon->discount <= 0) {
                    return $this->addError('Voucher đã sử dụng hết!');
                }
            }

            if (!empty($thiscoupon->mintotal) && $thiscoupon->mintotal > 0 && $this->cart->sumCartDetailsPrice($thiscoupon) < $thiscoupon->mintotal) {
                return $this->addError('Mã giảm giá không khả dụng. Vui lòng tham khảo điều kiện áp dụng');
            }

            if (!empty($thiscoupon->maxtotal) && $thiscoupon->maxtotal > 0 && $this->cart->sumCartDetailsPrice($thiscoupon) > $thiscoupon->maxtotal) {
                return $this->addError('Mã giảm giá không khả dụng. Vui lòng tham khảo điều kiện áp dụng');
            }

            if ($thiscoupon->product_ids && !$this->cart->isContaintsCouponProducts($thiscoupon)) {
                return $this->addError('Mã giảm giá không khả dụng. Vui lòng tham khảo điều kiện áp dụng');
            }

            if ($thiscoupon->category_ids && !$this->cart->isContaintsCouponCategories($thiscoupon)) {
                return $this->addError('Mã giảm giá không khả dụng. Vui lòng tham khảo điều kiện áp dụng');
            }

            if ($thiscoupon->product_except_ids && !$this->cart->isContaintsCouponProductsExcept($thiscoupon)) {
                return $this->addError('Mã giảm giá không khả dụng. Vui lòng tham khảo điều kiện áp dụng');
            }

            if ($thiscoupon->category_except_ids && !$this->cart->isContaintsCouponCategoriesExcept($thiscoupon)) {
                return $this->addError('Mã giảm giá không khả dụng. Vui lòng tham khảo điều kiện áp dụng');
            }
            if ($this->request->input('type_discount') == 'cart' || $this->request->input('type_discount') == 'shipping') {
                if (!empty($thiscoupon->uses_total)) {
                    if ($thiscoupon->getTotalUsed() > $thiscoupon->uses_total || $thiscoupon->getTotalUsedDelivery() > $thiscoupon->uses_total) {
                        // return $this->addError('Phiếu giảm giá là sử dụng tối đa!');
                        return $this->addError('Đã hết lượt sử dụng mã giảm giá');
                    }
                }
                if (!empty($thiscoupon->uses_customer)) {
                    if ($thiscoupon->getTotalUsed($this->customer) >= $thiscoupon->uses_customer || $thiscoupon->getTotalUsedDelivery($this->customer) >= $thiscoupon->uses_customer) {
                        return $this->addError('Đã hết lượt sử dụng mã giảm giá');
                    }
                }
            }
        }

        return $this;
    }

    protected function addCouponToCart()
    {
        $this->cart->addCoupon($this->coupon, $this->code, $this->delivery);

        return $this;
    }

    protected function success()
    {
        $this->isSuccess = true;

        return $this;
    }

    public function isSuccess()
    {
        return $this->isSuccess;
    }

    public function errors()
    {
        return $this->errors;
    }
}
