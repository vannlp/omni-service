<?php
/**
 * User: Phan Van
 */

namespace App;


class ConfigShippingCondition extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config_shipping_conditions';

    protected $fillable = [
        'config_shipping_id',
        'config_shipping_code',
        'condition_name',
        'condition_type',
        'condition_number',
        'condition_arrays',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];
        

    public function createdBy()
    {
        return $this->hasOne(Profile::class, 'user_id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(Profile::class, 'user_id', 'updated_by');
    }

    public function config_shipping() {
        return $this->belongsTo(ConfigShipping::class, 'config_shipping_id');
    }
    
}
