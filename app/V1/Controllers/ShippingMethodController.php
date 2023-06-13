<?php
/**
 * User: dai.ho
 * Date: 8/06/2020
 * Time: 3:13 PM
 */

namespace App\V1\Controllers;


use App\ShippingMethod;
use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\ShippingMethodModel;
use App\V1\Transformers\ShippingMethod\ShippingMethodClientTransformer;
use App\V1\Transformers\ShippingMethod\ShippingMethodTransformer;
use App\V1\Validators\ShippingMethod\ShippingMethodCreateValidator;
use App\V1\Validators\ShippingMethod\ShippingMethodUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShippingMethodController extends BaseController
{

    protected $model;

    /**
     * StoreController constructor.
     * @param ShippingMethodModel $model
     */
    public function __construct(ShippingMethodModel $model)
    {
        $this->model = $model;
    }

    /**
     * @param Request $request
     * @param ShippingMethodTransformer $methodTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, ShippingMethodTransformer $methodTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($input['name'])) {
            $input['name'] = ['like' => $input['name']];
        }
        if (!empty($input['code'])) {
            $input['code'] = ['like' => $input['code']];
        }

        $input['company_id'] = TM::getCurrentCompanyId();

        $method = $this->model->search($input, [], $limit);
        return $this->response->paginator($method, $methodTransformer);
    }

    /**
     * @param $id
     * @param ShippingMethodTransformer $methodTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function detail($id, ShippingMethodTransformer $methodTransformer)
    {
        $method = ShippingMethod::find($id);
        if (empty($method)) {
            return ['data' => []];
        }
        return $this->response->item($method, $methodTransformer);
    }

    /**
     * @param Request $request
     * @param ShippingMethodCreateValidator $methodCreateValidator
     * @return array|void
     */
    public function create(Request $request, ShippingMethodCreateValidator $methodCreateValidator)
    {
        $input = $request->all();
        $methodCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $method = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $method->name)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param ShippingMethodUpdateValidator $methodUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, ShippingMethodUpdateValidator $methodUpdateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $methodUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $method = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $method->name)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $method = ShippingMethod::find($id);
            if (empty($method)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $method->delete();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R003", $method->name)];
    }

    ########################################### NOT AUTHENTICATION ############################################

    public function getClientList(Request $request, ShippingMethodClientTransformer $clientTransformer)
    {
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $company_id = $store->company_id;
                }
            }
        }

        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['company_id'] = $company_id;
        $products = $this->model->search($input, [], $limit);
        return $this->response->paginator($products, $clientTransformer);
    }
}
