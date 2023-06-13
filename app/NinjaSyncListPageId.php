<?php


namespace App;


class NinjaSyncListPageId extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_list_page_ids';

    protected $fillable = [
        "code",
        "name",
        "like",
        "follow",
        "checkin",
        "email",
        "location",
        "category",
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
