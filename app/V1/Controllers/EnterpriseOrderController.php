<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 2:19 PM
 */

namespace App\V1\Controllers;


use App\EnterpriseOrder;
use App\EnterpriseOrderDetail;
use App\NotificationHistory;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserSession;
use App\V1\Models\EnterpriseOrderModel;
use App\V1\Transformers\EnterpriseOrder\EnterpriseOrderTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnterpriseOrderController extends BaseController
{
    /**
     * @var EnterpriseOrderModel
     */
    protected $model;

    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        /** @var EnterpriseOrder model */
        $this->model = new EnterpriseOrderModel();
    }

    public function search(Request $request, EnterpriseOrderTransformer $enterpriseOrderTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $enterprise_orders = $this->model->search($input, [], $limit);

        return $this->response->paginator($enterprise_orders, $enterpriseOrderTransformer);
    }

    public function detail($id, EnterpriseOrderTransformer $enterpriseOrderTransformer)
    {
        $enterprise_order = EnterpriseOrder::find($id);
        if (empty($enterprise_order)) {
            return ['data' => []];
        }

        return $this->response->item($enterprise_order, $enterpriseOrderTransformer);
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $enterprise_order = EnterpriseOrder::find($id);
            if (empty($enterprise_order)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Detail
            EnterpriseOrderDetail::model()->where('enterprise_order_id', $id)->delete();

            // 2. Delete Enterprise Order
            $enterprise_order->delete();
            Log::delete($this->model->getTable(), "#ID:" . $enterprise_order->id . "-" . $enterprise_order->code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R013", Message::get("enterprise"))];
    }

    public function updateStatus($id, Request $request)
    {
        $input = $request->all();

        if (empty($input['status'])) {
            return $this->responseError(Message::get("V001", "Status"));
        }

        if (!in_array($input['status'], [
            'NEW',
            'RECEIVED',
            'COMMING',
            'ARRIVED',
            'INPROGRESS',
            'COMPLETED',
        ])) {
            return $this->responseError(Message::get("V002", "Status"));
        }

        try {
            DB::beginTransaction();
            $enterprise_order = EnterpriseOrder::find($id);
            if (empty($enterprise_order)) {
                return $this->responseError(Message::get("V002", Message::get("enterprise") . " #$id"));
            }

            // Check to reject if status is NEW, COMPLETED
            if ($enterprise_order->status == ORDER_STATUS_NEW || $enterprise_order->status == ORDER_STATUS_COMPLETED) {
                return $this->responseError(Message::get("V046", $enterprise_order->status));
            }

            if ($input['status'] != ($status = $this->getNextStatus($enterprise_order))) {
                return $this->responseError(Message::get("V006", Message::get("status"), $status));
            }

            $enterprise_order->status = $input['status'];
            $enterprise_order->save();

            $this->sendNotifyUpdateStatus($enterprise_order);

            DB::commit();

            return response()->json(['status' => 'success', 'message' => "Order Status updated successfully"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function sendNotifyUpdateStatus($enterprise_order)
    {
        $statusTitle = [
            'RECEIVED'   => 'đã được nhận',
            'COMMING'    => 'nhân sự đang đến',
            'ARRIVED'    => 'đã đến',
            'INPROGRESS' => 'đang được thực hiện',
            'COMPLETED'  => 'đã hoàn thành',
        ];

        try {
            //Get Device
            $userSession = UserSession::model()->where('user_id',
                $userId = $enterprise_order->enterprise_id)->where('deleted',
                '0')->first();
            $device = $userSession->device_token ?? null;
            if (empty($device)) {
                return false;
            }

            $title = "Đơn hàng #" . ($enterprise_order->code) . " " . ($statusTitle[$enterprise_order->status]);
            $notificationHistory = new NotificationHistory();
            $notificationHistory->title = "Đơn hàng " . ($statusTitle[$enterprise_order->status]);
            $notificationHistory->body = $title;
            $notificationHistory->message = $title;
            $notificationHistory->notify_type = "ORDER";
            $notificationHistory->type = "ORDER";
            $notificationHistory->extra_data = '';
            $notificationHistory->receiver = $device;
            $notificationHistory->action = 1;
            $notificationHistory->item_id = $enterprise_order->id;
            $notificationHistory->created_at = date('Y-m-d H:i:s', time());
            $notificationHistory->created_by = TM::getCurrentUserId();
            $notificationHistory->updated_at = date('Y-m-d H:i:s', time());
            $notificationHistory->updated_by = TM::getCurrentUserId();
            $notificationHistory->save();

            DB::table('notification_histories')->where('id', $notificationHistory->id)->update([
                'created_by' => TM::getCurrentUserId(),
                'updated_by' => TM::getCurrentUserId(),
            ]);
            $notificationHistory = $notificationHistory->toArray();
            $action = ["click_action" => "FLUTTER_NOTIFICATION_CLICK"];
            $notificationHistory = array_merge($notificationHistory, $action);

            $notification = ['title' => "Đơn hàng " . ($statusTitle[$enterprise_order->status]), 'body' => $title];
            $notificationHistory["click_action"] = "FLUTTER_NOTIFICATION_CLICK";
            $notificationHistory["order_status"] = $enterprise_order->status;
            $fields = [
                'data'         => $notificationHistory,
                'notification' => $notification,
                'to'           => $device,
            ];

            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, env('FIREBASE_URL', ''));
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

            $result = curl_exec($ch);
            if ($result === false) {
                throw new \Exception('FCM Send Error: ' . curl_error($ch));
            }
            curl_close($ch);
        } catch (\Exception $ex) {
            throw $ex;
        }
        return $result;
    }

    /**
     * @param EnterpriseOrder $enterprise_order
     * @return mixed|null
     */
    private function getNextStatus(EnterpriseOrder $enterprise_order)
    {
        $nextStatus = [
            'NEW'        => 'RECEIVED',
            'RECEIVED'   => 'COMMING',
            'COMMING'    => 'ARRIVED',
            'ARRIVED'    => 'INPROGRESS',
            'INPROGRESS' => 'COMPLETED',
        ];

        return $nextStatus[$enterprise_order->status] ?? null;
    }
}
