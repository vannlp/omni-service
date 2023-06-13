<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:24
 */

namespace App\V1\Controllers;


use App\HashtagComment;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\HashtagCommentModel;
use App\V1\Transformers\HashtagComment\HashtagCommentTransformer;
use App\V1\Transformers\ReasonCancel\ReasonCancelTransformer;
use App\V1\Validators\HashtagComment\HashtagCommentCreateValidator;
use App\V1\Validators\HashtagComment\HashtagCommentUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class HashtagCommentController extends BaseController
{
    protected $model;
    protected $HashtagCommentModel;
    /**
     * UnitController constructor.
     */
    public function __construct()
    {
        $this->model = new HashtagCommentModel();
        $this->HashtagCommentModel = new HashtagComment();
    }

    public function search(Request $request, HashtagCommentTransformer $HashtagCommentTransformer)
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
        return $this->response->paginator($units, $HashtagCommentTransformer);
    }

    /**
     * @param $id
     * @param ReasonCancelTransformer $ReasonCancelTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, HashtagCommentTransformer $HashtagCommentTransformer)
    {
        try {
            $rc = HashtagComment::model()->where(['id' => $id, "company_id" => TM::getCurrentCompanyId()])->get();
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
        HashtagCommentCreateValidator $HashtagCommnetCreateValidator,
        HashtagCommentTransformer $HashtagCommentTransformer
    )
    {
        $input = $request->all();
        $HashtagCommnetCreateValidator->validate($input);

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
        return $this->response->item($rc, $HashtagCommentTransformer);
    }
    public function update(
        $id,
        Request $request,
        HashtagCommentUpdateValidator $HashtagCommentUpdateValidator,
        HashtagCommentTransformer $HashtagCommentTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $HashtagCommentUpdateValidator->validate($input);

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
        return $this->response->item($rc, $HashtagCommentTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $rc = HashtagComment::model()->where(['id' => $id, "company_id" => TM::getCurrentCompanyId()])->first();
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
    public function getClientReason(Request $request, HashtagCommentTransformer $HashtagCommentTransformer)
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
        return $this->response->paginator($units, $HashtagCommentTransformer);
    }
    public function getClientDetail($id, Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        try {
            $rc = HashtagComment::model()->where(['id' => $id, "company_id" => $company_id])->get();
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
