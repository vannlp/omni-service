<?php
/**
 * Created by PhpStorm.
 * User: kpistech2
 * Date: 2019-10-14
 * Time: 02:28
 */

namespace App;


class UserStatusOrder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_status_orders';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'order_id',
        'status',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    public function order()
    {
        return $this->hasOne(__NAMESPACE__ . '\Order', 'id', 'order_id');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }
}
