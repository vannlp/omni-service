<?php

/**
 * User: kpistech2
 * Date: 2020-06-08
 * Time: 22:43
 */

namespace App;


class Feedback extends BaseModel
{
     /**
      * The table associated with the model.
      *
      * @var string
      */
     protected $table = 'feedbacks';

     protected $fillable = [
          'title',
          'content',
          'user_id',
          'company_id',
          'image',
          'deleted',
          'updated_by',
          'created_by',
          'updated_at',
          'created_at',
     ];
     public function createdBy()
     {
          return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
     }

     public function updatedBy()
     {
          return $this->hasOne(User::class, 'id', 'updated_by');
     }

     public function company()
     {
          return $this->hasOne(Company::class, 'id', 'company_id');
     }

     public function user()
     {
          return $this->hasOne(User::class, 'id', 'user_id');
     }
}
