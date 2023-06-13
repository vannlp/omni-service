<?php

namespace App\V1\Actions;

use App\Cart;

class CartRemovePromocodeAction extends Action
{
    protected $cart;

    public function doIt()
    {
        $this->cart = Cart::current();

        $this->cart->removePromocode();
    }
}