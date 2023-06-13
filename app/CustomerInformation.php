<?php


namespace App;


use Illuminate\Database\Eloquent\Model;

class CustomerInformation extends Model
{
    /**
     * @var string
     */
    public $timestamps = false;
    protected $table    = 'customer_information';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'phone',
        'address',
        'email',
        'store_id',
        'city_code',
        'district_code',
        'ward_code',
        'full_address',
        'street_address',
        'note',
        'gender',
    ];

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

    public function user()
    {
        return $this->hasOne(User::class, 'phone', 'phone');
    }
}