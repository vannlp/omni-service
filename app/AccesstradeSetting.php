<?php

namespace App;

class AccessTradeSetting extends BaseModel
{
    protected $table = 'access_trade_settings';

    protected $fillable = [
        'key',
        'value',
        'campaign_id',
        'category_id',
        'company_id',
        'store_id',
        'deleted',
        'deleted_at',
        'created_at',
        'updated_at',
        'deleted_at',
        'created_by',
        'updated_by',
    ];
}
