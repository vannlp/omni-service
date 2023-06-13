<?php

namespace App;


class Collection extends BaseModel
{
    protected $table = 'collections';

    protected $fillable = [
        'name',
        'description',
        'deleted',
        'created_at',
        'created_by',
        'updated_by',
        'updated_at'
    ];

    public function createdBy()
    {
        return $this->hasOne(Profile::class, 'user_id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(Profile::class, 'user_id', 'updated_by');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'collection_product', 'collection_id', 'product_id');
    }

    public function scopeSearch($query, $request)
    {
        return $query;
    }
}
