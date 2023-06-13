<?php


namespace App;


class PostCategoryDetail extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'post_category_details';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'post_id',
        'post_category_id',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by'
    ];

    public function post()
    {
        return $this->hasOne(Post::class, 'id', 'post_id');
    }

    public function postCategory()
    {
        return $this->hasOne(PostCategory::class, 'id', 'post_category_id');
    }
}