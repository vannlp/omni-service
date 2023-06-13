<?php


namespace App;


class CallHistory extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'call_histories';

    protected $fillable = [
        "caller_id",
        "receiver_id",
        "call_from_time",
        "call_end_time",
        "total_time",
        "vote",
        "is_active",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function callerId()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'caller_id');
    }
    public function receiverId()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'receiver_id');
    }
}