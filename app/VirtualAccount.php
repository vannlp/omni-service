<?php

namespace App;

class VirtualAccount extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'virtual_accounts';


    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'code',
        'name',
        'order_id',
        'is_active',
        'deleted',
        'deleted_at',
        'deleted_by',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];
    public function virtualaccount()
    {
        return $this->hasOne(Order::class, 'virtual_account_code', 'code');
    }
}
