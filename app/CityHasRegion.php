<?php
/**
 * Created by PhpStorm.
 * User: SaoBang
 * Date: 9/14/2019
 * Time: 20:25
 */

namespace App;


class CityHasRegion extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'city_regions';

    protected $fillable = [
        'code_city',
        'code_region',
        'name_region',
        'store_id',
        'company_id',
        'deleted',
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
    public function region(){
        return $this->belongsTo(Region::class, 'code_region', 'code');
    }
}