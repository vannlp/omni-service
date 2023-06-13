<?php


namespace App\V1\Models;


use App\Post;

class PostModel extends AbstractModel
{
    /**
     * PostModel constructor.
     * @param Post|null $model
     */
    public function __construct(Post $model = null)
    {
        parent::__construct($model);
    }
}
