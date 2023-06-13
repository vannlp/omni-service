<?php
/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 09:54 PM
 */

namespace App\V1\Models;


use App\OrderDetail;

class OrderDetailModel extends AbstractModel
{
    /**
     * OrderDetailModel constructor.
     *
     * @param OrderDetail|null $model
     */
    public function __construct(OrderDetail $model = null)
    {
        parent::__construct($model);
    }
}
