<?php


namespace App\V1\Controllers;

use App\Category;
use App\Inventory;
use App\InventoryDetail;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Models\InventoryModel;
use App\V1\Transformers\Inventory\InventoryDetailTransformers;
use App\V1\Transformers\Inventory\InventoryTransformers;
use App\V1\Validators\InventoryCreateValidator;
use App\V1\Validators\InventoryUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends BaseController
{
    protected $model;

    /**
     * InventoryController constructor.
     */
    public function __construct()
    {
        $this->model = new InventoryModel();
    }

    /**
     * @return array
     */
    public function index()
    {
        return ['status' => '0k'];
    }

    /**
     * @param Request $request
     * @param InventoryTransformers $transformers
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, InventoryTransformers $transformers)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 100);
        $result = $this->model->search($input, [], $limit);
        return $this->response->paginator($result, $transformers);
    }

    public function searchDetails(Request $request, InventoryDetailTransformers $invoiceDetailTransformers)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 100);
        $invoiceDetail = $this->model->searchDetail($input, [], $limit);
        return $this->response->paginator($invoiceDetail, $invoiceDetailTransformers);
    }

    public function view($id, InventoryTransformers $invoiceTransformer)
    {
        $invoice = Inventory::model()->with(['details', 'user'])->where(
            'id',
            $id
        )->first();
        if (empty($invoice)) {
            return ['data' => []];
        }
        return $this->response->item($invoice, $invoiceTransformer);
    }

    public function create(
        Request $request,
        InventoryCreateValidator $invoiceCreateValidator
    ) {
        $input = $request->all();
        $invoiceCreateValidator->validate($input);
        // $input['code'] = str_clean_special_characters($input['code']);
        $invoiceCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $invoice = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("invoices.create-success", $invoice->code)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param InventoryUpdateValidator $invoiceUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, InventoryUpdateValidator $invoiceUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $invoiceUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $invoice = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("invoices.update-success", $invoice->code)];
    }

    /**
     * @param $id
     *
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $invoice = Inventory::find($id);
            if (empty($invoice)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            if ($invoice->status == INVOICE_STATUS_COMPLETED) {
                return $this->response->errorBadRequest(Message::get("V054", $invoice->code, $invoice->status));
            }
            //            if ($invoice->status == INVOICE_STATUS_COMPLETED && $invoice->type == INVOICE_TYPE_N) {
            //                $invoiceDetails = InventoryDetail::model()->where('invoice_id', $invoice->id)->get();
            //                foreach ($invoiceDetails as $invoiceDetail) {
            //                    $warehouseDetails = WarehouseDetail::model()->where([
            //                        'product_id'   => $invoiceDetail['product_id'],
            //                        'warehouse_id' => $invoiceDetail['warehouse_id'],
            //                        'batch_id'     => $invoiceDetail['batch_id'],
            //                        'company_id'   => TM::getCurrentCompanyId(),
            //                        'unit_id'      => $invoiceDetail['unit_id'],
            //                        'price'        => $invoiceDetail['price'],
            //                    ])->first();
            //                    if (empty($warehouseDetails)) {
            //                        return $this->response->errorBadRequest(Message::get("V055", $invoiceDetail['product_name']));
            //                    }
            //                    if ($warehouseDetails->quantity - $invoiceDetail['quantity'] < 0) {
            //                        return $this->response->errorBadRequest(Message::get("V053", $invoiceDetail['product_name']));
            //                    } else {
            //                        $warehouseDetails->quantity -= $invoiceDetail['quantity'];
            //                        $warehouseDetails->updated_at = date('Y-m-d H:i:s', time());
            //                        $warehouseDetails->updated_by = TM::getCurrentUserId();
            //                        if ($warehouseDetails->quantity == 0) {
            //                            DB::beginTransaction();
            //                            $queryDelete = "DELETE FROM `warehouse_details` WHERE `id` = {$warehouseDetails->id}";
            //                            DB::statement($queryDelete);
            //                            DB::commit();
            //                        }
            //                    }
            //                    $warehouseDetails->save();
            //                }
            //            }
            //            if ($invoice->status == INVOICE_STATUS_COMPLETED && $invoice->type == INVOICE_TYPE_X) {
            //                $invoiceDetails = InventoryDetail::model()->where('invoice_id', $invoice->id)->get();
            //                foreach ($invoiceDetails as $invoiceDetail) {
            //                    $warehouseDetails = WarehouseDetail::model()->where([
            //                        'product_id'   => $invoiceDetail['product_id'],
            //                        'warehouse_id' => $invoiceDetail['warehouse_id'],
            //                        'batch_id'     => $invoiceDetail['batch_id'],
            //                        'company_id'   => TM::getCurrentCompanyId(),
            //                        'unit_id'      => $invoiceDetail['unit_id'],
            //                        'price'        => $invoiceDetail['price'],
            //                    ])->first();
            //                    if (empty($warehouseDetails)) {
            //                        $param = [
            //                            "product_id"     => $invoiceDetail['product_id'],
            //                            "product_code"   => array_get($invoiceDetail, 'product_code', null),
            //                            "product_name"   => array_get($invoiceDetail, 'product_name', null),
            //                            "warehouse_id"   => $invoiceDetail['warehouse_id'],
            //                            "warehouse_code" => array_get($invoiceDetail, 'warehouse_code', null),
            //                            "warehouse_name" => array_get($invoiceDetail, 'warehouse_name', null),
            //                            "unit_id"        => $invoiceDetail['unit_id'],
            //                            "unit_code"      => array_get($invoiceDetail, 'unit_code', null),
            //                            "unit_name"      => array_get($invoiceDetail, 'unit_name', null),
            //                            "batch_id"       => $invoiceDetail['batch_id'],
            //                            "batch_code"     => array_get($invoiceDetail, 'batch_code', null),
            //                            "batch_name"     => array_get($invoiceDetail, 'batch_name', null),
            //                            "company_id"     => TM::getCurrentCompanyId(),
            //                            "price"          => $invoiceDetail['price'],
            //                            "quantity"       => $invoiceDetail['quantity'],
            //                        ];
            //                        $warehouseDetails->create($param);
            //                    }
            //                    if(!empty($warehouseDetails)){
            //                        $warehouseDetails->quantity += $invoiceDetail['quantity'];
            //                        $warehouseDetails->save();
            //                    }
            //                }
            //            }
            // 1. Delete Inventory detail
            InventoryDetail::model()->where('invoice_id', $id)->delete();
            // 2. Delete Inventory
            $invoice->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("invoices.delete-success", $invoice->code)];
    }

    public function exportList(Request $request)
    {
        $input = $request->all();
        // if (!isset($input['type'])) {
        //     return $this->response->errorBadRequest(Message::get("V009", "#Type"));
        // }

        try {
            // $type = INVENTORY_CODE_PREFIX[$input['type']];
            // $date = isset($input['created_at']) ? date('YmdHis', strtotime($input['created_at'])) : date(
            //     'YmdHis',
            //     time()
            // );
            $inventories    = Inventory::model()->get()->toArray();
            $date        = date('YmdHis', time());
            $dataInventory = [
                [
                    'STT',
                    'Mã Sản Phẩm/Dịch vụ',
                    'Tên Sản Phẩm/Dịch vụ',
                    'Giá bán/Phí Dịch vụ',
                    'Số lượng đặt hàng',
                    'Số lượng tồn kho',
                    'Doanh số',
                ],
            ];

            // foreach ($inventories as $key => $product) {
            //     $orderProduct  = Order::model()
            //         ->select([DB::raw("sum(od.qty) as qty"), DB::raw("sum(od.total) as total")])
            //         ->join('order_details as od', 'od.order_id', 'orders.id')
            //         ->where('product_id', $product['id'])
            //         ->whereDate('orders.created_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
            //         ->whereDate('orders.created_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
            //         ->get()->toArray();
            //     $dataProduct[] = [
            //         'stt'       => ++$key,
            //         'code'      => $product['code'],
            //         'name'      => $product['name'],
            //         'price'     => $product['price'],
            //         'order'     => $orderProduct[0]['qty'] ?? 0,
            //         'inventory' => $product['qty'] - ($orderProduct[0]['qty'] ?? 0),
            //         'turnover'  => $orderProduct[0]['total'] ?? 0,
            //     ];
            // }
            $this->ExcelExport("Inventory", storage_path('Export') . "/Inventory", $dataInventory);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }
}
