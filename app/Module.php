<?php

namespace App;

class Module extends BaseModel
{
    protected $table = 'modules';

    protected $fillable = [
        "module_type",
        "module_data",
        "company_id",
        "module_name",
        "module_code",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
    ];

    public function company()
    {
        return $this->hasOne(__NAMESPACE__ . '\Company', 'id', 'company_id');
    }
}
