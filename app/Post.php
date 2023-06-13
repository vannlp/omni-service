<?php


namespace App;


class Post extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'posts';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'title',
        'slug',
        'thumbnail',
        'content',
        'view',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'meta_robot',
        'short_description',
        'category_id',
        'category_code',
        'company_id',
        'tags',
        'author',
        'date',
        'is_show',
        'meta_title',
        'meta_description',
        'meta_keyword',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function createdBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'thumbnail');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function category()
    {
        return $this->hasOne(PostCategory::class, 'id', 'category_id');
    }

    // public function tags()
    // {
    //     return $this->belongsToMany(PostTag::class, 'post_has_tags');
    // }

    // public function categories()
    // {
    //     return $this->belongsToMany(PostCategory::class, 'post_category_details', 'post_id', 'post_category_id');
    // }
}
