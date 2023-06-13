<?php


namespace App;


use App\Supports\DataUser;
use Illuminate\Support\Str;

class CouponHistory extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'coupon_histories';
    /**
     * @var string[]
     */
    protected $fillable
        = [
            'order_id',
            'user_id',
            'price',
            'coupon_name',
            'coupon_discount_code',
            'coupon_code',
            'total_discount',
            'deleted',
            'created_at',
            'created_by',
            'updated_at',
            'updated_by'
        ];

    public function order()
    {
        return $this->hasOne(Order::class, 'id', 'order_id');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function coupon()
    {
        return $this->hasOne(Coupon::class, 'code', 'coupon_code');
    }

    public function scopeSearch($query, $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if ($user_id = $request->get('user_id')) {
            $query->whereRaw("user_id LIKE '%{$user_id}%'");
        }
        return $query;
    }
}