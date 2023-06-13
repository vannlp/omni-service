<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:24
 */

namespace App\V1\Controllers;


use App\Exports\UnitExport;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\Unit;
use App\V1\Models\UnitModel;
use App\V1\Transformers\Unit\UnitTransformer;
use App\V1\Validators\Unit\UnitCreateValidator;
use App\V1\Validators\Unit\UnitUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class UnitController extends BaseController
{
    protected $model;

    /**
     * UnitController constructor.
     */
    public function __construct()
    {
        $this->model = new UnitModel();
    }

    public function search(Request $request, UnitTransformer $unitTransformer)
    {
        $input = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        $input['store_id'] = TM::getCurrentStoreId();
        try {
            $units = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($units, $unitTransformer);
    }

    /**
     * @param $id
     * @param UnitTransformer $unitTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, UnitTransformer $unitTransformer)
    {
        try {
            $unit = Unit::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($unit)) {
                $unit = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($unit, $unitTransformer);
    }

    public function store(
        Request $request,
        UnitCreateValidator $unitCreateValidator,
        UnitTransformer $unitTransformer
    )
    {
        $input = $request->all();
        $unitCreateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $unitCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $store = Store::model()->where([
                'id'         => $input['store_id'],
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($store)) {
                return $this->response->errorBadRequest(Message::get("V002", "Store ID #{$input['store_id']}"));
            }
            $unit = $this->model->upsert($input);
            Log::view($this->model->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($unit, $unitTransformer);
    }

    public function update(
        $id,
        Request $request,
        UnitUpdateValidator $unitUpdateValidator,
        UnitTransformer $unitTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $unitUpdateValidator->validate($input);
        $input['name'] = str_clean_special_characters($input['name']);
        $input['code'] = str_clean_special_characters($input['code']);
        $unitUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $store = Store::model()->where([
                'id'         => $input['store_id'],
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($store)) {
                return $this->response->errorBadRequest(Message::get("V002", "Store ID #{$input['store_id']}"));
            }
            $unit = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $unit->id, null, $unit->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($unit, $unitTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $unit = Unit::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($unit)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Unit
            $unit->delete();
            Log::delete($this->model->getTable(), "#ID:" . $unit->id . "-" . $unit->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }

    public function unitExportExcel(){
        //ob_end_clean();
        $units = Unit::model()->get();
        //ob_start(); // and this
        return Excel::download(new UnitExport($units), 'list_unit.xlsx');
    }
}
