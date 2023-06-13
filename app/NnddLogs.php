<?php

namespace App;

class NnddLogs extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'nndd_logs';


    /**
     * @var array
     */
    protected $fillable = [
        'params',
        'params_request',
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
}
