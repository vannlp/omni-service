<?php

namespace App\Sync\Controllers;

use App\Channel;
use App\V1\Transformers\Order\OrderFromToTransformer;
use App\Warehouse;
use App\WarehouseDetail;
use App\Order;
use App\Supports\Message;
use App\Supports\TM_Error;
//use App\Sync\Models\ChannelModel;
//use App\Sync\Models\WarehouseDetailModel;
//use App\Sync\Models\WarehouseModel;
//use App\Sync\Validators\ChannelCreateValidator;
//use App\Sync\Validators\ChannelUpdateValidator;
//use App\Sync\Validators\WarehouseCreateValidator;
//use App\Sync\Validators\WarehouseUpdateValidator;
//use App\Sync\Validators\WarehouseCreateDetailValidator;
//use App\Sync\Validators\WarehouseUpdateDetailValidator;
use App\DMSSyncCustomer;
use App\Routing;
use App\RoutingCustomer;
use App\Supports\Log;
use App\Sync\Models\RoutingCustomerModel;
use App\Sync\Models\RoutingModel;
use App\Sync\Models\VisitPlanModel;
use App\Sync\Transformers\RoutingCustomerSyncTransformers;
use App\Sync\Transformers\RoutingSyncTransformers;
use App\Sync\Transformers\VisitPlanSyncTransformers;
use App\Sync\Validators\RoutingCreateValidator;
use App\Sync\Validators\RoutingCustomerCreateValidator;
use App\Sync\Validators\RoutingCustomerUpdateValidator;
use App\Sync\Validators\RoutingUpdateValidator;
use App\Sync\Validators\VisitPlanCreateValidator;
use App\Sync\Validators\VisitPlanUpdateValidator;
use App\TM;
use App\VisitPlan;
use App\SaleOrderConfigMin;
use App\Sync\Models\SaleOrderConfigMinModel;
use App\Sync\Transformers\SaleOrderConfigMinTransformer;
use App\Sync\Validators\SaleOrderConfigMinCreateValidator;
use App\Sync\Validators\SaleOrderConfigMinUpdateValidator;
use App\Sync\Models\DMSSyncCustomerModel;
use App\Sync\Validators\UserCustomerCreateValidator;
use App\Sync\Validators\UserCustomerUpdateValidate;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use App\Sync\Models\ProductInfoModel;
use App\Sync\Models\ProductDMSModel;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;

// use App\V1\Models\ChannelModel;
use Illuminate\Support\Arr;
use App\ProductInfo;
use App\ProductDMS;
use App\Sync\Models\OrderModel;
use App\Sync\Transformers\OrderTransformer;
use App\Sync\Validators\ProductDMSUpdateValidator;
use App\Sync\Validators\ProductDMSValidator;
use App\Sync\Validators\ProductInfoValidator;
use App\Sync\Transformers\ProductInfoSyncTransformer;
use App\Sync\Transformers\ProductDMSSyncTransformer;


//Key: 221fa1cd1377ebaaf6c5720716f7cb14ccba98c203f1a56c8d447503c28f9101
class DMSSyncController
{
    use Helpers;

    protected $ProductInfo;

    protected $dMSSyncCustomerModel;
    protected $OrderModel;

    /**
     * SyncBaseController constructor.
     * @param Request $request
     * @throws \Exception
     */
    /**
     * @var SaleOrderConfigMinModel
     */
    protected $model;

