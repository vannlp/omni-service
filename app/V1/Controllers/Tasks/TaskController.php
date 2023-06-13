<?php

namespace App\V1\Controllers\Tasks;

use App\Task;
use App\V1\Controllers\BaseController;
use App\V1\Models\TaskModel;
use Illuminate\Http\Request;

class TaskController extends BaseController
{
    protected $taskModel;

    public function __construct(TaskModel $taskModel = null)
    {
        $this->taskModel = $taskModel ?? new TaskModel();
    }

    public function search(Request $request)
    {
        $input = $request->all();
        var_dump($this->taskModel->all());die();
//        $results = $this->taskModel->search($input);

//        return $this->response->paginator($this->taskModel->search($input));
    }
}