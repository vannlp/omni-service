<?php


namespace App;


class PaymentHistory extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'payment_histories';
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'transaction_id',
        'date',
        'type',
        'method',
        'status',
        'content',
        'total_pay',
        'balance',
        'user_id',
        'data',
        'note',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}