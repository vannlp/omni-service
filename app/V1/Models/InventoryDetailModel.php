<?php


namespace App\V1\Models;


use App\InventoryDetail;

class InventoryDetailModel extends AbstractModel
{
    /**
     * InventoryDetailModel constructor.
     * @param InventoryDetail|null $model
     */
    public function __construct(InventoryDetail $model = null)
    {
        parent::__construct($model);
    }
}