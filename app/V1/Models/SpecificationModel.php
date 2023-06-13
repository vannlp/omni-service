<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:23
 */

namespace App\V1\Models;


use App\Specification;
use App\Supports\Message;
use App\TM;

class SpecificationModel extends AbstractModel
{
    public function __construct(Specification $model = null)
    {
        parent::__construct($model);
    }
}