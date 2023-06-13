<?php


namespace App;


class NinjaSyncListComment extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_list_comments';

    protected $fillable
        = [
            "user",
            "code",
            "name",
            "post_id",
            "comment",
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
