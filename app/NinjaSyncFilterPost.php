<?php


namespace App;


class NinjaSyncFilterPost extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_filter_posts';

    protected $fillable
        = [
            "code",
            "post",
            "created_date",
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
