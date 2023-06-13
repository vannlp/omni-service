<?php


namespace App;


class NinjaSyncUidPost extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_uid_posts';

    protected $fillable
        = [
            "like",
            "comment",
            "share",
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
