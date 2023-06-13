<?php


namespace App;


class ShippingAddress extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'shipping_address';
    /**
     * @var string[]
     */
    protected $fillable
        = [
            'user_id',
            'full_name',
            'phone',
            'city_code',
            'district_code',
            'ward_code',
            'street_address',
            'company_id',
            'store_id',
            'is_default',
            'deleted',
            'updated_by',
            'created_by',
            'updated_at',
            'created_at',
        ];

    public function getUser()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function getCity()
{
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'city_code');
    }

    public function getDistrict()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'district_code');
    }

    public function getWard()
    {
        return $this->hasOne(Ward::class, 'code', 'ward_code');
    }
}