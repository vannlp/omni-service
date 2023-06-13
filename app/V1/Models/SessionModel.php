<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:48 AM
 */

namespace App\V1\Models;


use App\Area;
use App\Session;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Arr;

class SessionModel extends AbstractModel
{
    public function __construct(Session $model = null)
    {
        parent::__construct($model);
    }
}