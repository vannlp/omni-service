<?php


namespace App\V1\Models;


use App\ProductFavorite;

class ProductFavoriteModel extends AbstractModel
{
    /**
     * ProductFavoriteModel constructor.
     * @param ProductFavorite|null $model
     */
    public function __construct(ProductFavorite $model = null)
    {
        parent::__construct($model);
    }

}