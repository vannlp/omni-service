<?php


namespace App;


class NotificationHistory extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'notification_histories';

    protected $fillable = [
        "title",
        "body",
        "message",
        "notify_type",
        "type",
        "item_id",
        "extra_data",
        "receiver",
        "action",
        "company_id",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function notify()
    {
        return $this->hasOne(__NAMESPACE__ . '\Notify', 'id', 'item_id')
            ->join($this->getTable(), $this->getTable() . '.item_id', '=', 'notifies.id')
            ->where($this->getTable() . '.notify_type', '=', 'SYSTEM');
    }

    public function userStatus($userId)
    {
        return $this->hasOne(__NAMESPACE__ . '\UserStatusNotification', 'notification_id', 'id')
            ->where('user_id', '=', $userId)->first();
    }
}