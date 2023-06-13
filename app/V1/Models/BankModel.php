<?php


namespace App\V1\Models;


use App\Bank;

class BankModel extends AbstractModel
{
    /**
     * BankModel constructor.
     * @param Bank|null $model
     */
    public function __construct(Bank $model = null)
    {
        parent::__construct($model);
    }
}