<?php
/**
 * User: Administrator
 * Date: 12/10/2018
 * Time: 06:34 PM
 */

namespace App\V1\Models;


use App\RolePermission;

class RolePermissionModel extends AbstractModel
{
    public function __construct(RolePermission $model = null)
    {
        parent::__construct($model);
    }
}