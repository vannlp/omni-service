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
class Ward extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wards';

    public function district()
    {
        return $this->hasOne(__NAMESPACE__ . '\District', 'code', 'district_code');
    }
}
