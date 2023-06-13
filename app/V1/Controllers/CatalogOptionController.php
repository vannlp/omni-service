<?php
/**
 * User: dai.ho
 * Date: 1/06/2020
 * Time: 1:42 PM
 */

namespace App\V1\Controllers;


use App\CatalogOption;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\CatalogOptionModel;
use App\V1\Transformers\CatalogOption\CatalogOptionTransformer;
use App\V1\Validators\CatalogOption\CatalogOptionCreateValidator;
use App\V1\Validators\CatalogOption\CatalogOptionUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogOptionController extends BaseController
{
    protected $model;

    /**
     * CatalogOptionController constructor.
     */
    public function __construct()
    {
        $this->model = new CatalogOptionModel();
    }

    public function search(Request $request, CatalogOptionTransformer $catalogOptionTransformer)
    {
        $input = $request->all();
        $input['company_id'] = TM::getCurrentCompanyId();
        if (!empty($input['code'])) {
            $input['code'] = ['like' => "%{$input['code']}%"];
        }
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        if (!empty($input['type'])) {
            $input['type'] = ['like' => "%{$input['type']}%"];
        }
        if (!empty($input['description'])) {
            $input['description'] = ['like' => "%{$input['description']}%"];
        }
//        $input['code'] = !empty($input['code']) ? ['like' => $input['code']] : null;
//        $input['name'] = !empty($input['name']) ? ['like' => $input['name']] : null;
//        $input['type'] = !empty($input['type']) ? ['like' => $input['type']] : null;
//        $input['description'] = !empty($input['description']) ? ['like' => $input['description']] : null;
        try {
            $catalogOptions = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($catalogOptions, $catalogOptionTransformer);
    }

    /**
     * @param $id
     * @param CatalogOptionTransformer $catalogOptionTransformer
     *
     * @return \Dingo\Api\Http\Response
     */
    public function detail($id, CatalogOptionTransformer $catalogOptionTransformer)
    {
        try {
            $catalogOption = CatalogOption::model()->where([
                'id'         => $id,
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($catalogOption)) {
                $catalogOption = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($catalogOption, $catalogOptionTransformer);
    }

    public function store(
        Request $request,
        CatalogOptionCreateValidator $catalogOptionCreateValidator,
        CatalogOptionTransformer $catalogOptionTransformer
    )
    {
        $input = $request->all();
        $catalogOptionCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $store = Store::model()->where([
                'id'         => $input['store_id'],
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($store)) {
                return $this->response->errorBadRequest(Message::get("V002", "Store ID #{$input['store_id']}"));
            }
            $catalogOption = $this->model->upsert($input);
            Log::view($this->model->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($catalogOption, $catalogOptionTransformer);
    }

    public function update(
        $id,
        Request $request,
        CatalogOptionUpdateValidator $catalogOptionUpdateValidator,
        CatalogOptionTransformer $catalogOptionTransformer
    )
    {
        $input = $request->all();
        $input['id'] = $id;
        $catalogOptionUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $store = Store::model()->where([
                'id'         => $input['store_id'],
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($store)) {
                return $this->response->errorBadRequest(Message::get("V002", "Store ID #{$input['store_id']}"));
            }
            $catalogOption = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $catalogOption->id, null, $catalogOption->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($catalogOption, $catalogOptionTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $catalogOption = CatalogOption::model()->where([
                'id'         => $id,
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($catalogOption)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete CatalogOption
            $catalogOption->delete();
            Log::delete($this->model->getTable(), "#ID:" . $catalogOption->id . "-" . $catalogOption->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }
}
