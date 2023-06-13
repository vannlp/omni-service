<?php

namespace App;

class TaskCategory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'task_categories';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        "id",
        "code",
        "name",
        "description",
        "is_active",
        "deleted",
        "created_by",
        "updated_by",
        "deleted_by"
    ];
}