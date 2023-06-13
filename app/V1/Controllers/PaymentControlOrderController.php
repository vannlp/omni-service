<?php
/**
 * User: kpistech2
 * Date: 2020-07-02
 * Time: 22:58
 */

namespace App\V1\Controllers;


use App\PaymentControlOrder;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\PaymentControlOrderModel;
use App\V1\Transformers\PaymentControlOrder\PaymentControlOrderTransformer;
use App\V1\Validators\PaymentControlOrder\PaymentControlOrderCreateValidator;
use App\V1\Validators\PaymentControlOrder\PaymentControlOrderUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentControlOrderController extends BaseController
{
    protected $model;

    /**
     * PaymentControlOrderController constructor.
     */
    public function __construct()
    {
        $this->model = new PaymentControlOrderModel();
    }

    /**
     * @param Request $request
     * @param PaymentControlOrderTransformer $paymentControlOrderTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function search(Request $request, PaymentControlOrderTransformer $paymentControlOrderTransformer)
    {
        $input = $request->all();

        try {
            $items = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($items, $paymentControlOrderTransformer);
    }

    /**
     * @param $id
     * @param PaymentControlOrderTransformer $paymentControlOrderTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function detail($id, PaymentControlOrderTransformer $paymentControlOrderTransformer)
    {
        try {
            $item = $this->model->getFirstWhere(['id' => $id]);
            if (empty($item)) {
                $item = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($item, $paymentControlOrderTransformer);
    }

    /**
     * @param Request $request
     * @param PaymentControlOrderCreateValidator $paymentControlOrderCreateValidator
     * @param PaymentControlOrderTransformer $paymentControlOrderTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function store(
        Request $request,
        PaymentControlOrderCreateValidator $paymentControlOrderCreateValidator,
        PaymentControlOrderTransformer $paymentControlOrderTransformer
    ) {
        $input = $request->all();
        $paymentControlOrderCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $item = $this->model->upsert($input);
            Log::view($this->model->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($item, $paymentControlOrderTransformer);
    }

    /**
     * @param $id
     * @param Request $request
     * @param PaymentControlOrderUpdateValidator $paymentControlOrderUpdateValidator
     * @param PaymentControlOrderTransformer $paymentControlOrderTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function update(
        $id,
        Request $request,
        PaymentControlOrderUpdateValidator $paymentControlOrderUpdateValidator,
        PaymentControlOrderTransformer $paymentControlOrderTransformer
    ) {
        $input = $request->all();
        $input['id'] = $id;
        $paymentControlOrderUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $item = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $item->id, null, $item->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($item, $paymentControlOrderTransformer);
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $item = PaymentControlOrder::find($id);
            if (empty($item)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete
            $item->delete();
            Log::delete($this->model->getTable(), "#ID:" . $item->id . "-" . $item->order_code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }

    /**
     * @return array
     */
    public function getOverview()
    {
        $data = $this->model->getOverview();

        return ['status' => 'OK', 'data' => $data];
    }
}
