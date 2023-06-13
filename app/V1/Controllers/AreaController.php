<?php
/**
 * User: dai.ho
 * Date: 5/06/2020
 * Time: 10:51 AM
 */

namespace App\V1\Controllers;


use App\Area;
use App\Store;
use App\Supports\Log;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\Exports\AreaExport;
use App\Supports\Message;
use Maatwebsite\Excel\Facades\Excel;
use App\V1\Models\AreaModel;
use App\V1\Transformers\Area\AreaClientTransformer;
use App\V1\Transformers\Area\AreaTransformer;
use App\V1\Validators\Area\AreaCreateValidator;
use App\V1\Validators\Area\AreaUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaController extends BaseController
{
    protected $model;

    /**
     * AreaController constructor.
     */
    public function __construct()
    {
        $this->model = new AreaModel();
    }

    public function search(Request $request, AreaTransformer $areaTransformer)
    {
        $input = $request->all();

        try {
            if (!empty($input['name'])) {
                $input['name'] = ['like' => "%{$input['name']}%"];
            }
            if (!empty($input['code'])) {
                $input['code'] = ['like' => "%{$input['code']}%"];
            }
            if (!empty($input['store_id'])) {
                $input['store_id'] = ['=' => $input['store_id']];
            }
            $company_id = TM::getCurrentCompanyId();
            $input['company_id'] = ['=' => $company_id];
            $areas = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($areas, $areaTransformer);
    }

    /**
     * @param $id
     * @param AreaTransformer $areaTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, AreaTransformer $areaTransformer)
    {
        try {
            $area = $this->model->getFirstWhere(['id' => $id]);
            if (empty($area)) {
                $area = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($area, $areaTransformer);
    }

    public function store(
        Request $request,
        AreaCreateValidator $areaCreateValidator,
        AreaTransformer $areaTransformer
    ) {
        $input = $request->all();
        $areaCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $areaCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $area = $this->model->upsert($input);
            Log::view($this->model->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($area, $areaTransformer);
    }

    public function update(
        $id,
        Request $request,
        AreaUpdateValidator $areaUpdateValidator,
        AreaTransformer $areaTransformer
    ) {
        $input = $request->all();
        $input['id'] = $id;
        $areaUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        if(!empty($input['description'])){
            $input['description'] = str_clean_special_characters($input['description']);
        }
        $areaUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $area = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $area->id, null, $area->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($area, $areaTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $area = Area::find($id);
            if (empty($area)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Area
            $area->delete();
            Log::delete($this->model->getTable(), "#ID:" . $area->id . "-" . $area->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }

    public function areaExportExcel(){
        //ob_end_clean();
        $area = Area::model()->get();
        //ob_start();
        return Excel::download(new AreaExport($area), 'list_area.xlsx');
    }

    ########################################### NOT AUTHENTICATION ############################################

    public function getClientArea(Request $request, AreaClientTransformer $areaClientTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }

        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['store_id'] = $store_id;
        $areas = $this->model->search($input, [], $limit);
        return $this->response->paginator($areas, $areaClientTransformer);
    }
}
