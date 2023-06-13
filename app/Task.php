<?php

namespace App;

class Task extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "name",
        "description",
        "status",
        "estimated_time",
        "category_id",
        "user_id",
        "parent_id",
        "progress",
        "priority",
        "start_time",
        "deadline",
        "version",
        "file_id",
        "is_prompt",
        "is_prompt",
        "related_issues",
        "is_active",
        "deleted",
        "created_by",
        "updated_by",
        "deleted_by"
    ];

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'category_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}