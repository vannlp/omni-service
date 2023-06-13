<?php

namespace App;

class SyncLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sync_logs';

    /**
     * @var array
     */
    protected $fillable = [
        'issue',
        'type',
        'store_id',
        'from',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];
}
