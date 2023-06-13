<?php


namespace App;


class WarehouseDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'warehouse_details';

    protected $fillable = [
        "id",
        "product_id",
        "variant_id",
        "product_code",
        "product_name",
        "warehouse_id",
        "warehouse_code",
        "warehouse_name",
        "object_id",
        "object_type",
        "available_quantity",
        "appoved_quantity",
        "seq",
        "warehouse_type",
        "descr",
        "upload_date",
        "unit_id",
        "unit_code",
        "unit_name",
        "batch_id",
        "batch_code",
        "batch_name",
        "company_id",
        "product_type",
        "quantity",
        "exp",
        "price",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function product()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }

    public function warehouse()
    {
        return $this->hasOne(__NAMESPACE__ . '\Warehouse', 'id', 'warehouse_id');
    }

    public function batch()
    {
        return $this->hasOne(__NAMESPACE__ . '\Batch', 'id', 'batch_id');
    }

    public function unit()
    {
        return $this->hasOne(__NAMESPACE__ . '\Unit', 'id', 'unit_id');
    }
}