<?php


namespace App;


class MembershipRank extends BaseModel
{
    protected $table = 'membership_ranks';

    protected $fillable
        = [
            "code",
            "name",
            "point",
            "total_sale",
            "point_rate",
            "description",
            "icon",
            "company_id",
            "date_start",
            "date_end",
            "is_active",
            "deleted",
            "created_at",
            "created_by",
            "updated_at",
            "updated_by",
        ];
}