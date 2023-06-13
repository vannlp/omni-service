<?php


namespace App;


class Inventory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventories';

    protected $fillable = [
        'code',
        "transport",
        "user_id",
        "company_id",
        "date",
        "status",
        "description",
        "type",
        "providers",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function details()
    {
        return $this->hasMany(InventoryDetail::class, 'inventory_id', 'id');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }

    public function master_data_provider()
    {
        return $this->hasOne(__NAMESPACE__ . '\MasterData', 'id', 'providers');
    }
}