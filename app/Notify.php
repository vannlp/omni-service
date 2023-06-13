<?php

namespace App;


class Notify extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifies';

    protected $fillable = [
        "title",
        "body",
        "type",
        "target_id",
        "product_search_query",
        "notify_for",
        "delivery_date",
        "user_id",
        "frequency",
        "user_id",
        "company_id",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}
