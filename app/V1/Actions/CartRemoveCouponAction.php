<?php

namespace App\V1\Actions;

use App\Cart;

class CartRemoveCouponAction extends Action
{
    protected $cart;

    public function doIt()
    {
        $this->cart = Cart::current();

        $this->cart->removeCoupon();
    }
}