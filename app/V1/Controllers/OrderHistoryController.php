<?php


namespace App\V1\Controllers;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\OrderHistoryModel;
use App\V1\Transformers\OrderHistory\OrderHistoryTransformer;
use App\V1\Validators\OrderHistoryUpsertValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderHistoryController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new OrderHistoryModel();
    }

    public function search(Request $request, OrderHistoryTransformer $orderHistoryTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $order_histories = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($order_histories, $orderHistoryTransformer);
    }
}