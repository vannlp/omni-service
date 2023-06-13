<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:24
 */

namespace App\V1\Controllers;


use App\Exports\UnitExport;
use App\ReasonCancel;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\Unit;
use App\V1\Models\ReasonCancelModel;
use App\V1\Models\UnitModel;
use App\V1\Transformers\ReasonCancel\ReasonCancelTransformer;
use App\V1\Transformers\Unit\UnitTransformer;
use App\V1\Validators\Feedback\ReasonCancelCreateValidator;
use App\V1\Validators\Feedback\ReasonCancelUpdateValidator;
use App\V1\Validators\Unit\UnitCreateValidator;
use App\V1\Validators\Unit\UnitUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ReasonCancelController extends BaseController
{
    protected $model;
    protected $reasonCancelModel;
    /**
     * UnitController constructor.
     */
    public function __construct()
    {
        $this->model = new ReasonCancelModel();
        $this->reasonCancelModel = new ReasonCancel();
    }

    public function search(Request $request, ReasonCancelTransformer $ReasonCancelTransformer)
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
        return $this->response->paginator($units, $ReasonCancelTransformer);
    }

    /**
     * @param $id
     * @param ReasonCancelTransformer $ReasonCancelTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, ReasonCancelTransformer $ReasonCancelTransformer)
    {
        try {
            $rc = ReasonCancel::model()->where(['id' => $id, "company_id" => TM::getCurrentCompanyId()])->get();
            if (empty($rc)) {
                $rc = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return response()->json(["data" => $rc]);
    }
    public function create(
        Request $request,
        ReasonCancelCreateValidator $ReasonCancelCreateValidator,
        ReasonCancelTransformer $ReasonCancelTransformer
    )
    {
        $input = $request->all();
        $ReasonCancelCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $rc = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $rc->id, null, $rc->value);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($rc, $ReasonCancelTransformer);
    }
    public function update(
        $id,
        Request $request,
        ReasonCancelUpdateValidator $ReasonCancelUpdateValidator,
        ReasonCancelTransformer $ReasonCancelTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $ReasonCancelUpdateValidator->validate($input);
        // print_r($id);die;
        try {
            DB::beginTransaction();
            $rc = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $rc->id, null, $rc->value);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($rc, $ReasonCancelTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $rc = ReasonCancel::model()->where(['id' => $id, "company_id" => TM::getCurrentCompanyId()])->first();
            if (empty($rc)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $rc->delete();
            Log::delete($this->model->getTable(), "#ID:" . $rc->id . "-" . $rc->value);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => 'OK', 'message' => "Delete Successful"];
    }
    public function getClientReason(Request $request, ReasonCancelTransformer $ReasonCancelTransformer)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        $input['company_id'] = $company_id;
        $input['store_id'] = $store_id;
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
        return $this->response->paginator($units, $ReasonCancelTransformer);
    }
    public function getClientDetail($id, Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        try {
            $rc = ReasonCancel::model()->where(['id' => $id, "company_id" => $company_id])->get();
            if (empty($rc)) {
                $rc = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return response()->json(["data" => $rc]);
    }
}
