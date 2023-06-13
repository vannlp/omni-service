<?php


namespace App;


class CouponCode extends BaseModel
{
     /**
      * @var string
      */
     protected $table = 'coupon_codes';
     /**
      * @var string[]
      */
     protected $fillable = [
          'coupon_id',
          'code',
          'is_active',
          'type',
          'limit_discount',
          'discount',
          'order_used',
          'deleted',
          'created_at',
          'created_by',
          'updated_at',
          'updated_by',
     ];
     public function order()
     {
          return $this->hasOne(Order::class, 'id', 'order_used');
     }
}
