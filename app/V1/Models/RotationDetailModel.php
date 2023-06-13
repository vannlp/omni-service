<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\Supports\Message;
use App\RotationDetail;
use App\TM;
use phpDocumentor\Reflection\Types\Nullable;

class RotationDetailModel extends AbstractModel
{
    public function __construct(RotationDetail $model = null)
    {
        parent::__construct($model);
    }
}
