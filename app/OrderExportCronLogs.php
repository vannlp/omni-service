<?php

/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 22:53
 */

namespace App;


class OrderExportCronLogs extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'order_export_cron_logs';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'code',
        'message',
        'params',
        'endpoint',
        'function',
        'created_at',
        'updated_at',
        'updated_by'
    ];
}
