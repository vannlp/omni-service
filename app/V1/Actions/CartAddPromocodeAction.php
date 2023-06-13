<?php

namespace App\V1\Actions;

use App\TM;
use App\Cart;
use App\User;
use App\Coupon;
use Illuminate\Http\Request;

class CartAddPromocodeAction extends Action
{
    protected $card;
    protected $coupon;
    protected $customer;

    public function doIt()
    {
        $this->addPromocodeToCart()->success();
    }

    public function validate()
    {
        $this->cart = Cart::current();

        if ( ! $this->cart ) {
            return $this->addError('Giỏ hàng không tồn tại!');
        }

        if ( $this->cart->details->isEmpty() ) {
            return $this->addError('Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi sử dụng phiếu giảm giá!');
        }

        $this->customer = User::where('type', 'CUSTOMER')
                            ->find(TM::getCurrentUserId());

        if ( ! $this->customer ) {
            return $this->addError('Khách hàng không tồn tại!');
        }
        
        $now = date('Y-m-d H:i:s');
        $this->coupon = Coupon::where('code', $this->request->input('promocode_code'))
        ->whereRaw("'{$now}' BETWEEN date_start AND date_end")
        ->first();

        if ( !$this->coupon ) {
            return $this->addError('Mã phiếu giảm giá không tồn tại!');
        }

        if ( $this->cart->sumCartDetailsPrice() < $this->coupon->total ) {
            return $this->addError('Áp dụng điều kiện chưa đủ, giá giỏ hàng < tổng phiếu giảm giá');
        }

        if ($this->coupon->product_ids && !$this->cart->isContaintsCouponProducts($this->coupon)) {
            return $this->addError('Sản phẩm phiếu giảm giá không tồn tại trong giỏ hàng!');
        }

        if ($this->coupon->category_ids && !$this->cart->isContaintsCouponCategories($this->coupon)) {
            return $this->addError('Loại phiếu giảm giá không tồn tại trong giỏ hàng!');
        }

        if ($this->coupon->product_except_ids && !$this->cart->isContaintsCouponProductsExcept($this->coupon)) {
            return $this->addError('Giỏ hàng tồn tại sản phẩm không được giảm giá!');
        }

        if ($this->coupon->category_except_ids && !$this->cart->isContaintsCouponCategoriesExcept($this->coupon)) {
            return $this->addError('Giỏ hàng tồn tại sản phẩm nằm trong danh mục không được giảm giá!');
        }
        if(!empty($this->coupon->uses_total)){
            if ( $this->coupon->getTotalUsed() > $this->coupon->uses_total ) {
                return $this->addError('Phiếu giảm giá là sử dụng tối đa!');
            }
        }
        if(!empty($this->coupon->uses_customer)){
        if ( $this->coupon->getTotalUsed($this->customer) >= $this->coupon->uses_customer ) {
            return $this->addError('Phiếu giảm giá được sử dụng tối đa bởi khách hàng này!');
        }
    }

        return $this;
    }

    protected function addPromocodeToCart()
    {
        $this->cart->addPromocode($this->coupon);

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