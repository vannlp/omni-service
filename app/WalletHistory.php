<?php
/**
 * Created by PhpStorm.
 * User: dai.ho
 * Date: 10/30/2019
 * Time: 10:40 AM
 */

namespace App;


class WalletHistory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_histories';

    /**
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'transaction_id',
        'date',
        'balance',
        'increase',
        'reduce',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }
}