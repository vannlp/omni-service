<?php


namespace App\V1\Controllers;


use App\Supports\Message;
use App\Supports\TM_Error;
use App\V1\Validators\WarehouseCreateValidator;
use App\V1\Validators\WarehouseUpdateValidator;
use App\V1\Models\WarehouseModel;
use App\V1\Transformers\Warehouse\WarehouseTransformer;
use App\Warehouse;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\WarehouseExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends BaseController
{
    protected $model;

    /**
     * WarehouseController constructor.
     * @param WarehouseModel $model
     */
    public function __construct(WarehouseModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param WarehouseTransformer $warehouseTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, WarehouseTransformer $warehouseTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $warehouse = $this->model->search($input, [], $limit);
        return $this->response->paginator($warehouse, $warehouseTransformer);
    }

    /**
     * @param $id
     * @param WarehouseTransformer $warehouseTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, WarehouseTransformer $warehouseTransformer)
    {
        $warehouse = Warehouse::find($id);
        if (empty($warehouse)) {
            return ['data' => []];
        }
        return $this->response->item($warehouse, $warehouseTransformer);
    }

    /**
     * @param Request $request
     * @param WarehouseCreateValidator $warehouseCreateValidator
     * @return array|void
     */
    public function create(Request $request, WarehouseCreateValidator $warehouseCreateValidator)
    {
        $input = $request->all();
        $warehouseCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $warehouseCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $warehouse = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("warehouses.create-success", $warehouse->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param WarehouseUpdateValidator $warehouseUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, WarehouseUpdateValidator $warehouseUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $warehouseUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $warehouseUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $warehouse = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("warehouses.update-success", $warehouse->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $warehouse = Warehouse::find($id);
            if (empty($warehouse)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $warehouse->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("warehouses.delete-success", $warehouse->name)];
    }
    public function warehouseExportExcel(){
        //ob_end_clean();
        $warehouse = Warehouse::model()->get();
        //ob_start(); // and this
        return Excel::download(new WarehouseExport($warehouse), 'list_warehouse.xlsx');
    }
}