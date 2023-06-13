<?php


namespace App;


class PostComment extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'post_comments';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'content',
        'post_id',
        'parent_id',
        'website_id',
        'like',
        'count_like',
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

    public function post()
    {
        return $this->hasOne(__NAMESPACE__ . '\Post', 'id', 'post_id');
    }

    public function parent()
    {
        return $this->hasOne(__NAMESPACE__ . '\PostComment', 'id', 'parent_id');
    }

    public function website()
    {
        return $this->hasOne(Website::class, 'id', 'website_id');
    }
}