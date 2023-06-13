<?php
/**
 * Created by PhpStorm.
 * User: SaoBang
 * Date: 9/15/2019
 * Time: 01:06
 */

namespace App;


class Warehouse extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'warehouses';

    protected $fillable = [
        'code',
        'name',
        'address',
        'shop_id',
        'warehouse_type',
        'seq',
        'description',
        'company_id',
        'store_id',
        'is_active',
        'deleted',
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function warehouseDetail()
    {
        return $this->hasMany(__NAMESPACE__ . '\WarehouseDetail', 'warehouse_id', 'id');
    }

    public function stores()
    {
        return $this->hasOne(Store::class, 'store_id', 'id');
    }
}