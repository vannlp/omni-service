<?php


namespace App;


class Consultant extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'consultants';

    protected $fillable = [
        "user_id",
        "consultant_id",
        "company_id",
        "title",
        "ext",
        "is_online",
        "socket_id",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function consultantVideoCallAccount()
    {
        return $this->hasOne(__NAMESPACE__ . '\VideoCallAccount', 'user_id', 'user_id');
    }
}