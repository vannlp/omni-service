<?php


namespace App;


class Folder extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'folders';

    protected $fillable = [
        'folder_name',
        "folder_path",
        'parent_id',
        "folder_key",
        "company_id",
        "store_id",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function file()
    {
        return $this->hasMany(__NAMESPACE__ . '\Files', 'folder_id', 'id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }
}