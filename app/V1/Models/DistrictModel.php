<?php
/**
 * User: Ho Sy Dai
 * Date: 9/28/2018
 * Time: 10:11 AM
 */

namespace App\V1\Models;


use App\District;

/**
 * Class DistrictModel
 * @package App\V1\Models
 */
class DistrictModel extends AbstractModel
{
    /**
     * DistrictModel constructor.
     * @param District|null $model
     */
    public function __construct(District $model = null)
    {
        parent::__construct($model);
    }
}