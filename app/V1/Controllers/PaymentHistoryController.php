<?php


namespace App\V1\Controllers;


use App\PaymentHistory;
use App\Supports\Log;
use App\V1\Models\PaymentHistoryModel;
use App\V1\Transformers\PaymentHistory\PaymentHistoryTransformer;
use Illuminate\Http\Request;

class PaymentHistoryController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new PaymentHistoryModel();
    }

    /**
     * @param Request $request
     * @param PaymentHistoryTransformer $paymentHistoryTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, PaymentHistoryTransformer $paymentHistoryTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $item = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($item, $paymentHistoryTransformer);
    }

    /**
     * @param $id
     * @param PaymentHistoryTransformer $paymentHistoryTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, PaymentHistoryTransformer $paymentHistoryTransformer)
    {
        $item = PaymentHistory::find($id);
        if (empty($item)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($item, $paymentHistoryTransformer);
    }
}