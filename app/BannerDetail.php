<?php


namespace App;


class BannerDetail extends BaseModel
{
    protected $table = 'banner_details';
    /*
     *
     */
    protected $fillable = [
        "banner_id",
        "slug",
        "lp_name",
        "image",
        "router",
        "query",
        "color",
        "category_id",
        "post_name",
        "name",
        "is_active",
        "product_search_query",
        "target_id",
        "type",
        "order_by",
        "data",
        "display_in_categories",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function categoryBanner()
    {
        return $this->hasOne(__NAMESPACE__ . '\Category', 'id', 'category_id');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'image');
    }
}