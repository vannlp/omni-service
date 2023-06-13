<?php

namespace App\V1\Actions;

use App\TM;
use App\Cart;
use App\User;
use App\Coupon;
use Illuminate\Http\Request;

class CartAddVoucherAction extends Action
{
    protected $card;
    protected $coupon;
    protected $customer;

    public function doIt()
    {
        $this->addVoucherToCart()->success();
    }

    public function validate()
    {
        $this->cart = Cart::current();

        if ( ! $this->cart ) {
            return $this->addError('Giỏ hàng không tồn tại!');
        }

        if ( $this->cart->details->isEmpty() ) {
            return $this->addError('Giỏ hàng trống! Vui lòng thêm sản phẩm trước khi sử dụng voucher!');
        }

        $this->customer = User::where('type', 'CUSTOMER')
                            ->find(TM::getCurrentUserId());

        if ( ! $this->customer ) {
            return $this->addError('Khách hàng không tồn tại!');
        }
        
        $now = date('Y-m-d H:i:s');
        $this->voucher = Coupon::where('code', $this->request->input('voucher_code'))
        ->whereRaw("'{$now}' BETWEEN date_start AND date_end")
        ->where('status', 1)
        ->first();

        if ( !$this->voucher ) {
            return $this->addError('Mã voucher không tồn tại!');
        }

        if ( $this->voucher->discount <= 0 ) {
            return $this->addError('Voucher đã sử dụng hết!');
        }

        if ( $this->cart->sumCartDetailsPrice() < $this->voucher->mintotal ) {
            return $this->addError('Áp dụng điều kiện chưa đủ, giá giỏ hàng < tổng voucher');
        }

        if ($this->voucher->product_ids && !$this->cart->isContaintsCouponProducts($this->voucher)) {
            return $this->addError('Sản phẩm voucher không tồn tại trong giỏ hàng!');
        }

        if ($this->voucher->category_ids && !$this->cart->isContaintsCouponCategories($this->voucher)) {
            return $this->addError('Loại voucher không tồn tại trong giỏ hàng!');
        }

        if ($this->voucher->product_except_ids && !$this->cart->isContaintsCouponProductsExcept($this->voucher)) {
            return $this->addError('Giỏ hàng tồn tại sản phẩm không được giảm giá!');
        }

        if ($this->voucher->category_except_ids && !$this->cart->isContaintsCouponCategoriesExcept($this->voucher)) {
            return $this->addError('Giỏ hàng tồn tại sản phẩm nằm trong danh mục không được giảm giá!');
        }
        if(!empty($this->voucher->uses_total)){
            if ( $this->voucher->getTotalUsed() > $this->voucher->uses_total ) {
                return $this->addError('Voucher là sử dụng tối đa!');
            }
        }
        if(!empty($this->voucher->uses_customer)){
        if ( $this->voucher->getTotalUsed($this->customer) >= $this->voucher->uses_customer ) {
            return $this->addError('Voucher được sử dụng tối đa bởi khách hàng này!');
        }
    }

        return $this;
    }

    protected function addVoucherToCart()
    {
        $this->cart->addVoucher($this->voucher);

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