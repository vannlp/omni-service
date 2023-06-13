<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

/**
 * Class Role
 *
 * @package App
 */
class Role extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'roles';


    /**
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'status',
        'description',
        'is_active',
        'role_level',
        'role_group',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function rolePermission()
    {
        return $this->hasMany(__NAMESPACE__ . '\RolePermission', 'role_id', 'id');
    }
    public function roleGroup()
    {
        return $this->belongsTo(RoleGroup::class, 'role_group', 'id');
    }

}
