<?php


namespace App;


class ContactSupport extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'contact_supports';
    /**
     * @var array
     */
    protected $fillable = [
        "subject",
        "content",
        "category",
        "user_id",
        "status",
        "attached_image",
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
        return $this->hasOne(__NAMESPACE__ . '\User', 'id', 'user_id');
    }

    public function details()
    {
        return $this->hasMany(__NAMESPACE__ . '\ContactSupportDetail', 'contact_support_id', 'id');
    }
}