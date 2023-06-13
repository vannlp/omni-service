<?php
namespace App;
class ShipFeeNJV extends BaseModel
{
       /**
        * @var string
        */
       protected $table = 'ship_fee_NJV';

       protected $fillable = [
              "code",
              "type_ship",
              "price_ship",
              "weight",
              "deleted",
              "created_at",
              "created_by",
              "updated_at",
              "updated_by",
              "deleted_at",
              "deleted_by",
       ];
}
