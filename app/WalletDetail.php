<?php


namespace App;


class WalletDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wallet_details';

    /**
     * @var array
     */
    protected $fillable = [
        'wallet_id',
        'total',
        'description',
        'status',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\Wallet', 'id', 'wallet_id');
    }
}