    /**
     * SyncBaseController constructor.
     * @param Request $request
     * @throws \Exception
     */
    public function __construct(Request $request)
    {

        $headers     = $request->headers->all();
        $this->model = new SaleOrderConfigMinModel();
        if (empty($headers['authorization'][0])) {
            throw new \Exception(Message::get("V001", "Token"));
        }

        if (strlen($headers['authorization'][0]) != 64) {
            throw new \Exception(Message::get("token_invalid"));
        }

        if ($headers['authorization'][0] != env('DMS_SYNC_KEY', null)) {
            throw new \Exception(Message::get("token_invalid"));
        }
        $this->ProductInfo = new ProductInfoModel();
        $this->model       = new ProductDMSModel();

        $this->dMSSyncCustomerModel        = new DMSSyncCustomerModel();
        $this->dmsSyncRoutingModel         = new RoutingModel();
        $this->dmsSyncRoutingCustomerModel = new RoutingCustomerModel();
        $this->dmsSyncVisitPlan            = new VisitPlanModel();


        // $this->ChannelModel= new ChannelModel();
        // $this->WarehouseDetailModel= new WarehouseDetailModel();
        // $this->WarehouseModel= new WarehouseModel();

        $this->dMSSyncCustomerModel        = new DMSSyncCustomerModel();
        $this->dmsSyncRoutingModel         = new RoutingModel();
        $this->dmsSyncRoutingCustomerModel = new RoutingCustomerModel();
        $this->dmsSyncVisitPlan            = new VisitPlanModel();
        $this->OrderModel                  = new OrderModel();
    }

    /**
     * @param null $msg
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseError($msg = null, $code = 400)
    {
        $msg = $msg ? $msg : Message::get("V1001");
        return response()->json(['status' => 'error', 'error' => ['errors' => ["msg" => $msg]]], $code);
    }

    /**
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseData(array $data = [])
    {
        return response()->json(['status' => 'success', 'data' => $data]);
    }


    /*==============================================================================*/

