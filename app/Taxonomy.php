<?php


namespace App;


class Taxonomy extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'taxonomies';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'slug',
        'website_id',
        'post_type_ids',
        'parent_id',
        'thumbnail_id',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function getBlog()
    {
        return $this->hasOne(Blog::class, 'id', 'blog_id');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'thumbnail_id');
    }

    public function website()
    {
        return $this->hasOne(Website::class, 'id', 'website_id');
    }
}