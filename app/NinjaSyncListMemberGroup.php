<?php


namespace App;


class NinjaSyncListMemberGroup extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_list_member_groups';

    protected $fillable = [
        "code",
        "name",
        "admin",
        "location",
        "gender",
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
