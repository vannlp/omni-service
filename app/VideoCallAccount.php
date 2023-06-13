<?php


namespace App;


class VideoCallAccount extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'video_call_accounts';

    /**
     * @var array
     */
    protected $fillable = [
        'user_id',
        'phone',
        'password',
        'company_id',
        'deleted',
        'created_at',
        'created_by',
        'updated_at',
        'updated_by',
        'deleted_at',
        'deleted_by'
    ];
}