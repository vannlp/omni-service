<?php
/**
 * User: Ho Sy Dai
 * Date: 9/28/2018
 * Time: 10:12 AM
 */

namespace App\V1\Models;


use App\Ward;

/**
 * Class WardModel
 * @package App\V1\CMS\Models
 */
class WardModel extends AbstractModel
{
    /**
     * WardModel constructor.
     * @param Ward|null $model
     */
    public function __construct(Ward $model = null)
    {
        parent::__construct($model);
    }
}