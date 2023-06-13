<?php
/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 07:54 PM
 */

namespace App\V1\Models;


use App\CustomerProfile;

class CustomerProfileModel extends AbstractModel
{
    /**
     * CityModel constructor.
     * @param CustomerProfile|null $model
     */
    public function __construct(CustomerProfile $model = null)
    {
        parent::__construct($model);
    }
}