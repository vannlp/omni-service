<?php


namespace App;


class CustomerGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'customer_groups';

    protected $fillable = [
        "code",
        "name",
        "description",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function details()
    {
        return $this->hasOne(__NAMESPACE__ . '\CustomerGroupDetail', 'group_id', 'id');
    }

    public function customers()
    {
        return $this->hasMany(__NAMESPACE__ . '\Customer', 'group_id', 'id');
    }
}