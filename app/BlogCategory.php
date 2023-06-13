<?php


namespace App;


class BlogCategory extends BaseModel
{
    protected $table = 'blog_categories';
    protected $fillable = [
        "name",
        "slug",
        "blog_id",
        "website_id",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function blog()
    {
        return $this->hasOne(__NAMESPACE__ . '\Blog', 'id', 'blog_id');
    }

    public function website()
    {
        return $this->hasOne(Website::class, 'id', 'website_id');
    }
}