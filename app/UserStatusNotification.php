<?php
/**
 * User: kpistech2
 * Date: 2019-11-15
 * Time: 19:55
 */

namespace App;


class UserStatusNotification extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_status_notifications';

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'user_id',
        'notification_id',
        'read',
        'is_active',
        'deleted',
        'created_at',
        'created_by',
        'upadted_at',
        'updated_by',
        'deleted_at',
        'deleted_by',
    ];

    public function notificationHistory()
    {
        return $this->hasOne(__NAMESPACE__ . '\NotificationHistory', 'id', 'notification_id');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }
}
