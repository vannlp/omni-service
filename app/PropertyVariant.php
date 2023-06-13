<?php

namespace App;

class PropertyVariant extends BaseModel
{
    protected $table = 'property_variants';

    protected $fillable
        = [
            'code',
            'name',
            'company_id',
            'store_id',
            'property_id',
            'deleted',
            'updated_by',
            'created_by',
            'updated_at',
            'created_at',
        ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function property()
    {
        return $this->hasOne(Property::class, 'id', 'property_id');
    }
}
