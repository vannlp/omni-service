<?php


namespace App\V1\Controllers;


use App\Company;
use App\ShipOrder;
use App\ShipOrderDetail;
use App\ShippingOrder;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Supports\TM_PDF;
use App\TM;
use App\V1\Models\ShipOrderModel;
use App\V1\Transformers\ShipOrder\ShipOrderTransformer;
use App\V1\Validators\ShipOrderCreateValidator;
use App\V1\Validators\ShipOrderUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShipOrderController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new ShipOrderModel();
    }

    public function search(Request $request, ShipOrderTransformer $shipOrderTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $shipOrder = $this->model->search($input, [], $limit);
        return $this->response->paginator($shipOrder, $shipOrderTransformer);
    }

    public function view($id, ShipOrderTransformer $shipOrderTransformer)
    {
        $shipOrder = ShipOrder::find($id);
        if (empty($shipOrder)) {
            return ['data' => []];
        }
        return $this->response->item($shipOrder, $shipOrderTransformer);
    }

    public function create(
        Request $request,
        ShipOrderCreateValidator $shipOrderCreateValidator,
        ShipOrderTransformer $shipOrderTransformer
    )
    {
        $input = $request->all();
        $shipOrderCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $shipOrder = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($shipOrder, $shipOrderTransformer);
    }

    public function update(
        $id,
        Request $request,
        ShipOrderUpdateValidator $shipOrderUpdateValidator,
        ShipOrderTransformer $shipOrderTransformer
    )
    {

        $input = $request->all();
        $input['id'] = $id;
        $shipOrderUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $shipOrder = ShipOrder::find($input['id']);
            if (empty($shipOrder)) {
                return $this->response->errorBadRequest(Message::get("ship_orders.not-exist", "#{$input['id']}"));
            }
            $this->model->upsert($input);

            $shipOrder = $this->model->search(['id' => $id], [], 1);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($shipOrder, $shipOrderTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $shipOrder = ShipOrder::find($id);
            if (empty($shipOrder)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Ship Order Detail
            ShipOrderDetail::model()->where('ship_id', $id)->delete();
            // 2. Delete Ship Order
            $shipOrder->delete();
            DB::commit();

        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("ship_orders.delete-success", $shipOrder->code)];
    }
}