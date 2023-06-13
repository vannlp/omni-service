<?php

/**
 * Created by PhpStorm.
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 3:12 PM
 */

namespace App;

/**
 * Class SalePrice
 * @package App
 */
class CdpLogs extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'cdp_logs';


    /**
     * @var array
     */
    protected $fillable = [
        'param',
        'param_request',
        'content',
        'response',
        'code',
        'sync_type',
        'count_repost',
        'status',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at',
        'deleted_by',
        'deleted',
        'function_request',
    ];

    public function orders()
    {
        return $this->hasOne(Order::class, 'code', 'order_code');
    }
}
