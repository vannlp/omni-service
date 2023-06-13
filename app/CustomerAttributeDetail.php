<?php


namespace App;


class CustomerAttributeDetail extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_attribute_details';

    protected $fillable = [
        "customer_attribute_detail_id",
        "customer_id",
        "customer_attribute_id",
        "value",
        "customer_attribute_enum_id",
        "status",
        "deleted",
        "deleted_at",
        "deleted_by",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

}