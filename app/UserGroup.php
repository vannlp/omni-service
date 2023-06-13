<?php
/**
 * User: dai.ho
 * Date: 14/05/2020
 * Time: 4:28 PM
 */

namespace App;

class UserGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_groups';

    protected $fillable = [
        "code",
        "name",
        "description",
        "company_id",
        "is_view",
        "is_view_app",
        "is_default",
        "is_active",
        "deleted",
        "created_at",
        "created_by",
        "updated_at",
        "updated_by",
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'group_id', 'id');
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'id', 'company_id');
    }

    public function children()
    {
        return $this->hasMany(UserGroup::class, 'parent_id', 'id');
    }
}