<?php


namespace App;


class OrderDmsHistory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_dms_histories';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'order_id',
        'status',
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

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }
}