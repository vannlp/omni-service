<?php

namespace App;

class FileCloud extends BaseModel
{
    protected $table = 'file_clouds';

    protected $fillable = [
        "title",
        "category",
        "shop",
        "url",
        "path",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
    ];

    public function fileCategory()
    {
        return $this->hasOne(__NAMESPACE__ . '\FileCategory', 'id', 'category');
    }

    public function store()
    {
        return $this->hasOne(__NAMESPACE__ . '\Store', 'id', 'shop');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }
}
