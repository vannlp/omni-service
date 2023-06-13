<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

/**
 * Class District
 * @package App
 */
class District extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'districts';

    public function city()
    {
        return $this->hasOne(__NAMESPACE__ . '\City', 'code', 'city_code');
    }
    public function ward()
    {
        return $this->hasMany(Ward::class, 'district_code', 'code');
    }
}