    ////////////////////////////////Channel_Types nè///////////////////////
    public function funListChannel(Request $request)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->ChannelModel->search($input, [], $limit);
        return response()->json($result);
    }

    public function funDetailChannel($id)
    {
        $result = Channel::find($id);
        if (empty($result)) {
            return ['data' => Null];
        }
        return response()->json($result);
    }

    public function funcPostChannel(Request $request, ChannelCreateValidator $channelValidator)
    {
        $input = $request->all();
        $channelValidator->validate($input);
        try {
            // DB::beginTransaction();
            $ChannelModel = $this->ChannelModel->upsert($input);

            // DB::commit();
        } catch (\Exception $ex) {
            // DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $ChannelModel->name)];
    }

    public function funcPutChannel(Request $request, $id, ChannelUpdateValidator $channelUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $channelUpdateValidator->validate($input);
        try {
            $ChannelModel = $this->ChannelModel->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $ChannelModel->name)];
    }

    //PRODUCT DMS IMPORTS

    public function listProductDMS(Request $request, ProductDMSSyncTransformer $ProductDMSSyncTransformer)
    {

        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->model->search($input, [], $limit);

        return $this->response->paginator($result, $ProductDMSSyncTransformer);
    }

    public function createProductDMS(Request $request)
    {

        $input = $request->all();

        (new ProductDMSValidator())->validate($input);
        try {

            DB::beginTransaction();

            $model = $this->model->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }
    // public function searchCustomer($code = null,Request $request)
    // {

    // }
    public function funDeleteChannel($id)
    {
        try {
            $result = Channel::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->name)]);
    }


    /////////////////////////////////////WareHouse nè//////////////////////////////////////////////////
    public function funListWarehouse(Request $request)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->WarehouseModel->search($input, [], $limit);
        return response()->json($result);
    }

    public function funDetailWarehouse($id)
    {
        $result = Warehouse::find($id);
        if (empty($result)) {
            return ['data' => Null];
        }
        return response()->json($result);
    }

    public function funcPostWarehouse(Request $request, WarehouseCreateValidator $warehouseValidator)
    {
        $input = $request->all();
        $warehouseValidator->validate($input);
        try {
            // DB::beginTransaction();
            $WarehouseModel = $this->WarehouseModel->upsert($input);

            // DB::commit();
        } catch (\Exception $ex) {
            // DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $WarehouseModel->name)];
    }

    public function funcPutWarehouse(Request $request, $id, WarehouseUpdateValidator $warehouseUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $warehouseUpdateValidator->validate($input);
        try {
            $WarehouseModel = $this->WarehouseModel->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $WarehouseModel->name)];
    }

    public function funDeleteWarehouse(Request $request, $id)
    {
        try {
            $result = Warehouse::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->name)]);
    }

    /////////////////////////////WareHouseDetail///////////////////////////////
    public function funListWarehouseDetail(Request $request)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->WarehouseDetailModel->search($input, [], $limit);
        return response()->json($result);
    }

    public function funDetailWarehouseDetail($id)
    {
        $result = WarehouseDetail::find($id);
        if (empty($result)) {
            return ['data' => Null];
        }
        return response()->json($result);
    }

    public function funcPostWarehouseDetail(Request $request, WarehouseCreateDetailValidator $warehouseCreateDetailValidator)
    {
        $input = $request->all();
        $warehouseCreateDetailValidator->validate($input);
        try {
            // DB::beginTransaction();
            $WarehouseModel = $this->WarehouseDetailModel->upsert($input);

            // DB::commit();
        } catch (\Exception $ex) {
            // DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $WarehouseModel->product_name)];
    }

    public function funcPutWarehouseDetail(Request $request, $id, WarehouseUpdateDetailValidator $warehouseUpdateDetailValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $warehouseUpdateDetailValidator->validate($input);
        try {
            $WarehouseModel = $this->WarehouseDetailModel->upsert($input);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $WarehouseModel->product_name)];
    }

    public function funDeleteWarehouseDetail($id)
    {
        try {
            $result = WarehouseDetail::find($id);
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->product_name)]);
    }


    public function searchCustomer($code = null, Request $request)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        if (!empty($code)) {
            $input['code'] = $code;
            $userCustomer  = DMSSyncCustomer::where('code', $code)->first();
        } else $userCustomer = $this->dMSSyncCustomerModel->search($input, [], $limit);
        if ($userCustomer) return response()->json($userCustomer);
        return ['data' => null];
    }

    public function createCustomer(Request $request, UserCustomerCreateValidator $UserCustomerCreateValidator)
    {
        $input = $request->all();
        if (!empty($input['PHONE']) || !empty($input['MOBIPHONE'])) {
            $UserCustomerCreateValidator->validate($input);
        } else return ['status' => Message::get("V003", "PHONE hoặc MOBIPHONE")];
        try {
            DB::beginTransaction();

            $customerSyncModel = $this->dMSSyncCustomerModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $model->product_name)];

    }

    public function detailProductDMS($id)
    {
        $result = ProductDMS::find($id);
        if (empty($result)) {
            return ['data' => Null];
        }
        return $this->response->item($result, new ProductDMSSyncTransformer());
    }

    public function updateProductDMS($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new ProductDMSUpdateValidator)->validate($input);
        try {
            DB::beginTransaction();
            $result = ProductDMS::findOrFail($id);

            $result = $this->model->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->product_name)]);
    }

    public function deleteProductDMS($id)
    {
        try {
            DB::beginTransaction();
            $ProductDMS = ProductDMS::find($id);
            if (empty($ProductDMS)) {
                return ['data' => Null];
            }
            $ProductDMS->delete();
            DB::commit();
        } catch (\Exception $ex) {
            return ['status' => Message::get("R001", $customerSyncModel->name)];

        }

    }

    public function updateCustomer($code, Request $request, UserCustomerUpdateValidate $userCustomerUpdateValidate)
    {
        $input                  = $request->all();
        $input['CUSTOMER_CODE'] = $code;
        if (!empty($input['EMAIL'])) {
            $checkEmail = DMSSyncCustomer::where('email', $input['EMAIL'])->first();
            if (!empty($checkEmail) && $checkEmail->code != $code) {
                return $this->responseError(Message::get("unique", Message::get("EMAIL")), 500);
            }
        }
        if (!empty($input['PHONE'])) {
            $checkPhone = DMSSyncCustomer::where('phone', $input['PHONE'])->first();
            if (!empty($checkPhone) && $checkPhone->code != $code) {
                return $this->responseError(Message::get("unique", Message::get("PHONE")), 500);
            }
        }
        if (!empty($input['MOBIPHONE'])) {
            $checkMobihone = DMSSyncCustomer::where('mobiphone', $input['MOBIPHONE'])->first();
            if (!empty($checkMobihone) && $checkMobihone->code != $code) {
                return $this->responseError(Message::get("unique", Message::get("MOBIPHONE")), 500);
            }
        }
        $userCustomerUpdateValidate->validate($input);
        try {
            DB::beginTransaction();

            $customerSyncModel = $this->dMSSyncCustomerModel->upsert($input, true);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $customerSyncModel->name)];
    }

    public function deleteCustomer($code, Request $request)
    {
        try {
            DB::beginTransaction();

            $customerSyncModel = $this->dMSSyncCustomerModel->delete($code);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        if ($customerSyncModel) return ['status' => Message::get("R003", $customerSyncModel->name)];
        return $this->responseError(Message::get('V061'));
    }


    /*======================================ROUTING========================================*/

    public function createRouting(Request $request, RoutingCreateValidator $routingCreateValidator)
    {
        $input = $request->all();
        $routingCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $dmsSyncRouting = $this->dmsSyncRoutingModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return response()->json(['status' => Message::get('R003', $ProductDMS->product_name)]);
    }

    //PRODUCT INFO DMS IMPORTS

    public function listProductInfo(Request $request, ProductInfoSyncTransformer $ProductInfoSyncTransformer)
    {

        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $result = $this->ProductInfo->search($input, [], $limit);

        return $this->response->paginator($result, $ProductInfoSyncTransformer);
    }

    public function createProductInfo(Request $request)
    {

        $input = $request->all();

        (new ProductInfoValidator())->validate($input);
        try {

            DB::beginTransaction();

            $ProductInfo = $this->ProductInfo->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            return ['status' => Message::get("R001", $dmsSyncRouting->routing_name)];
        }
    }

    public function updateRouting($id, Request $request, RoutingUpdateValidator $routingUpdateValidator)
    {
        $input = $request->all();
        $routingUpdateValidator->validate($input);
        $input['id'] = $id;
        try {
            DB::beginTransaction();
            $dmsSyncRouting = $this->dmsSyncRoutingModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $dmsSyncRouting->routing_name)];
    }

    public function listRouting(Request $request, RoutingSyncTransformers $routingTransformer)
    {
        $input             = $request->all();
        $input['store_id'] = !empty($input['store_id']) ? $input['store_id'] : TM::getIDP()->store_id;
        $limit             = array_get($input, 'limit', 20);
        $routings          = $this->dmsSyncRoutingModel->search($input, [], $limit);
        Log::view($this->dmsSyncRoutingModel->getTable());
        return $this->response->paginator($routings, $routingTransformer);
    }

    public function deleteRouting($id)
    {
        try {
            $result = Routing::where('routing_id', $id)->first();
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->routing_code)]);
    }

    /*======================================ROUTING-CUSTOMER=======================================*/

    public function listCustomerRouting(Request $request, RoutingCustomerSyncTransformers $routingCustomerSyncTransformers)
    {
        $input           = $request->all();
        $limit           = array_get($input, 'limit', 20);
        $routingCustomer = $this->dmsSyncRoutingCustomerModel->search($input, [], $limit);
        Log::view($this->dmsSyncRoutingCustomerModel->getTable());
        return $this->response->paginator($routingCustomer, $routingCustomerSyncTransformers);
    }

    public function createCustomerRouting(Request $request, RoutingCustomerCreateValidator $routingCustomerCreateValidator)
    {
        $input = $request->all();
        $routingCustomerCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $dmsSyncCustomerRouting = $this->dmsSyncRoutingCustomerModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $dmsSyncCustomerRouting->routing_customer_id)];
    }

    public function updateCustomerRouting($id, Request $request, RoutingCustomerUpdateValidator $routingCustomerUpdateValidator)
    {
        $input = $request->all();
        $routingCustomerUpdateValidator->validate($input);
        $input['id'] = $id;

        try {
            DB::beginTransaction();
            $dmsSyncCustomerRouting = $this->dmsSyncRoutingCustomerModel->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $dmsSyncCustomerRouting->routing_customer_id)];

    }

    public function detailProductInfo($id)
    {
        $result = ProductInfo::find($id);
        if (empty($result)) {
            return ['data' => Null];
        }
        return $this->response->item($result, new ProductInfoSyncTransformer());
    }

    public function updateProductInfo($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new ProductInfoValidator)->validate($input);
        try {
            DB::beginTransaction();
            $result = ProductInfo::findOrFail($id);

            $result = $this->ProductInfo->upsert($input);


            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R002', $result->product_info_name)]);
    }

    public function deleteProductInfo($id)
    {
        try {
            DB::beginTransaction();
            $productinfo = ProductInfo::find($id);
            if (empty($productinfo)) {
                return ['data' => Null];
            }
            $productinfo->delete();
            DB::commit();
        } catch (\Exception $ex) {
            return response()->json(['status' => Message::get('R003', $productinfo->code)]);

        }
    }

    public function deleteRoutingCustomer($id)
    {
        try {
            $result = RoutingCustomer::where('routing_customer_id', $id)->first();
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->routing_customer_id)]);
    }

    /*======================================VISIT-PLANS=======================================*/

    public function listVisitPlan(Request $request, VisitPlanSyncTransformers $visitPlanSyncTransformers)
    {
        $input     = $request->all();
        $limit     = array_get($input, 'limit', 20);
        $visitPlan = $this->dmsSyncVisitPlan->search($input, [], $limit);
        Log::view($this->dmsSyncRoutingModel->getTable());
        return $this->response->paginator($visitPlan, $visitPlanSyncTransformers);
    }

    public function createVisitPlan(Request $request, VisitPlanCreateValidator $visitPlanCreateValidator)
    {
        $input = $request->all();
        $visitPlanCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $dmsSyncVisitPlan = $this->dmsSyncVisitPlan->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R001", $dmsSyncVisitPlan->visit_plan_id)];
    }

    public function updateVisitPlan($id, Request $request, VisitPlanUpdateValidator $visitPlanUpdateValidator)
    {
        $input = $request->all();
        $visitPlanUpdateValidator->validate($input);
        $input['id'] = $id;
        try {
            DB::beginTransaction();
            $dmsSyncVisitPlan = $this->dmsSyncVisitPlan->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $dmsSyncVisitPlan->visit_plan_id)];
    }

    public function deleteVisitPlan($id)
    {
        try {
            $result = VisitPlan::where('visit_plan_id', $id)->first();
            if (empty($result)) {
                return $this->responseError(Message::get("V003", "ID: #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->visit_plan_id)]);
    }

    /*======================================SaleOrderConfigMin=======================================*/


    /**
     * @param Request $request
     * @param SaleOrderConfigMinTransformer $saleOrderConfigMinTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, SaleOrderConfigMinTransformer $saleOrderConfigMinTransformer)
    {
        $input     = $request->all();
        $limit     = array_get($input, 'limit', 20);
        $warehouse = $this->model->search($input, [], $limit);
        return $this->response->paginator($warehouse, $saleOrderConfigMinTransformer);
    }

    public function detail($id, SaleOrderConfigMinTransformer $saleOrderConfigMinTransformer)
    {
        $saleOrderConfigMin = SaleOrderConfigMin::find($id);
        if (empty($saleOrderConfigMin)) {
            return ['data' => []];
        }
        return $this->response->item($saleOrderConfigMin, $saleOrderConfigMinTransformer);
    }

    /**
     * @param Request $request
     * @param SaleOrderConfigMinCreateValidator $SaleOrderConfigMinCreateValidator
     * @return array|void
     */
    public function create(Request $request, SaleOrderConfigMinCreateValidator $SaleOrderConfigMinCreateValidator)
    {
        $input = $request->all();
        $SaleOrderConfigMinCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $SaleOrderConfigMin = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("warehouses.create-success", $SaleOrderConfigMin->shop_id)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param SaleOrderConfigMinUpdateValidator $SaleOrderConfigMinUpdateValidator
     * @return array|void
     */
    public function update($id, Request $request, SaleOrderConfigMinUpdateValidator $SaleOrderConfigMinUpdateValidator)
    {
        $input       = $request->all();
        $input['id'] = $id;
        $SaleOrderConfigMinUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            $SaleOrderConfigMin = $this->model->upsert($input);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("warehouses.update-success", $SaleOrderConfigMin->shop_id)];
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $SaleOrderConfigMin = SaleOrderConfigMin::find($id);
            if (empty($SaleOrderConfigMin)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $SaleOrderConfigMin->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }


        return ['status' => Message::get("warehouses.delete-success", $SaleOrderConfigMin->shop_id)];
    }

    public function putOrder($code, Request $request)
    {
        $input = $request->all();
        if (empty($input['status'])) {
            return $this->response->errorBadRequest("Vui lòng nhập status!!");
        }

        if ($input['status'] == ORDER_STATUS_CANCELED && empty($input['cancellationReason'])) {
            return $this->response->errorBadRequest("Cập nhật trạng thái huỷ đơn cần có lý do huỷ!!");
        }
        try {
            DB::beginTransaction();
            $order = Order::model()->where('code', $code)->first();
            if (!empty($order)) {
                $order->status        = $input['status'];
                $order->cancel_reason = $input['cancellationReason'] ?? null;
                $order->save();
            }
            if (empty($order)) {
                return $this->responseError("Đơn hàng không tồn tại!!", 400);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => "Cập nhật đơn hàng $code thành công"];
    }

    public function getOrder($code)
    {
        try {
            DB::beginTransaction();
            $order = Order::model()->where('code', $code)->first();
            if (!empty($order)) {
                $data = [
                    "id"         => $order->id,
                    "code"       => $order->code,
                    "status"     => ORDER_STATUS_NAME[$order->status],
                    "order_date" => date("d-m-Y H:i:s", strtotime($order->created_at)),
                ];
            }
            if (empty($order)) {
                return $this->responseError("Đơn hàng không tồn tại!!", 400);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return response()->json($data);
    }

    public function OrderDateFrom(Request $request, OrderTransformer $orderTransformer)
    {
        $input = $request->all();
        if (empty($input['date'])) {
            return $this->response->errorBadRequest("Vui lòng nhập ngày!!");
        }
        $order = $this->OrderModel->search($input, [
            'partner',
            'customer',
            'details',
            'promotionTotals',
            'statusHistories.createdBy',
        ]);
        Log::view($this->model->getTable());
        return $this->response->collection($order, $orderTransformer);
    }

    public function OrderDateFromTo(Request $request, OrderFromToTransformer $orderFromToTransformer)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->response->errorBadRequest("Vui lòng nhập ngày [from]!");
        }
        if (empty($input['to'])) {
            return $this->response->errorBadRequest("Vui lòng nhập ngày [to]!");
        }
        $order = $this->OrderModel->search($input, [
            'partner',
            'customer',
            'details',
            'distributor',
            'promotionTotals',
            'getCity',
            'getDistrict',
            'getWard',
            'statusHistories.createdBy',
        ]);
        return $this->response->collection($order, $orderFromToTransformer);
    }

    public function OrderByOrderNumber(Request $request, OrderFromToTransformer $orderFromToTransformer)
    {
        $input = $request->all();
        if (empty($input['orderNumber'])) {
            return $this->response->errorBadRequest("Vui lòng nhập mã đơn hàng.");
        }
        $order = $this->OrderModel->search($input, [
            'partner',
            'customer',
            'details',
            'distributor',
            'promotionTotals',
            'getCity',
            'getDistrict',
            'getWard',
            'statusHistories.createdBy',
        ]);
        return $this->response->collection($order, $orderFromToTransformer);
    }
}
