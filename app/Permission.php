<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

/**
 * Class Permission
 *
 * @package App
 */
class Permission extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permissions';


    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'group_id',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function permissionGroup()
    {
        return $this->hasOne(__NAMESPACE__ . '\PermissionGroup', 'id', 'group_id');
    }
}
