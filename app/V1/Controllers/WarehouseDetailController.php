<?php


namespace App\V1\Controllers;


use App\TM;
use App\V1\Transformers\Warehouse\WarehouseDetailTransformer;
use App\V1\Models\WarehouseDetailModel;
use App\V1\Models\WarehouseModel;
use App\V1\Transformers\Warehouse\WarehouseDetailViewTransformer;
use App\WarehouseDetail;
use Illuminate\Http\Request;
use App\Exports\ExportWareHouseDetails;
use Maatwebsite\Excel\Facades\Excel;

class WarehouseDetailController extends BaseController
{
    protected $model;

    /**
     * WarehouseDetailController constructor.
     * @param WarehouseDetailModel $model
     */
    public function __construct(WarehouseDetailModel $model)
    {
        $this->model = $model;
    }

    public function search(Request $request, WarehouseDetailTransformer $warehouseDetailTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $warehouseDetail = $this->model->search($input, [], $limit);
        return $this->response->paginator($warehouseDetail, $warehouseDetailTransformer);
    }

    public function view($id, Request $request, WarehouseDetailTransformer $warehouseDetailViewTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['warehouse_id'] = $id;
        $warehouseDetail = $this->model->search($input, [], $limit);
        return $this->response->paginator($warehouseDetail, $warehouseDetailViewTransformer);
    }
    public function exportwarehousedetails(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $date  = date('YmdHis', time());
        $warehouseDetail = WarehouseDetail::with(['product', 'warehouse', 'batch', 'unit'])->where('company_id', TM::getCurrentCompanyId())
        ->get();
        //ob_start(); // and this
        return Excel::download(new ExportWareHouseDetails($warehouseDetail), 'list_orders_' . $date . '.xlsx');
    }

}