<?php


namespace App;


class CustomerAttribute extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_attributes';

    protected $fillable = [
        "customer_attribute_id",
        "code",
        "name",
        "description",
        "type",
        "data_length",
        "min_value",
        "is_enumeration",
        "is_search",
        "mandatory",
        "display_order",
        "status",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

}