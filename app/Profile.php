<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

class Profile extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'profiles';


    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'email',
        'first_name',
        'last_name',
        'short_name',
        'full_name',
        'representative',
        'address',
        'temp_address',
        'registed_address',
        'marital_status',
        'work_experience',
        'city_code',
        'district_code',
        'ward_code',
        'phone',
        'home_phone',
        'birthday',
        'avatar',
        'avatar_url',
        'gender',
        'id_number',
        'id_number_at',
        'id_number_place',
        'id_images',
        'transaction_total',
        'transaction_cancel',
        'money_total',
        'lat',
        'long',
        'area_id',
        'ready_work',
        'introduce_from',
        'customer_introduce',
        'operation_field',
        'education',
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

    public function city()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'city_code');
    }

    public function district()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'district_code');
    }

    public function ward()
    {
        return $this->hasOne(__NAMESPACE__ . '\Ward', 'code', 'ward_code');
    }

}
