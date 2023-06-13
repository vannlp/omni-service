<?php


namespace App;


class Blog extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'blogs';
    /**
     * @var string[]
     */
    protected $fillable = [
        'id',
        'name',
        'description',
        'keyword',
        'icon',
        'website_id',
        'favicon',
        'is_active',
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

    public function website()
    {
        return $this->hasOne(Website::class, 'id', 'website_id');
    }
}