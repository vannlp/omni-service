<?php


namespace App;


class NinjaSyncListUser extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_list_users';

    protected $fillable
        = [
            "code",
            "name",
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
