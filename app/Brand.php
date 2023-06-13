<?php

namespace App;

class Brand extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'brands';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable
        = [
            "id",
            "name",
            "store_id",
            "description",
            "slug",
            "parent_id",
            "created_by",
            "updated_by"
        ];

    /**
     * HasMany product
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parent()
    {
        return $this->hasOne(Brand::class, 'id', 'parent_id');
    }
}