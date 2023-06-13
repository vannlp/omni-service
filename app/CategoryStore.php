<?php

namespace App;

/**
 * Class Category
 * @package App
 */
class CategoryStore extends BaseModel
{
    protected $table = 'category_stores';
    protected $fillable = [
        "category_id",
        "store_id",
        "store_code",
        "store_name",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function category()
    {
        return $this->hasOne(__NAMESPACE__ . '\Category', 'id', 'category_id');
    }

    public function zaloStoreCategory()
    {
        return $this->hasOne(__NAMESPACE__ . '\ZaloStoreCategory', 'category_store_id', 'id');
    }
}
