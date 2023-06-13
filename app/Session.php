<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:43 AM
 */

namespace App;


class Session extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string,
     *
     */
    protected $table = 'sessions';

    protected $fillable = [
        'id',
        'session_id',
        'ip',
        'general_string',
        'store_id',
        'phone',
        'user_agent',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
        'deleted_at'
    ];
}
