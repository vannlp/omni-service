<?php


namespace App;


class NinjaSyncListGroup extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_list_groups';

    protected $fillable = [
        "code",
        "name",
        "status",
        "location",
        "member",
        "pending",
        "company_id",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",
    ];
}
