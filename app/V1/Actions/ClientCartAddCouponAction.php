<?php

namespace App\V1\Actions;

use App\Session;
use App\TM;
use App\Cart;
use App\User;
use App\Coupon;
use Illuminate\Http\Request;

class ClientCartAddCouponAction extends Action
{
    protected $card;
    protected $coupon;
    protected $sessions;

    public function doIt()
    {
        $this->addCouponToCart()->success();
    }

    public function validate()
    {
        $sessionId  = $this->request->input('session_id');
        $this->cart = Cart::model()->where('session_id', $sessionId)->first();

        if (!$this->cart) {
            return $this->addError('Cart is not exists!');
        }

        if ($this->cart->details->isEmpty()) {
            return $this->addError('Cart is empty! Please add product before using coupon!');
        }

        $now          = date('Y-m-d H:i:s');
        $this->coupon = Coupon::where('code', $this->request->input('coupon_code'))
            ->whereRaw("'{$now}' BETWEEN date_start AND date_end")
            ->first();

        if (!$this->coupon) {
            return $this->addError('Coupon code is not exists!');
        }

//        $this->customer = User::where('type', 'CUSTOMER')
//                            ->find(TM::getCurrentUserId());

        $this->sessions = Session::where('session_id', $this->request->input('session_id'))->select('session_id')->first();

        if (!$this->sessions) {
            return $this->addError('Customer is not exists!');
        }

        if ($this->cart->sumCartDetailsPrice() < $this->coupon->total) {
            return $this->addError('Chưa đủ điều kiện áp dụng, cart price < coupon total');
        }
        if ($this->coupon->product_ids && !$this->cart->isContaintsCouponProducts($this->coupon)) {
            return $this->addError('Coupon products is not exists in cart!');
        }

        if ($this->coupon->category_ids && !$this->cart->isContaintsCouponCategories($this->coupon)) {
            return $this->addError('Coupon category is not exists in cart!');
        }

        if ($this->coupon->getClientTotalUsed() > $this->coupon->uses_total) {
            return $this->addError('Coupon is maximum uses!');
        }

        if ($this->coupon->getClientTotalUsed($this->sessions) >= $this->coupon->uses_customer) {
            return $this->addError('Coupon maximum used by this customer!');
        }

        return $this;
    }

    protected function addCouponToCart()
    {
        $this->cart->addCoupon($this->coupon);

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