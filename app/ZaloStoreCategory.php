<?php

namespace App;

/**
 * Class ZaloStoreCategory
 * @package App
 */
class ZaloStoreCategory extends BaseModel
{
    protected $table = 'zalo_store_categories';
    protected $fillable = [
        "category_store_id",
        "zalo_category_id",
        "sync_zalo",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}
