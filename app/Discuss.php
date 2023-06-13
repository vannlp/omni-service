<?php


namespace App;


class Discuss extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'discuss';

    /**
     * @var array
     */
    protected $fillable = [
        'issue_id',
        'description',
        'parent_id',
        'image_id',
        'count_like',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
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
        return $this->hasOne(File::class, 'id', 'image_id');
    }
}