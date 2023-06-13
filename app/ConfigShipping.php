<?php
/**
 * User: Phan Van
 */

namespace App;


class ConfigShipping extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'config_shippings';

    protected $fillable = [
        'code',
        'delivery_code',
        'delivery_name',
        'time_from',
        'time_to',
        'time_type',
        'shipping_partner_code',
        'shipping_partner_name',
        'shipping_fee',
        'is_active',
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

    public function config_shipping_conditions() {
        return $this->hasMany(ConfigShippingCondition::class, 'config_shipping_id', 'id');
    }
    
}
