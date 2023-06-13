<?php


namespace App;


class PostCategory extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'post_categories';
    /**
     * @var string[]
     */
    protected $fillable
        = [
            'id',
            'code',
            'title',
            'slug',
            'thumbnail',
            'description',
            'order',
            'company_id',
            'is_show',
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

    public function thumbnail()
    {
        return $this->hasOne(File::class, 'id', 'thumbnail_id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }
}
