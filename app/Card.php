<?php


namespace App;


class Card extends BaseModel
{
    protected $table = 'cards';
    protected $fillable = [
        "code",
        "name",
        "from",
        "expired",
        "type",
        "is_active",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];
}