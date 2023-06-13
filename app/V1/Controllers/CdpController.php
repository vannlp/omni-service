<?php

namespace App\V1\Controllers;

use App\CdpLogs;
use App\Order;
use App\Product;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\Request;
use Exception;
use App\TM;
use App\User;
use App\V1\Library\CDP;
use App\V1\Models\CdpLogsModel;
use App\V1\Transformers\CDP\CdpLogsTransformer;

class CdpController
{
    use Helpers;

    protected $model;

    /**
     * SyncBaseController constructor.
     * @param Request $request
     * @throws \Exception
     */
    public function __construct(Request $request)
    {
        $this->model                 = new CdpLogsModel();
    }

    public function logSyncCDP(Request $request, CdpLogsTransformer $transformer)
    {
        $input = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $input['store_id']   = TM::getCurrentStoreId();
        $order               = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($order, $transformer);
    }

    public function respostSyncCDP($id)
    {
        $find = CdpLogs::model()->find($id);

        if (empty($find)) {
            return $this->responseError(Message::get("V003", Message::get("id")));
        }

        if ($find->sync_type = 'Đồng bộ đơn hàng') {
            $order  = Order::where('code', $find->code)->first();
            if (!empty($order)) {
                $reponse = CDP::pushOrderCdp($order, 'CdpController@cronRepostSyncCDP', $find);
            }
        }

        if ($find->sync_type = 'Đồng bộ khách hàng') {
            $customer = User::where('code', $find->code)->first();

            if (!empty($customer)) {
                $reponse = CDP::pushCustomerCdp($customer, 'CdpController@cronRepostSyncCDP', $find);
            }
        }

        if ($find->sync_type = 'Đồng bộ sản phẩm') {
            $product   = Product::where('code', $find->code)->first();
            if (!empty($product)) {
                $reponse = CDP::pushProductCdp($product, 'CdpController@cronRepostSyncCDP', $find);
            }
        }
        if (!empty($reponse) && $reponse == 200) {
            return ['status' => "success", "message" => Message::get("cdp_logs_orders.update-success", json_decode($find->param, true)['code'] ?? null)];
        } else {
            return ['status' => "error", "message" => Message::get("cdp_logs_orders.update-failed", json_decode($find->param, true)['code'] ?? null)];
        }
        try {
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            TM_Error::handle($e);
            return ['status' => "error", "message" => Message::get("cdp_logs_orders.update-failed", json_decode($find->param)->PurchaseOrderByCustomer ?? null)];
        }
    }

    public function pushOldDataOrderCdp(Request $request)
    {
        return CDP::pushOldDataOrderCdp($request);
    }

    public function pushOldDataCustomerCdp(Request $request)
    {
        return CDP::pushOldDataCustomerCdp($request);
    }

    public function pushOldDataProductCdp(Request $request)
    {
        return CDP::pushOldDataProductCdp($request);
    }
}
