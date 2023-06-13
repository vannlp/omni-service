<?php

namespace App\V1\Models;

use App\Task;

class TaskModel extends AbstractModel
{
    /**
     * AttributeModel constructor.
     *
     * @param Task|null $model
     */
    public function __construct(Task $model = null)
    {
        parent::__construct($model ?? new Task());
    }
}