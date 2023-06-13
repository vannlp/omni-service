<?php


namespace App;


class NinjaSyncFilterInteractive extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_filter_interactives';

    protected $fillable
        = [
            "code",
            "user_name",
            "gender",
            "location",
            "interactive",
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
