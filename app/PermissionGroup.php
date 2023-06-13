<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App;

/**
 * Class PermissionGroup
 *
 * @package App
 */
class PermissionGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'permission_groups';


    /**
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'deleted',
        'updated_by',
        'created_by',
        'updated_at',
        'created_at',
    ];

    public function permissions()
    {
        return $this->hasMany(__NAMESPACE__ . '\Permission', 'group_id', 'id');
    }
}
