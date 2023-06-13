<?php

namespace App;

class LiveStreamingSchedule extends BaseModel
{
    protected $table = 'live_streaming_schedules';

    protected $fillable = [
        'name',
        'user_id',
        'start_time',
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
