<?php
/**
 * Created by PhpStorm.
 * User: SaoBang
 * Date: 9/14/2019
 * Time: 20:25
 */

namespace App;


class DistributorHasShipper extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'distributor_has_shippers';

    protected $fillable = [
        'distributor_id',
        'shipper_id',
        'shipper_code',
        'shipper_name',
        'deleted',
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}