<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

/**
 * Class City
 *
 * @package App
 */
class City extends BaseModel {
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cities';

    public function country()
    {
        return $this->hasOne(__NAMESPACE__ . '\Country', 'id', 'country_id');
    }
    public function cityhasregion()
    {
        return $this->belongsTo(CityHasRegion::class, 'code', 'code_city');
    }
}
