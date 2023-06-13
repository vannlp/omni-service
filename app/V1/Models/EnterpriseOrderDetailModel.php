<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:21 AM
 */

namespace App\V1\Models;


use App\EnterpriseOrderDetail;

class EnterpriseOrderDetailModel extends AbstractModel
{
    /**
     * EnterpriseOrderDetailModel constructor.
     * @param EnterpriseOrderDetail|null $model
     */
    public function __construct(EnterpriseOrderDetail $model = null)
    {
        parent::__construct($model);
    }
}
