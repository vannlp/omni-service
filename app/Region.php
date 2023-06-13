<?php

/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class Region extends BaseModel
{
     /**
      * The table associated with the model.
      *
      * @var string
      */
     protected $table = 'regions';

     protected $fillable = [
          'code',
          'name',
          'distributor_id',
          'distributor_code',
          'distributor_name',
          'city_code',
          'city_full_name',
          'district_code',
          'district_full_name',
          'ward_code',
          'ward_full_name',
          'company_id',
          'store_id',
          'deleted',
          'updated_by',
          'created_by',
          'deleted_by',
          'updated_at',
          'created_at',
          'deleted_at',
     ];


     public function createdBy()
     {
          return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
     }

     public function updatedBy()
     {
          return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
     }
     public function sector()
     {
          return $this->hasMany(Sector::class, 'region_id', 'id');
     }
}
