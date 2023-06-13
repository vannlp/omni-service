<?php

namespace App;

class Property extends BaseModel
{
    protected $table = 'properties';

    protected $fillable
        = [
            'code',
            'name',
            'company_id',
            'store_id',
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

    public function category()
    {
        return $this->belongsToMany(Category::class, 'category_properties');
    }
    public function variant()
    {
        return $this->hasMany(__NAMESPACE__ . '\PropertyVariant', 'property_id','id');
    }
}
