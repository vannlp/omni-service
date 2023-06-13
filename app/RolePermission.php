<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

/**
 * Class RolePermission
 *
 * @package App
 */
class RolePermission extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'role_permissions';


    /**
     * @var array
     */
    protected $fillable = [
        'role_id',
        'permission_id',
        'description',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function permission()
    {
        return $this->hasOne(__NAMESPACE__ . '\Permission', 'id', 'permission_id');
    }
}
