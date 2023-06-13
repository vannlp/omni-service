<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class StaffSync extends BaseModel
{
    protected $table = 'staff_syncs';

    protected $fillable = [
        'id',
        "staff_id",
        "staff_code",
        "staff_name",
        "shop_id",
        "idno",
        "idno_place",
        "idno_date",
        "email",
        "area_id",
        "street",
        "housenumber",
        "address",
        "phone",
        "mobilephone",
        "gender",
        "start_working_day",
        "education",
        "position",
        "birthday",
        "birthday_place",
        "staff_type_id",
        "status",
        "last_approved_order",
        "last_order",
        "create_date",
        "create_user",
        "update_date",
        "update_user",
        "plan",
        "update_plan",
        "sku",
        "reset_threshold",
        "sale_group",
        "name_text",
        "sale_type_code",
        "check_type",
        "enable_client_log",
        "work_state_code",
        "organization_id",
        "order_view",
        "equipment",
        "sale_area",
        "root_area",
        "permanent",
        "company_id",
        "store_id",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
        "deleted_at",
        "deleted_by",




    ];

    // public function file()
    // {
    //     return $this->hasOne(File::class, 'id', 'image_id');
    // }
}
