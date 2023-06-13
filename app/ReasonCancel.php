<?php

namespace App;

use App\Supports\DataUser;

class ReasonCancel extends BaseModel
{
    public static $current;

    protected $table = 'reason_cancels';

    protected $fillable = [
        'id',
        'code',
        'is_choose',
        'value',
        'group_reason',
        'is_description',  
        'type',
        'company_id',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
    ];
    public function scopeSearch($query, $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if ($company_id = $request->get('company_id')) {
            $query->whereRaw("company_id LIKE '%{$company_id}%'");
        }
        return $query;
    }
}