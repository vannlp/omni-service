<?php


namespace App;


class InventoryDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventory_details';

    protected $fillable = [
        "product_id",
        "variant_id",
        "product_code",
        "product_name",
        "inventory_id",
        "quantity",
        "exp",
        "price",
        "unit_id",
        "unit_code",
        "unit_name",
        "batch_id",
        "batch_code",
        "batch_name",
        "warehouse_id",
        "warehouse_code",
        "warehouse_name",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];


    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'id', 'inventory_id');
    }

    public function unitInventory()
    {
        return $this->hasOne(__NAMESPACE__ . '\Unit', 'id', 'unit_id');
    }

    public function warehouseInventory()
    {
        return $this->hasOne(__NAMESPACE__ . '\Warehouse', 'id', 'warehouse_id');
    }

    public function productInventory()
    {
        return $this->hasOne(__NAMESPACE__ . '\Product', 'id', 'product_id');
    }

    public function batchInventory()
    {
        return $this->hasOne(__NAMESPACE__ . '\Batch', 'id', 'batch_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

}