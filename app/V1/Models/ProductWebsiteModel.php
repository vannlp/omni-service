<?php


namespace App\V1\Models;


use App\ProductWebsite;

class ProductWebsiteModel extends AbstractModel
{
    /**
     * CityModel constructor.
     * @param ProductWebsite|null $model
     */
    public function __construct(ProductWebsite $model = null)
    {
        parent::__construct($model);
    }
}