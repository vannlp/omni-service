<?php


namespace App;


class OrderStatus extends BaseModel
{
    const NEW = "NEW";
    const APPROVED = "APPROVED";
    const INPROGRESS = "INPROGRESS";
    const SHIPPING = "SHIPPING";
    const SHIPPER = "SHIPPER";
    const COMPLETED = "COMPLETED";
    const CANCEL = "CANCEL";

    protected $table = 'order_status';

    protected $fillable = [
        'code',
        'name',
        'description',
        'order',
        'company_id',
        'status_for',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function getCompany()
    {
        return $this->hasOne(__NAMESPACE__ . '\Company', 'id', 'company_id');
    }
}