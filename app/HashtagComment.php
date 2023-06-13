<?php

namespace App;

use App\Supports\DataUser;

class HashtagComment extends BaseModel
{
    public static $current;

    protected $table = 'hashtag_comments';

    protected $fillable = [
        'id',
        'type',
        'code',
        'content',
        'is_choose',
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