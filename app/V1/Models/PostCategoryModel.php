<?php


namespace App\V1\Models;


use App\PostCategory;

class PostCategoryModel extends AbstractModel
{
    /**
     * PostCategoryModel constructor.
     * @param PostCategory|null $model
     */
    public function __construct(PostCategory $model = null)
    {
        parent::__construct($model);
    }
}
