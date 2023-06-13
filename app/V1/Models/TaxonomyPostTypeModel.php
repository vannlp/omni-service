<?php


namespace App\V1\Models;


use App\TaxonomyPostType;

class TaxonomyPostTypeModel extends AbstractModel
{
    public function __construct(TaxonomyPostType $model = null)
    {
        parent::__construct($model);
    }
}