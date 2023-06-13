<?php


namespace App;


class NinjaSyncUidAnalysis extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'ninja_uid_analysis';

    protected $fillable
        = [
            "code",
            "name",
            "gender",
            "country",
            "nation",
            "friend",
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
