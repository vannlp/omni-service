<?php


namespace App;


class NinjaSyncUidFriend extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_uid_friends';

    protected $fillable
        = [
            "code",
            "name",
            "birthday",
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
