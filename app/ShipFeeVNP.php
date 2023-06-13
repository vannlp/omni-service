<?php
namespace App;
class ShipFeeVNP extends BaseModel
{
       /**
        * @var string
        */
       protected $table = 'ship_fee_VNP';

       protected $fillable = [
              "code",
              "type_ship",
              "type",
              "price_ship_zero_two",
              "price_ship_two_five",
              "price_five_ten",
              "price_ship_over_ten",
              "deleted",
              "created_at",
              "created_by",
              "updated_at",
              "updated_by",
              "deleted_at",
              "deleted_by",
       ];
}
