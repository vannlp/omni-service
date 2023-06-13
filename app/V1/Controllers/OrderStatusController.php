<?php


namespace App\V1\Controllers;


use App\OrderStatus;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\OrderStatusModel;
use App\V1\Transformers\OrderStatus\OrderStatusTransformer;
use App\V1\Validators\OrderStatus\OrderStatusCreateValidator;
use App\V1\Validators\OrderStatus\OrderStatusUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderStatusController extends BaseController
{
    /**
     * @var OrderStatusModel
     */
    protected $model;

    /**
     * OrderStatusController constructor.
     */
    public function __construct()
    {
        $this->model = new OrderStatusModel();
    }

    /**
     * @param Request $request
     * @param OrderStatusTransformer $orderStatusTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, OrderStatusTransformer $transformer)
    {
        $input = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        $limit = array_get($input, 'limit', 20);
        $result = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $transformer);
    }

    /**
     * @param $id
     * @param OrderStatusTransformer $transformer
     * @return \Dingo\Api\Http\Response|null[]
     */
    public function detail($id, OrderStatusTransformer $transformer)
    {
        $result = OrderStatus::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
        if (empty($result)) {
            return ['data' => null];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $transformer);
    }

    /**
     * @param Request $request
     * @param OrderStatusCreateValidator $validator
     * @return array|void
     */
    public function create(Request $request, OrderStatusCreateValidator $validator)
    {
        $input = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("order_status.create-success", $result->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param OrderStatusUpdateValidator $validator
     * @return array|void
     */
    public function update($id, Request $request, OrderStatusUpdateValidator $validator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $input['company_id'] = TM::getCurrentCompanyId();
        $validator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("order_status.update-success", $result->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $result = OrderStatus::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $result->delete();
            Log::delete($this->model->getTable(), "#ID:" . $result->id);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("order_status.delete-success", $result->name)];
    }
}