<?php
/**
 * User: kpistech2
 * Date: 2019-11-03
 * Time: 14:57
 */

namespace App;


class Store extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'stores';

    protected $fillable = [
        'id',
        'code',
        'name',
        'lat',
        'long',
        'email',
        'email_notify',
        'description',
        'address',
        'contact_phone',
        'token',
        'company_id',
        'company_code',
        'company_name',
        'chanels',
        'warehouse_id',
        'warehouse_code',
        'warehouse_name',
        'city_code',
        'city_type',
        'city_name',
        'district_code',
        'district_type',
        'district_name',
        'ward_code',
        'ward_type',
        'ward_name',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function products()
    {
        return $this->hasMany(__NAMESPACE__ . '\Product', 'store_id', 'id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function warehouse()
    {
        return $this->hasOne(Warehouse::class, 'id', 'warehouse_id');
    }

    public function city()
    {
        return $this->hasOne(City::class, 'code', 'city_code');
    }

    public function district()
    {
        return $this->hasOne(District::class, 'code', 'district_code');
    }

    public function ward()
    {
        return $this->hasOne(Ward::class, 'code', 'ward_code');
    }
}
