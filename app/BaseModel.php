<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseModel extends Model
{
    use SoftDeletes;
    protected $dates = ['deleted_at'];

    public static function boot()
    {
        parent::boot();
        // Write Log
        static::creating(function ($model) {
            $user_id = TM::getCurrentUserId(true);
            //$str = "USER: #";
//            if(empty($user_id)){
//                $str = "CUSTOMER: #";
//                $user_id = CUS::getCurrentCustomerId();
//            }
            if(!empty($user_id)){
                $model->updated_by = $user_id;
            }
//            $user = !empty($user_id) ? $str.$user_id : 0;
//            $model->created_by = $user_id;
            // $model->updated_by = $user_id;
            $date = date('Y-m-d H:i:s', time());
            $model->created_at = $date;
            // $model->updated_at = $date;
            //Log::create($model->getTable(), $model->toArray());
        });

        static::updating(function ($model) {
            $user_id = TM::getCurrentUserId(true);
//            $str = "USER: #";
//            if(empty($user_id)){
//                $str = "CUSTOMER: #";
//                $user_id = CUS::getCurrentCustomerId();
//            }
//            $user = !empty($user_id) ? $str.$user_id : 0;
            // $model->created_by = $user_id;
            $model->updated_by = $user_id;
        });

        static::saving(function ($model) {
            $user_id = TM::getCurrentUserId(true);
//            $str = "USER: #";
//            if(empty($user_id)){
//                $str = "CUSTOMER: #";
//                $user_id = CUS::getCurrentCustomerId();
//            }
//            $user = !empty($user_id) ? $str.$user_id : 0;
            $model->updated_by = $user_id;
            $model->updated_at = date('Y-m-d H:i:s', time());
        });

        static::deleting(function ($model) {
            //Log::delete($model->getTable(), $model->toArray());
            $user_id = TM::getCurrentUserId();
//            $str = "USER: #";
//            if(empty($user_id)){
//                $str = "CUSTOMER: #";
//                $user_id = CUS::getCurrentCustomerId();
//            }
//            $user = !empty($user_id) ? $str.$user_id : 0;

            $model->deleted = 1;
            $model->deleted_by = $user_id;
            $model->save();
        });
    }

    /**
     * @return array
     */
    public function getTableColumns()
    {
        return $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());
    }

    /**
     * @param $query
     * @param int $is_active
     */
    public function filterData(&$query, $is_active = 1)
    {
        $query->where($this->getTable() . '.is_active', $is_active);
    }

    public static final function model()
    {
        $classStr = get_called_class();
        /** @var Model $class */
        $class = new $classStr();
        return $class::whereNull($class->getTable() . '.deleted_at');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function userCreated()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function userUpdated()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
}