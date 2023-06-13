<?php
/**
 * User: Administrator
 * Date: 01/01/2019
 * Time: 08:42 PM
 */

namespace App;


class Price extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prices';

    protected $fillable = [
        "code",
        "name",
        "company_id",
        "from",
        "to",
        "group_ids",
        "sale_area",
        "sale_area_list",
        "city_code",
        "district_code",
        "ward_code",
        "description",
        "status",
        "duplicated_from",
        "dup_type",
        "value",
        "order",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function details()
    {
        return $this->hasMany(PriceDetail::class, 'price_id', 'id');
    }
    public function getPrice()
    {
        return $this->hasOne(Price::class, 'id', 'duplicated_from');
    }
}
