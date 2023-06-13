<?php


namespace App\V1\Models;


use App\Blog;

class BlogModel extends AbstractModel
{
    public function __construct(Blog $model = null)
    {
        parent::__construct($model);
    }
}