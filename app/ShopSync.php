<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class ShopSync extends BaseModel
{
     protected $table = 'shop_syncs';

     protected $fillable = [
          'id',
          "shop_id",
          "shop_code",
          "shop_name",
          "full_name",
          "parent_shop_id",
          "area_code",
          "phone",
          "mobiphone",
          "shop_type_id",
          "shop_type",
          "shop_channel",
          "email",
          "address",
          "tax_num",
          "invoice_number_account",
          "invoice_bank_name",
          "status",
          "lat",
          "lng",
          "fax",
          "area_id",
          "bill_to",
          "ship_to",
          "contact_name",
          "distance_order",
          "distance_training",
          "shop_location",
          "under_shop",
          "price_type",
          "organization_id",
          "abbreviation",
          "debit_date_limit",
          "order_view",
          "sale_area",
          "name_text",
          "create_date",
          "create_user",
          "update_date",
          "update_user",
          
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
