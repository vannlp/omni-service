<?php


namespace App;


class AppMenu extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'app_menus';
    /**
     * @var array
     */
    protected $fillable = [
        "code",
        "name",
        "data",
        "store_id",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }
}
