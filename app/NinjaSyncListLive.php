<?php


namespace App;


class NinjaSyncListLive extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_list_lives';

    protected $fillable
        = [
            "code",
            "user_name",
            "phone",
            "comment",
            "created_date",
            "deleted",
            "created_at",
            "created_by",
            "updated_at",
            "updated_by",
            "deleted_at",
            "deleted_by",
        ];
}
