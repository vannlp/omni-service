<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\User;
use App\V1\CMS\Transformers\Log\LogUserTransformer;
use App\V1\Models\UserLogModel;
use App\V1\Transformers\UserLog\UserLogTransformer;
use Illuminate\Http\Request;

class UserLogController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new UserLogModel();
    }

    public function view(Request $request, UserLogTransformer $userLogTransformer)
    {
        $input = $request->all();
        try {
            $logs = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->paginator($logs, $userLogTransformer);
    }
}