<?php

namespace App\V1\Models;

use App\Category;
use App\CategoryStore;

/**
 * Class CategoryModel
 * @package App\V1\CMS\Models
 */
class CategoryStoreModel extends AbstractModel
{
    /**
     * CategoryModel constructor.
     * @param Category|null $model
     */
    public function __construct(CategoryStore $model = null)
    {
        parent::__construct($model);
    }
}
