<?php


namespace App\V1\Models;


use App\OrderDetail;
use App\ShipOrderDetail;

class ShipOrderDetailModel extends AbstractModel
{
    /**
     * OrderDetailModel constructor.
     *
     * @param OrderDetail|null $model
     */
    public function __construct(ShipOrderDetail $model = null)
    {
        parent::__construct($model);
    }
}