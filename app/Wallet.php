<?php


namespace App;


class Wallet extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallets';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_pay',
        'total_deposit',
        'code',
        'pin',
        'using_pin',
        'description',
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

    public function details()
    {
        return $this->hasMany(__NAMESPACE__ . '\WalletDetail', 'wallet_id', 'id');
    }
}