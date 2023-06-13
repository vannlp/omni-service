<?php


namespace App;


class Banner extends BaseModel
{
    /*
     * @var string
     */
    protected $table = 'banners';
    /*
     *
     */
    protected $fillable = [
        "id",
        "code",
        "title",
        "is_active",
        "display_in_categories",
        "store_id",
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

    public function details()
    {
        return $this->hasMany(BannerDetail::class, 'banner_id', 'id');
    }

    public function category()
    {
        return $this->belongsToMany(Category::class, 'banner_has_categories');
    }
}