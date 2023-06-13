<?php


namespace App;


class ReplyContactSupport extends BaseModel
{
    /**
     * @var string
     */
    protected $table = 'reply_contact_supports';
    /**
     * @var array
     */
    protected $fillable = [
        "contact_support_id",
        "content_reply",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function createdBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'created_by');
    }

    public function updatedBy()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'updated_by');
    }

    public function user()
    {
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_reply');
    }
}