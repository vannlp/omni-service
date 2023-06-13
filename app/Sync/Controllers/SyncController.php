<?php

namespace App\Sync\Controllers;


use App\Batch;
use App\Company;
use App\Exceptions\DaiException;
use App\Jobs\SendMailCustomerHubNewJob;
use App\Order;
use App\OrderDetail;
use App\OrderDmsHistory;
use App\OrderHistory;
use App\OrderStatus;
use App\OrderStatusHistory;
use App\Product;
use App\Profile;
use App\Role;
use App\Rotation;
use App\Setting;
use App\ShippingHistoryStatus;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Supports\Viettel;
use App\Sync\Models\OrderModel;
use App\Sync\Models\ProductModel;
use App\Sync\Models\PromotionModel;
use App\Sync\Models\UserModel;
use App\Sync\Transformers\OrderTransformer;
use App\Sync\Validators\CustomerCreateValidator;
use App\Sync\Validators\CustomerUpdateValidator;
use App\Sync\Validators\DistributorCreateValidator;
use App\Sync\Validators\DistributorUpdateValidator;
use App\Sync\Validators\OrderCreateValidator;
use App\Sync\Validators\OrderUpdateValidator;
use App\Sync\Validators\ProductCreateValidator;
use App\Sync\Validators\ProductUpdateValidator;
use App\Sync\Validators\PromotionCreateValidator;
use App\Sync\Validators\PromotionUpdateValidator;
use App\Sync\Validators\ShipOrderValidator;
use App\TM;
use App\Unit;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\V1\Library\GHN;
use App\V1\Library\GRAB;
use App\V1\Library\NJV;
use App\V1\Library\VNP;
use App\V1\Library\VTP;
use App\V1\Models\InventoryModel;
use App\V1\Validators\ShippingOrder\ShippingOrderCreateValidator;
use App\Warehouse;
use App\WarehouseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SyncController extends SyncBaseController
{

    /**
     * SyncController constructor.
     */
    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    /**
     * @return mixed
     */
    public function getDMSData()
    {
        $param[] = [
            "orderNumber"  => "DH20211231009",
            "orderDate"    => "31-12-2021 11:21:19",
            "shortCode"    => "0774220722_34_58",
            "customerName" => "NPP Quang Bách",
            "phone"        => "0774220722",
            "address"      => "22/127 văn cao, Phường Liễu Giai, Quận Ba Đình, Thành phố Hà Nội",
            "shopCode"     => "phni0111",
            "shopName"     => "phni0111",
            "status"       => "SHIPPED",
            "sumAmount"    => 1210000,
            "sumDiscount"  => 30000,
            "total"        => 1180000,
            "totalDetail"  => 2,
            "dataStatus"   => "C",
            'promotion'    => [
                [
                    "promotionCode"  => "GIAMGIAVARNA",
                    "promotionName"  => "KM| Värna",
                    "promotionPrice" => 30000
                ]
            ],
            'details'      => [
                [

                    "productCode"   => "FBVD138B00",
                    "productName"   => "Sữa Bột Värna Diabetes lon 850g",
                    "quantity"      => 2,
                    "price"         => 605000,
                    "discount"      => 15000,
                    "totalDiscount" => 30000,
                    "amount"        => 1180000,
                    "isFreeItem"    => 0,
                    "programName"   => null
                ], [
                    "productCode"   => "FRVD162300",
                    "productName"   => "Thùng 24 chai 237ml Sữa Bột Pha Sẵn Värna Diabetes",
                    "quantity"      => 2,
                    "price"         => 0,
                    "discount"      => 0,
                    "totalDiscount" => 0,
                    "amount"        => 0,
                    "isFreeItem"    => 1,
                    "programName"   => "QUÀ TẶNG | Sữa Pha Sẵn Värna"
                ],
            ]
        ];
        return $param;
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnSuccess()
    {
        return $this->syncSuccess("U");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function returnFail(Request $request)
    {
        $input = $request->all();
        if (empty($input)) {
            return $this->syncError("JSON Empty!");
        }
        $err          = [];
        $count        = 0;
        $countSuccess = 0;

        foreach ($input as $value) {
            try {
                $validate             = new OrderUpdateValidator();
                $errValue             = [];
                $errValue['errorMgs'] = '';
                $count++;
                if (!empty($validate->checkValidate($value))) {
                    $mesErr = $validate->checkValidate($value);
                    foreach ($mesErr as $row) {
                        $errValue['errorMgs'] .= $row[0];
                    }
                    $errValue['data'] = $value;
                    array_push($err, $errValue);
                    continue;
                }
            }
            catch (\Exception $ex) {
                $errValue['errorMgs'] .= 'Đã xảy ra lỗi khi chèn dữ liệu vào hệ thống! \\' . $ex->getMessage();
                $errValue['data']     = $value;
                array_push($err, $errValue);
            }
        }

        if ($count > $countSuccess) $err['notify'] = $countSuccess . ' dòng thành công / Tổng:' . $count;

        $notify = !empty($err['notify']) ? $err['notify'] : null;

        if ($notify) unset($err['notify']);
        if (!empty($err)) return $this->syncError("U", $err, $notify);

        return $this->syncError("U");
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request)
    {
        $input     = $request->all();
        $statusDms = array_keys(STATUS_NAME_VIETTEL);
        if (empty($input)) {
            return $this->syncError("JSON Empty!");
        }
        $err          = [];
        $count        = 0;
        $countSuccess = 0;
        Log::logSyncOrderStatusDMS("TOTAL_STATUS", "Không có lỗi", $input, 'NTDMS', 0, 200);
        foreach ($input as $value) {
            if (in_array($value['orderNumber'],['DH20220604001','DH20220602029','DH20220601070','DH20220602025','DH20220602022','DH20220603020','DH20220603021','DH20220603021','DH20220603011','DH20220529039','DH20220529019','DH20220529020','DH20220529037','DH20220530025','DH20220529017','DH20220530048','DH20220531056','DH20220531037','DH20220531068','DH20220604020','DH20220605002','DH20220607004','DH20220606067','DH20220609028','DH20220609039','DH20220609018'])){
                continue;
            }
            try {
                $validate             = new OrderUpdateValidator();
                $errValue             = [];
                $errValue['errorMgs'] = '';
                $count++;
                if (!empty($validate->checkValidate($value))) {
                    $mesErr = $validate->checkValidate($value);
                    foreach ($mesErr as $row) {
                        $errValue['errorMgs'] .= $row[0];
                    }
                    $errValue['data'] = $value;
                    array_push($err, $errValue);
                    Log::logSyncOrderStatusDMS($value['orderNumber'], 'Lỗi validate', $value, 'NTDMS', 0, 400);
                    continue;
                }
                $order = Order::model()->where('code', $value['orderNumber'])->first();
                if (empty($order)) {
                    $errValue['errorMgs'] .= "Không tìm thấy đơn hàng";
                    $errValue['data']     = $value;
                    array_push($err, $errValue);
                    Log::logSyncOrderStatusDMS($value['orderNumber'], 'Không tìm thấy đơn hàng', $value, 'NTDMS', 0, 400);
                    continue;
                }
                if (!in_array($value['status'], $statusDms)) {
                    $errValue['errorMgs'] .= "Trạng thái đơn hàng không đúng";
                    $errValue['data']     = $value;
                    array_push($err, $errValue);
                    Log::logSyncOrderStatusDMS($value['orderNumber'], 'Không tìm thấy đơn hàng', $value, 'NTDMS', 0, 400);
                    continue;
                }
                if ($value['status'] != 3) {
                    OrderHistory::insert([
                        'order_id'   => $order->id,
                        'status'     => SYNC_STATUS_NAME_VIETTEL[$value['status']],
                        'created_at' => date("Y-m-d H:i:s", time()),
                    ]);
                    ShippingHistoryStatus::insert([
                        'shipping_id'      => $order->code,
                        'status_code'      => SYNC_STATUS_NAME_VIETTEL[$value['status']],
                        'text_status_code' => ORDER_STATUS_NEW_NAME[SYNC_STATUS_NAME_VIETTEL[$value['status']]],
                        'created_at'       => date("Y-m-d H:i:s", time()),
                    ]);
                    $checkStatusDms = OrderDmsHistory::model()->where(['order_id' => $order->id, 'status' => SYNC_STATUS_NAME_VIETTEL[$value['status']]])->first();
                    if (!empty($checkStatusDms)) {
                        $errValue['errorMgs'] .= "Trạng thái đơn hàng DMS đã tồn tại trong hệ thống!";
                        $errValue['data']     = $value;
                        array_push($err, $errValue);
                        Log::logSyncOrderStatusDMS($value['orderNumber'], 'Trạng thái đơn hàng DMS đã tồn tại trong hệ thống!', $value, 'NTDMS', 0, 400);
                        continue;
                    }
                    OrderDmsHistory::insert([
                        'order_id'   => $order->id,
                        'status'     => SYNC_STATUS_NAME_VIETTEL[$value['status']],
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'created_by' => 999999,
                    ]);
                    $order->status        = SYNC_STATUS_NAME_VIETTEL[$value['status']];
                    $order->status_text   = ORDER_STATUS_NAME[SYNC_STATUS_NAME_VIETTEL[$value['status']]];
                    $order->cancel_reason = $value['deliveryNote'] ?? null;
                    $order->save();

//                    if ($order->status == ORDER_STATUS_APPROVED) {
//                        $this->postOrder($order->code, []);
//                    }
                }
                if ($value['status'] == 3) {
                    $this->postOrder($order->code, []);
                }
                try {
                    if (in_array($value['status'], [4, 5, 6])) {
                        $shippingOrder              = ShippingOrder::model()->where('code', $order->code)->first();
                        $shippingOrder->code        = SYNC_STATUS_NAME_VIETTEL[$value['status']];
                        $shippingOrder->status_text = ORDER_STATUS_NAME[SYNC_STATUS_NAME_VIETTEL[$value['status']]];
                        $shippingOrder->save();
                    }
                }
                catch (\Exception $exception) {
                }
                Log::logSyncOrderStatusDMS($value['orderNumber'], "Không có lỗi", $value, 'NTDMS', 0, 200);
                $countSuccess++;
            }
            catch (\Exception $ex) {
                $errValue['errorMgs'] .= 'Đã xảy ra lỗi khi chèn dữ liệu vào hệ thống! \\' . $ex->getMessage();
                $errValue['data']     = $value;
                array_push($err, $errValue);
                Log::logSyncOrderStatusDMS($value['orderNumber'], $ex->getMessage(), $value, 'NTDMS', 0, 500);
            }
        }

        if ($count > $countSuccess) $err['notify'] = $countSuccess . ' dòng thành công / Tổng:' . $count;

        $notify = !empty($err['notify']) ? $err['notify'] : null;

        if ($notify) unset($err['notify']);

        if (!empty($err)) return $this->syncError("U", $err, $notify);

        return $this->syncSuccess("U", $notify);
    }

    function postOrder($orderId, $request = [])
    {
        $orderId     = explode(',', $orderId);
        $orderStatus = Order::whereIn('code', $orderId)->pluck('status', 'code')->toArray();
        foreach ($orderStatus as $key => $status) {
            if (!in_array($status, [ORDER_STATUS_NEW, ORDER_STATUS_APPROVED])) {
                return $this->responseError(Message::get('V002', Message::get('status') . " [$key]"));
            }
        }
        $date     = date('Y-m-d H:i:s', time());
        $rotation = Rotation::model()
            ->with('condition')
            ->where('is_active', 1)->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
        try {
            DB::beginTransaction();
            $orders = Order::whereIn('code', $orderId)->get();
            foreach ($orders as $order) {
                if (!empty($rotation)) {
                    foreach ($rotation->condition as $detail) {
                        if ($detail->type == 'ORDER' && $detail->is_active == 1) {
                            if ($detail->price < $order->total_price) {
                                $user                = User::model()
                                    ->where('id', $order->customer_id)
                                    ->first();
                                $user->turn_rotation = $user->turn_rotation + 1;
                                $user->save();
                            }
                        }
                    }
                }
                if (empty($order->id)) {
                    return $this->responseError(Message::get("V003", Message::get("orders") . " #$order->id"));
                }

                // Check Free Ship
                if ($order->is_freeship == 1 && $order->ship_fee > 0) {
                    return $this->responseError(Message::get("orders.free_ship", " #{$order->code}"));
                }

                $result = [];
                $type   = !empty($order->shipping_method_code) ? $order->shipping_method_code : "DEFAULT";
                if (env('TEST_DVVC') == 1) {
                    $type = SHIPPING_PARTNER_TYPE_DEFAULT;
                }
                if ($type) {
                    switch ($type) {
                        case SHIPPING_PARTNER_TYPE_GHTK:
                            // $result = GHTK::sendOrder($order, $request);
                            break;
                        case SHIPPING_PARTNER_TYPE_GHN:
                            $result = GHN::sendOrderGHN($order);
                            break;
                        case SHIPPING_PARTNER_TYPE_GRAB:
                            $result = GRAB::sendOrder($order);
                            break;
                        case SHIPPING_PARTNER_TYPE_AHA:
                            break;
                        case SHIPPING_PARTNER_TYPE_NJV:
                            $token  = Setting::where('code', 'NINJA-VAN')->first();
                            $result = NJV::sendOrder($order, $token->value);
                            break;
                        case SHIPPING_PARTNER_TYPE_VNP:
                            $result = VNP::sendOrder($order, VNP::getToken());
                            break;
                        case SHIPPING_PARTNER_TYPE_VTP:
                            $token = VTP::getApiToken();
                            if ($token == [] || (isset($token['status']) && isset($token['success']) && $token['status'] == 'error' && $token['success'] == 'false')) {
                                break;
                            }
                            $result = VTP::sendOrder($order, $token);
                            break;
                        case SHIPPING_PARTNER_TYPE_DEFAULT:
                            $result = $this->createShippingOrder($order, []);
                            break;
                    }
                }
                if (!empty($result['status']) && $result['status'] == 'success' && $result['success'] == true) {
                    //Create Inventory && Update Quantity Warehouse
                    $this->createInventory($order, $result['warehouse'], $type);
                }
            }
            $result = !empty($result) ? $result : ["message" => "Tạo vận đơn thành công"];
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return response()->json($result);
    }

    private function createShippingOrder($order, $request)
    {
        $input = $request;
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order = Order::model()->with(['details.product', 'store', 'customer.profile'])->where('id', $order->id)->first();
            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get("orders")));
            }
            $weight_converts = ['KG' => 1, 'GRAM' => 0.001];
            $order_details   = [];
            $warehouse       = [];
            foreach ($order->details as $key => $detail) {
                $order_details[$detail->id] = $detail->toArray();
                array_push($warehouse, $detail->product);
                $warehouse[$key]['order_detail_id'] = $detail->id;
                $warehouse[$key]['warehouse_id']    = $detail->product->warehouse->warehouse_id;
                $warehouse[$key]['batch_id']        = $detail->product->warehouse->batch_id;
            }
            $products            = [];
            $shippingDetailParam = [];
            $transport           = 'fly';
            $status              = !empty($input['status']) && $input['status'] == SHIP_STATUS_SHIPPED ? SHIP_STATUS_SHIPPED : SHIP_STATUS_SHIPPING;
            if (!empty($input['transport']) && in_array($input['transport'], ['fly', 'road'])) {
                $transport = $input['transport'];
            }
            $allProduct   = Product::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($order_details, 'product_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allUnit      = Unit::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'unit_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allWarehouse = Warehouse::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'warehouse_id'))
                ->get()->pluck(null, 'id')->toArray();
            $allBatch     = Batch::model()->select(['id', 'code', 'name'])
                ->whereIn('id', array_column($warehouse, 'batch_id'))
                ->get()->pluck(null, 'id')->toArray();
            $now          = date("Y-m-d H:i:s");
            foreach ($warehouse as $input_detail) {
                if (empty($order_details[$input_detail['order_detail_id']])) {
                    throw new \Exception(Message::get("V003",
                        Message::get("order_details") . " #{$input_detail['order_detail_id']}"));
                }
                $order_detail = $order_details[$input_detail['order_detail_id']];
                if (empty($order_detail['product'])) {
                    throw new \Exception(Message::get("V003",
                        Message::get("products") . " #{$order_detail['product_id']}"));
                }
                $item = $order_detail['product'];
                if ((int)$input_detail['ship_qty'] + (int)$order_detail['shipped_qty'] > (int)$order_detail['qty']) {
                    throw new \Exception(Message::get("V013", 'ship_qty', 'shipped_qty'));
                }
                $inventory = WarehouseDetail::model()->select('quantity')->where([
                    'product_id'   => $order_detail['product_id'],
                    'warehouse_id' => $input_detail['warehouse_id'],
                    'batch_id'     => $input_detail['batch_id'],
                    'unit_id'      => $input_detail['unit_id'],
                    'company_id'   => 34,
                ])->first();
                if (empty($inventory) || $inventory->quantity < $input_detail['ship_qty']) {
                    throw new \Exception(Message::get("V051", $item['code']));
                }
                WarehouseDetail::model()->where([
                    'product_id'   => $order_detail['product_id'],
                    'warehouse_id' => $input_detail['warehouse_id'],
                    'batch_id'     => $input_detail['batch_id'],
                    'unit_id'      => $input_detail['unit_id'],
                    'company_id'   => 34,
                ])->update(['quantity' => $inventory->quantity - $order_detail['qty']]);
                if (empty($item['weight_class']) || !in_array($item['weight_class'], ['GRAM', 'KG'])) {
                    throw new \Exception(Message::get("V004",
                        "weight_class (" . Message::get("products") . " #{$order_detail['product_id']})", 'GRAM|KG'));
                }
                $products[]            = [
                    "name"     => $item['name'] ?? $order_detail['product_id'],
                    "weight"   => number_format($input_detail['ship_qty'] * $item['weight'] * $weight_converts[$item['weight_class']],
                        2),
                    "quantity" => $input_detail['ship_qty'],
                ];
                $waiting_qty           = $status == SHIP_STATUS_SHIPPING ? ($order_detail['qty']) : 0;
                $shippingDetailParam[] = [
                    'order_detail_id' => $order_detail['id'],
                    'product_id'      => $order_detail['product_id'],
                    'product_code'    => $allProduct[$order_detail['product_id']]['code'] ?? null,
                    'product_name'    => $allProduct[$order_detail['product_id']]['name'] ?? null,
                    'unit_id'         => $input_detail['unit_id'],
                    'unit_code'       => $allUnit[$input_detail['unit_id']]['code'] ?? null,
                    'unit_name'       => $allUnit[$input_detail['unit_id']]['name'] ?? null,
                    'warehouse_id'    => $input_detail['warehouse_id'],
                    'warehouse_code'  => $allWarehouse[$input_detail['warehouse_id']]['code'] ?? null,
                    'warehouse_name'  => $allWarehouse[$input_detail['warehouse_id']]['code'] ?? null,
                    'batch_id'        => $input_detail['batch_id'],
                    'batch_code'      => $allBatch[$input_detail['batch_id']]['code'] ?? null,
                    'batch_name'      => $allBatch[$input_detail['batch_id']]['name'] ?? null,
                    'qty'             => $order_detail['qty'],
                    'ship_qty'        => $order_detail['qty'],
                    'shipped_qty'     => $status == SHIP_STATUS_SHIPPED ? ($order_detail['qty'] + $order_detail['shipped_qty']) : 0,
                    'waiting_qty'     => $waiting_qty,
                    'price'           => $order_detail['price'],
                    'total_price'     => $order_detail['total'],
                    'discount'        => $order_detail['discount'],
                    'created_at'      => $now,
                    'created_by'      => 999999,
                ];
            }
            if (empty($order->shipping_address_full_name) || empty($order->shipping_address_city_name) || empty($order->shipping_address_district_name) || empty($order->shipping_address_phone)) {
                throw new \Exception(Message::get("V003", Message::get("receiver_address")));
            }
            if (empty($order->store->address) || empty($order->store->city_name) || empty($order->store->district_name) || empty($order->store->contact_phone)) {
                throw new \Exception(Message::get("V003", Message::get("pick_address")));
            }
            $lastShip = ShippingOrder::model()->where('ship_code', 'like',
                "{$order->code}-" . SHIPPING_PARTNER_TYPE_DEFAULT . "-%")->orderBy('id', 'desc')->first();

            $codeIndex = 0;
            if (!empty($lastShip)) {
                $codeIndex = explode("-" . SHIPPING_PARTNER_TYPE_DEFAULT . "-", $lastShip->ship_code);
                $codeIndex = (int)($codeIndex[1] ?? 0);
            }
//            $shipCode = $order->code . "-" . SHIPPING_PARTNER_TYPE_DEFAULT . "-" . (str_pad(++$codeIndex, 2, '0',
//                    STR_PAD_LEFT));

            $pickMoney = Arr::get($input, 'pick_money', 0) > 0 ? Arr::get($input, 'pick_money', 0) : 0;


            // Update Order
            $order->shipping_info_code = $order->code;
            $order->status             = $status;
            $order->save();
            // Create Shipping Order
            $shippingOrder                         = new ShippingOrder();
            $shippingOrder->type                   = "DEFAULT";
            $shippingOrder->code                   = $order->code;
            $shippingOrder->name                   = $order->code;
            $shippingOrder->count_print            = 0;
            $shippingOrder->partner_id             = $order->partner_id;
            $shippingOrder->status                 = $status;
            $shippingOrder->status_text            = SHIP_STATUS_NAME[$status];
            $shippingOrder->description            = Arr::get($input, 'description', '');
            $shippingOrder->ship_fee               = $order->fee;
            $shippingOrder->estimated_pick_time    = $order->estimated_pick_time;
            $shippingOrder->estimated_deliver_time = $order->estimated_deliver_time;
            $shippingOrder->pick_money             = $pickMoney;
            $shippingOrder->transport              = $transport;
            $shippingOrder->order_id               = $order->id;
            $shippingOrder->ship_code              = $order->code;
            $shippingOrder->company_id             = 34;
            $shippingOrder->result_json            = json_encode($order);
            $shippingOrder->created_at             = date("Y-m-d H:i:s");
            $shippingOrder->created_by             = 1234;
            $shippingOrder->save();
            // Update Order Detail
            if ($status == SHIP_STATUS_SHIPPED) {
                foreach ($shippingDetailParam as $key => $item) {
                    OrderDetail::model()->where('id', $item['order_detail_id'])
                        ->update(['shipped_qty' => $item['shipped_qty']]);
                    $shippingDetailParam[$key]['shipping_order_id'] = $shippingOrder->id;
                    unset($shippingDetailParam[$key]['order_detail_id']);
                }
            } else {
                foreach ($shippingDetailParam as $key => $item) {
                    $odrDtl              = OrderDetail::model()->where('id', $item['order_detail_id'])->first();
                    $odrDtl->waiting_qty = $item['waiting_qty'];
                    $odrDtl->save();
                    $shippingDetailParam[$key]['shipping_order_id'] = $shippingOrder->id;
                    unset($shippingDetailParam[$key]['order_detail_id']);
                }
            }

            // Create Shipping Order Detail
            ShippingOrderDetail::insert($shippingDetailParam);

            // Update order history
            $orderStatus        = OrderStatus::model()->where([
                'company_id' => 34,
                'code'       => $status
            ])->first();
            $orderSatusTHistory = OrderStatusHistory::model()->where([
                'order_id'          => $order->id,
                'order_status_code' => $status
            ])->get()->first();
            if (empty($orderSatusTHistory) && !empty($orderStatus)) {
                $param = [
                    'order_id'          => $order->id,
                    'order_status_id'   => $orderStatus->id,
                    'order_status_code' => $orderStatus->code,
                    'order_status_name' => $orderStatus->name,
                ];
                OrderStatusHistory::create($param);
                $param_shipping = [
                    'shipping_id'      => $order->code,
                    'status_code'      => $orderStatus->code,
                    'text_status_code' => $orderStatus->name,
                ];
                ShippingHistoryStatus::create($param_shipping);
            }

        }
        catch (\Exception $exception) {

            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, 'shipping_orders' => $shippingOrder, 'warehouse' => $warehouse];
    }

    private function createInventory($order, $input, $type)
    {
        try {
            if (empty($order) || empty($order->id)) {
                throw new \Exception(Message::get("V001", Message::get("orders")));
            }
            $order = Order::find($order->id);
            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get("orders")));
            }
            $data = [
                'date'        => date("Y-m-d H:i:s", time()),
                'description' => $type,
                'user_id'     => 1234,
                'company_id'  => 34,
                'status'      => INVENTORY_STATUS_COMPLETED,
                'type'        => INVENTORY_TYPE_X,
            ];

            foreach ($input as $detail) {
                $data['details'][] = [
                    'product_id'   => $detail['product_id'],
                    'warehouse_id' => $detail['warehouse_id'],
                    'unit_id'      => $detail['unit_id'],
                    'batch_id'     => $detail['batch_id'],
                    'quantity'     => $detail['ship_qty'],
                    // 'price'        => $detail['price'],
                ];
            }
            //Create Inventory && Update Quantity Warehouse
            $inventoryModel = new InventoryModel();
            $inventoryModel->upsert($data);
        }
        catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }
    }



//
//    public function createOrder(Request $request, OrderCreateValidator $orderCreateValidator)
//    {
//        $input = $request->all();
//        $orderCreateValidator->validate($input);
//        try {
//            DB::beginTransaction();
//            /** @var OrderModel $orderModel */
//            $orderModel = new OrderModel();
//            $order      = $orderModel->upsert($input);
//
//            // Update Order Status History
//            $orderModel->updateOrderStatusHistory($order);
//
//            // DB::commit();
//            $order = Order::model()->with([
//                'customer',
//                'store',
//                'partner',
//                'details.product.unit',
//                'details.product',
//                'approverUser',
//            ])->where('id', $order->id)->first();
//
//            // Send Email
//            $company = Company::model()->where('id', TM::getIDP()->company_id)->first();
////            dispatch(new SendCustomerMailNewOrderJob($order->customer->email, [
////                'logo'         => $company->avatar,
////                'support'      => $company->email,
////                'company_name' => $company->name,
////                'order'        => $order,
////            ]));
////            dispatch(new SendStoreMailNewOrderJob($order->store->email_notify, [
////                'logo'         => $company->avatar,
////                'support'      => $company->email,
////                'company_name' => $company->name,
////                'order'        => $order,
////            ]));
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->response->errorBadRequest($response['message']);
//        }
//        return $this->syncSuccess([
//            'code'             => $order->code,
//            'status'           => $order->status,
//            'customer_code'    => $order->customer_code,
//            'customer_name'    => $order->customer_name,
//            'shipping_address' => $order->shipping_address . ", " .
//                (object_get($order, 'getWard.full_name')) . ", " .
//                (object_get($order, 'getDistrict.full_name')) . ", " .
//                (object_get($order, 'getCity.full_name')),
//            'distributor_code' => $order->distributor_code,
//            'distributor_name' => $order->distributor_name,
//        ]);
//    }

//    public function updateOrder(
//        $code,
//        Request $request,
//        OrderUpdateValidator $orderUpdateValidator
//    )
//    {
//        $input         = $request->all();
//        $input['code'] = $code;
//        $orderUpdateValidator->validate($input);
//        try {
//            DB::beginTransaction();
//            $order       = Order::model()->where('code', $code)->first();
//            $originOrder = $order;
//
//            // Create History
//            if ($order->status != $input['status']) {
//                OrderHistory::insert([
//                    'order_id'   => $order->id,
//                    'status'     => $input['status'],
//                    'created_at' => date("Y-m-d H:i:s", time()),
//                    'created_by' => TM::getIDP()->sync_name,
//                ]);
//            }
//
//            $orderModel = new OrderModel();
//            $order      = $orderModel->upsert($input, true);
//
//            if ($order->status == ORDER_STATUS_CANCELED) {
//                $order->customer_point = null;
//                $order->save();
//            }
//
//            // Update Order Status History
//            $orderModel->updateOrderStatusHistory($order);
//
//            // Notify
////            $this->sendNotifyUpdateStatus($order);
//            DB::commit();
//        } catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->response->errorBadRequest($response['message']);
//        }
//
//        return ['status' => Message::get("orders.update-success", $order->code)];
//    }

//    /**
//     * @param $code
//     * @param Request $request
//     * @param ShipOrderValidator $shipOrderValidator
//     * @return array|\Illuminate\Http\JsonResponse
//     */
//    public function updateStatusOrder($code, Request $request, ShipOrderValidator $shipOrderValidator)
//    {
//        $input                      = $request->all();
//        $input['ORDER_NUMBER']      = $code;
//        $input['QUANTITY_APPROVED'] = !empty($input['QUANTITY_APPROVED']) ? $input['QUANTITY_APPROVED'] : 0;
//        $input['QUANTITY_PENDING']  = !empty($input['QUANTITY_PENDING']) ? $input['QUANTITY_PENDING'] : 0;
//        try {
//            $shipOrderValidator->validate($input);
//            DB::beginTransaction();
//            $order = Order::model()->where('code', $code)->first();
//            // Create History
//            if ($order->sync_status > 1) {
//                return $this->syncError("U", "Order not allowed: [Current Status = {$order->sync_status}]");
//            }
//
//            $details = array_pluck($order->details, null, 'product_code');
//            foreach ($input['details'] as $inputDetail) {
//                if (!empty($details[$inputDetail['PRODUCT_CODE']])) {
//                    /** @var OrderDetail $detail */
//                    $detail              = $details[$inputDetail['PRODUCT_CODE']];
//                    $detail->shipped_qty = !empty($inputDetail['QUANTITY_APPROVED']) ? $inputDetail['QUANTITY_APPROVED'] : 0;
//                    $detail->save();
//                }
//            }
//            $order->sync_status        = $input['STATUS'];
//            $order->search_sync_status = $input['STATUS'];
//            $order->save();
//            DB::commit();
//        } catch (DaiException $dai) {
//            return $this->syncError("U", $dai->getData());
//        } catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("U", $response['message']);
//        }
//
//        return $this->syncSuccess("U");
//    }

//    public function createProduct(Request $request, ProductCreateValidator $productCreateValidator)
//    {
//        $input = $request->all();
//
//        try {
//            $input['NetWeight']   = !empty($input['NetWeight']) ? $input['NetWeight'] : 0;
//            $input['GrossWeight'] = !empty($input['GrossWeight']) ? $input['GrossWeight'] : 0;
//            $productCreateValidator->validate($input);
//            DB::beginTransaction();
//            $input['code'] = $input['ProductCode'];
//            $check_product = Product::model()->where([
//                'code'     => $input['ProductCode'],
//                'store_id' => TM::getIDP()->store_id,
//            ])->first();
//            if (!empty($check_product)) {
//                return $this->syncError("C", Message::get("unique", $input['code']));
//            }
//            $productModel = new ProductModel();
//            $product      = $productModel->upsert($input);
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("C", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("C", $response['message']);
//        }
//        return $this->syncSuccess("U", [
//            'code' => $product->code,
//            'name' => $product->name,
//        ]);
//    }
//
//    public function updateProduct($code, Request $request, ProductUpdateValidator $productUpsertValidator)
//    {
//        $input         = $request->all();
//        $input['code'] = $code;
//
//        try {
//            $productUpsertValidator->validate($input);
//            DB::beginTransaction();
//            $productModel = new ProductModel();
//            $product      = $productModel->upsert($input, true);
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("U", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("U", $response['message']);
//        }
//
//        return $this->syncSuccess("U");
//    }
//
//    public function createDistributor(
//        Request $request,
//        DistributorCreateValidator $distributorCreateValidator
//    )
//    {
//        $input = $request->all();
//
//        try {
//            $input['CustomerCode'] = $input['ShopCode'] ?? null;
//            $input['CustomerName'] = !empty($input['ShopName']) ? $input['ShopName'] : $input['CustomerCode'];
//
//
//            $distributorCreateValidator->validate($input);
//            DB::beginTransaction();
//            $userModel = new UserModel();
//            $user      = $userModel->upsert($input);
//            //Send Mail
//            if (env('SEND_EMAIL', 0) == 1) {
//            if (!empty($user->email) && !empty($input['send_mail']) && $input['send_mail'] == 1) {
//                $storeName = Store::find(TM::getIDP()->store_id);
//                $mail      = [
//                    'to'        => $user->email,
//                    'user_name' => $user->name,
//                    'msg'       => $storeName->name,
//                    'phone'     => substr($user->phone, 0, -3) . '***',
//                    'password'  => $input['password'] ?? '[Not Available]',
//                ];
//                dispatch(new SendMailCustomerHubNewJob($mail['to'], $mail));
//            }
//        }
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("C", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("C", $response['message']);
//        }
//        return $this->syncSuccess("C", [
//            'code'  => $user->code,
//            'name'  => $user->name,
//            'email' => $user->email,
//            'phone' => $user->phone,
//        ]);
//    }

//    public function updateDistributor(
//        $code,
//        Request $request,
//        DistributorUpdateValidator $distributorUpdateValidator
//    )
//    {
//        $input                 = $request->all();
//        $input['code']         = $code;
//        $input['CustomerCode'] = $code;
//        $input['name']         = !empty($input['ShopName']) ? $input['ShopName'] : $input['CustomerCode'];;
//        $input['CustomerName'] = !empty($input['ShopName']) ? $input['ShopName'] : $input['CustomerCode'];
//
//        try {
//            $distributorUpdateValidator->validate($input);
//            DB::beginTransaction();
//            $userModel = new UserModel();
//            $user      = $userModel->upsert($input, true);
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("U", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("U", $response['message']);
//        }
//        return $this->syncSuccess("U", [
//            'code'  => $user->code,
//            'name'  => $user->name,
//            'email' => $user->email,
//            'phone' => $user->phone,
//        ]);
//    }

//    public function createCustomer(
//        Request $request,
//        CustomerCreateValidator $customerCreateValidator
//    )
//    {
//        $input = $request->all();
//
//        try {
//            $input['phone']           = !empty($input['Phone']) ? $input['Phone'] : 'VT_NOT_PHONE';
//            $input['code']            = $input['CustomerCode'];
//            $input['name']            = $input['CustomerName'];
//            $input['reference_phone'] = null;
//            $input['userRefer']       = null;
//            $customerCreateValidator->validate($input);
//
//            // Validate Reference Phone
//            if (!empty($input['reference_phone'])) {
//                $userRefer = User::model()->where('phone', $input['reference_phone'])
//                    ->where('company_id', TM::getIDP()->company_id)
//                    ->first();
//                if (empty($userRefer)) {
//                    return $this->syncError("C", Message::get("V003", Message::get("phone_introduce")));
//                }
//
//                if ($userRefer->account_status != 'approved') {
//                    return $this->syncError("C",
//                        Message::get("users.login-not-exist", Message::get('phone_introduce')));
//                }
//            }
//            $input['reference_phone'] = 1000;
//            $input['phone']           = str_replace([" ", "-"], "", $input['phone']);
//            $tmp                      = trim($input['phone'], "+0");
////            || !is_numeric($tmp)
//            if (strlen($input['phone']) < 10 || strlen($input['phone']) > 12) {
//                return $this->syncError("C", Message::get("V002", Message::get("phone")));
//            }
//            if (!empty($input["email"])) {
//                $email = $input["email"];
//                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
//                    return $this->syncError("C", Message::get("regex", Message::get("email")));
//                }
//
//                // Check duplicate Email
//                $user = User::model()->where('email', $input['email'])->first();
//                if ($user) {
//                    return $this->syncError("C", Message::get("unique", Message::get("email")));
//                }
//            }
//
//            $user = User::model()
//                ->where('phone', $input['phone'])
//                ->where('phone', '!=', 'VT_NOT_PHONE')
//                ->where('store_id', TM::getIDP()->store_id)
//                ->first();
//
//            if (!empty($user) && $user->is_active == '1') {
//                return $this->syncError("C", Message::get("unique", Message::get("phone")));
//            }
//            if (!empty($input['reference_phone'])) {
//                if ($input['phone'] == $input['reference_phone']) {
//                    return $this->syncError("C", Message::get("reference_phone_errors", $input['reference_phone']));
//                }
//            }
//            DB::beginTransaction();
//            $now  = date("Y-m-d H:i:s", time());
//            $user = $user ?? new User();
//            if (!empty($input['password'])) {
//                $user->password = password_hash($input['password'], PASSWORD_BCRYPT);
//            }
//            $user->name            = $input['name'];
//            $user->phone           = $input['phone'];
//            $user->email           = $input['email'] ?? null;
//            $user->code            = $input['code'];
//            $user->est_revenues    = $input['est_revenues'] ?? null;
//            $user->role_id         = USER_ROLE_GUEST_ID;
//            $user->type            = USER_TYPE_CUSTOMER;
//            $user->store_id        = TM::getIDP()->store_id;
//            $user->account_status  = ACCOUNT_STATUS_PENDING;
//            $user->customer_type   = CUSTOMER_TYPE_PARTNER;
//            $user->tax             = $input['tax'] ?? null;
//            $user->agent_register  = $input['agent_register'] ?? null;
//            $user->reference_phone = $input['reference_phone'] ?? null;
//            $user->channel_type    = $input['ChannelType'] ?? null;
//            $user->register_at     = $now;
//            $user->is_active       = 1;
//            $user->company_id      = TM::getIDP()->company_id;
//
//            $userGroup = UserGroup::model()->where('code', USER_GROUP_OUTLET)->first();
//            if (empty($userGroup)) {
//                throw new \Exception(Message::get("V043", Message::get("stores"), Message::get('user_groups')),
//                    400);
//            }
//            $user->group_id   = $userGroup->id;
//            $user->group_code = $userGroup->code;
//            $user->group_name = $userGroup->name;
//
//            $user->save();
//            // Assign Company
//            if (!empty($user->company_id)) {
//                $this->updateCompanyStore($user);
//            }
//
//            // Update Level
//            if ($input['reference_phone'] != 1000) {
//                $this->updateLevelNewPartner($user, $userRefer);
//            }
//
//            $this->accessLogin($user, $input);
//
//            // Assign Distributor
//            if ($user->group_code == USER_GROUP_AGENT) {
//                $this->assignDistributor($user);
//            }
//
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("C", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("C", $response['message']);
//        }
//        return $this->syncSuccess("C", [
//            'code'  => $user->code,
//            'name'  => $user->name,
//            'email' => $user->email,
//            'phone' => $user->phone,
//        ]);
//    }
//
//    public function updateCustomer(
//        $code,
//        Request $request,
//        CustomerUpdateValidator $customerUpdateValidator
//    )
//    {
//        $input                    = $request->all();
//        $input['code']            = $code;
//        $input['phone']           = $input['Phone'] ?? null;
//        $input['name']            = $input['CustomerName'];
//        $input['reference_phone'] = null;
//        $input['userRefer']       = null;
//        try {
//            $customerUpdateValidator->validate($input);
//            DB::beginTransaction();
//            $userModel = new UserModel();
//            $user      = $userModel->upsert($input, true, USER_TYPE_AGENT);
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("U", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("U", $response['message']);
//        }
//        return $this->syncSuccess("U", [
//            'code'  => $user->code,
//            'name'  => $user->name,
//            'email' => $user->email,
//            'phone' => $user->phone,
//        ]);
//    }

//    public function createPromotion(Request $request, PromotionCreateValidator $createValidator)
//    {
//        $input = $request->all();
//
//        try {
//            $createValidator->validate($input);
//            DB::beginTransaction();
//
//            $promotionModel = new PromotionModel();
//            $promotion      = $promotionModel->upsert($input);
//
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("C", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("C", $response['message']);
//        }
//
//        return $this->syncSuccess("C");
//    }

//    public function updatePromotion($id, Request $request, PromotionUpdateValidator $updateValidator)
//    {
//        $input       = $request->all();
//        $input['id'] = $id;
//
//        try {
//            $updateValidator->validate($input);
//            DB::beginTransaction();
//
//            $promotionModel = new PromotionModel();
//            $promotion      = $promotionModel->upsert($input);
//
//            DB::commit();
//        }
//        catch (DaiException $dai) {
//            return $this->syncError("U", $dai->getData());
//        }
//        catch (\Exception $ex) {
//            DB::rollBack();
//            $response = TM_Error::handle($ex);
//            return $this->syncError("U", $response['message']);
//        }
//
//        return $this->syncSuccess("U");
//    }

    ############################### PRIVATE FUNCTION #################################
//    private function getAutoOrderCode()
//    {
//        $orderType = "O";
//        $y = date("Y", time());
//        $m = date("m", time());
//        $d = date("d", time());
//        $lastCode = DB::table('orders')->select('code')->where('code', 'like', "$orderType$y$m$d%")->orderBy('id',
//            'desc')->first();
//        $index = "001";
//        if (!empty($lastCode)) {
//            $index = (int)substr($lastCode->code, -3);
//            $index = str_pad(++$index, 3, "0", STR_PAD_LEFT);
//        }
//        return "$orderType$y$m$d$index";
//    }
//
//    private function sendNotifyUpdateStatus($order)
//    {
//        $statusTitle = OrderStatus::model()->select([
//            'code',
//            'name',
//        ])->where(['company_id' => TM::getIDP()->company_id])
//            ->get()->pluck('name', 'code')->toArray();
//
//        try {
//            $title = "Đơn hàng #" . ($order->code) . " " . ($statusTitle[$order->status]);
//            //Notify
//            $notify = new Notify();
//            $notify->title = "Đơn hàng " . ($statusTitle[$order->status]);
//            $notify->body = $title;
//            $notify->type = "ORDER";
//            $notify->target_id = null;
//            $notify->product_search_query = null;
//            $notify->notify_for = 'ORDER';
//            $notify->delivery_date = date('Y-m-d H:i:s', time());
//            $notify->frequency = 'ASAP';
//            $notify->user_id = $order->customer_id;
//            $notify->company_id = TM::getIDP()->company_id;
//            $notify->sent = 0;
//            $notify->is_active = 1;
//            $notify->created_at = date('Y-m-d H:i:s', time());
//            $notify->created_by = $order->customer_id;
//            $notify->updated_at = date('Y-m-d H:i:s', time());
//            $notify->updated_by = $order->customer_id;
//            $notify->save();
//
//            //Get Device
//            $userSession = UserSession::model()->where('user_id', $userId = $order->customer_id)->where('deleted',
//                '0')->first();
//            $device = $userSession->device_token ?? null;
//
//            if (empty($device)) {
//                return false;
//            }
//
//
//            $notificationHistory = new NotificationHistory();
//            $notificationHistory->title = "Đơn hàng " . ($statusTitle[$order->status]);
//            $notificationHistory->body = $title;
//            $notificationHistory->message = $title;
//            $notificationHistory->notify_type = "ORDER";
//            $notificationHistory->type = "ORDER";
//            $notificationHistory->extra_data = '';
//            $notificationHistory->receiver = $device;
//            $notificationHistory->action = 1;
//            $notificationHistory->item_id = $order->id;
//            $notificationHistory->created_at = date('Y-m-d H:i:s', time());
//            $notificationHistory->created_by = $order->customer_id;
//            $notificationHistory->updated_at = date('Y-m-d H:i:s', time());
//            $notificationHistory->updated_by = $order->customer_id;
//            $notificationHistory->save();
//
//
//            DB::table('notification_histories')->where('id', $notificationHistory->id)->update([
//                'created_by' => $order->customer_id,
//                'updated_by' => $order->customer_id,
//            ]);
//            $notificationHistory = $notificationHistory->toArray();
//            $action = ["click_action" => "FLUTTER_NOTIFICATION_CLICK"];
//            $notificationHistory = array_merge($notificationHistory, $action);
//
//            $notification = ['title' => "Đơn hàng " . ($statusTitle[$order->status]), 'body' => $title];
//            $notificationHistory["click_action"] = "FLUTTER_NOTIFICATION_CLICK";
//            $notificationHistory["order_status"] = $order->status;
//            $fields = [
//                'data'         => $notificationHistory,
//                'notification' => $notification,
//                'to'           => $device,
//            ];
//
//            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];
//
//            $ch = curl_init();
//            curl_setopt($ch, CURLOPT_URL, env('FIREBASE_URL', ''));
//            curl_setopt($ch, CURLOPT_POST, true);
//            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
//            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
//            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
//
//            $result = curl_exec($ch);
//            if ($result === false) {
//                throw new \Exception('FCM Send Error: ' . curl_error($ch));
//            }
//            curl_close($ch);
//        } catch (\Exception $ex) {
//            throw $ex;
//        }
//        return $result;
//    }

//    private function updateCompanyStore(User $user)
//    {
//        // Delete Old Company
//        $role    = Role::model()->where('id', $user->role_id)->first();
//        $company = Company::model()->where('id', $user->company_id)->first();
//
//        // Create New Company
//        $time         = date('Y-m-d H:i:s', time());
//        $paramCompany = [
//            'user_id'      => $user->id,
//            'user_code'    => $user->code,
//            'user_name'    => $user->name,
//            'company_id'   => $company->id,
//            'company_code' => $company->code,
//            'company_name' => $company->name,
//            'role_id'      => $role->id,
//            'role_code'    => $role->code,
//            'role_name'    => $role->name,
//            'created_at'   => $time,
//            'created_by'   => TM::getIDP()->sync_name,
//            'updated_at'   => $time,
//            'updated_by'   => TM::getIDP()->sync_name,
//        ];
//
//        UserCompany::insert($paramCompany);
//    }
//
//    private function updateLevelNewPartner(User $newUser, User $referUser)
//    {
//        switch ($referUser->level_number) {
//            case 1:
//                $newUser->level_number     = 2;
//                $newUser->reference_id     = $referUser->id;
//                $newUser->reference_code   = $referUser->code;
//                $newUser->reference_name   = $referUser->name;
//                $newUser->user_level1_id   = $referUser->id;
//                $newUser->user_level1_code = $referUser->code;
//                $newUser->user_level1_name = $referUser->name;
//                $newUser->save();
//
//                $level2_ids                   = explode(",", $referUser->user_level2_ids);
//                $level2_ids[]                 = $newUser->id;
//                $level2_codes                 = explode(",", $referUser->user_level2_codes);
//                $level2_codes[]               = trim($newUser->code);
//                $level2_data                  = json_decode($referUser->user_level2_data, true);
//                $level2_data[$newUser->code]  = [
//                    'id'    => $newUser->id,
//                    'code'  => $newUser->code,
//                    'name'  => $newUser->name,
//                    'phone' => $newUser->phone,
//                ];
//                $referUser->user_level2_ids   = implode(",", array_filter(array_unique($level2_ids)));
//                $referUser->user_level2_codes = implode(",", array_filter(array_unique($level2_codes)));
//                $referUser->user_level2_data  = json_encode($level2_data);
//                $referUser->save();
//                break;
//            case 2:
//                $newUser->level_number      = 3;
//                $newUser->reference_id      = $referUser->id;
//                $newUser->reference_code    = $referUser->code;
//                $newUser->reference_name    = $referUser->name;
//                $newUser->user_level1_id    = $referUser->user_level1_id;
//                $newUser->user_level1_code  = $referUser->user_level1_code;
//                $newUser->user_level1_name  = $referUser->user_level1_name;
//                $newUser->user_level2_ids   = $referUser->id;
//                $newUser->user_level2_codes = $referUser->code;
//                $newUser->user_level2_data  = json_encode([
//                    $referUser->code => [
//                        'id'    => $referUser->id,
//                        'code'  => $referUser->code,
//                        'name'  => $referUser->name,
//                        'phone' => $referUser->phone,
//                    ],
//                ]);
//                $newUser->save();
//
//                $level3_ids                   = explode(",", $referUser->user_level3_ids);
//                $level3_ids[]                 = $newUser->id;
//                $level3_codes                 = explode(",", $referUser->user_level3_codes);
//                $level3_codes[]               = trim($newUser->code);
//                $level3_data                  = json_decode($referUser->user_level3_data, true);
//                $level3_data[$newUser->code]  = [
//                    'id'    => $newUser->id,
//                    'code'  => $newUser->code,
//                    'name'  => $newUser->name,
//                    'phone' => $newUser->phone,
//                ];
//                $referUser->user_level3_ids   = implode(",", array_filter(array_unique($level3_ids)));
//                $referUser->user_level3_codes = implode(",", array_filter(array_unique($level3_codes)));
//                $referUser->user_level3_data  = json_encode($level3_data);
//                $referUser->save();
//
//                $userLevel1                    = User::model()->where('id', $referUser->user_level1_id)->first();
//                $level3_ids                    = explode(",", $userLevel1->user_level3_ids);
//                $level3_ids[]                  = $newUser->id;
//                $level3_codes                  = explode(",", $userLevel1->user_level3_codes);
//                $level3_codes[]                = trim($newUser->code);
//                $level3_data                   = json_decode($userLevel1->user_level3_data, true);
//                $level3_data[$newUser->code]   = [
//                    'id'    => $newUser->id,
//                    'code'  => $newUser->code,
//                    'name'  => $newUser->name,
//                    'phone' => $newUser->phone,
//                ];
//                $userLevel1->user_level3_ids   = implode(",", array_filter(array_unique($level3_ids)));
//                $userLevel1->user_level3_codes = implode(",", array_filter(array_unique($level3_codes)));
//                $userLevel1->user_level3_data  = json_encode($level3_data);
//                $userLevel1->save();
//                break;
//            case 3:
//                $newUser->level_number      = 3;
//                $newUser->reference_id      = $referUser->id;
//                $newUser->reference_code    = $referUser->code;
//                $newUser->reference_name    = $referUser->name;
//                $newUser->user_level1_id    = (int)$referUser->user_level2_ids;
//                $newUser->user_level1_code  = $referUser->user_level2_codes;
//                $newUser->user_level1_name  = json_decode($referUser->user_level2_data,
//                        true)[$referUser->user_level2_codes]['name'] ?? null;
//                $newUser->user_level2_ids   = $referUser->id;
//                $newUser->user_level2_codes = $referUser->code;
//                $newUser->user_level2_data  = json_encode([
//                    $referUser->code => [
//                        'id'    => $referUser->id,
//                        'code'  => $referUser->code,
//                        'name'  => $referUser->name,
//                        'phone' => $referUser->phone,
//                    ],
//                ]);
//                $newUser->save();
//
//                $referUser->level_number      = 2;
//                $referUser->user_level1_id    = $newUser->user_level1_id;
//                $referUser->user_level1_code  = $newUser->user_level1_code;
//                $referUser->user_level1_name  = $newUser->user_level1_name;
//                $referUser->user_level2_ids   = null;
//                $referUser->user_level2_codes = null;
//                $referUser->user_level2_data  = null;
//                $referUser->user_level3_ids   = $newUser->id;
//                $referUser->user_level3_codes = $newUser->code;
//                $referUser->user_level3_data  = json_encode([
//                    $newUser->code => [
//                        'id'    => $newUser->id,
//                        'code'  => $newUser->code,
//                        'name'  => $newUser->name,
//                        'phone' => $newUser->phone,
//                    ],
//                ]);
//                $referUser->save();
//
//                $userLevel1 = User::model()->where('id', $referUser->user_level1_id)->first();
//                $userLevel0 = User::model()->where('id', $userLevel1->user_level1_id)->first();
//
//                $level2_ids = explode(",", $userLevel0->user_level2_ids);
//                if (($key = array_search($userLevel1->id, $level2_ids)) !== false) {
//                    unset($level2_ids[$key]);
//                }
//                $level2_codes = explode(",", $userLevel0->user_level2_codes);
//                if (($key = array_search($userLevel1->code, $level2_codes)) !== false) {
//                    unset($level2_codes[$key]);
//                }
//                $level2_data = json_decode($userLevel0->user_level2_data, true);
//                if (array_key_exists($userLevel1->code, $level2_data)) {
//                    unset($level2_data[$userLevel1->code]);
//                }
//                $userLevel0->user_level2_ids   = implode(",", array_filter(array_unique($level2_ids)));
//                $userLevel0->user_level2_codes = implode(",", array_filter(array_unique($level2_codes)));
//                $userLevel0->user_level2_data  = json_encode($level2_data);
//
//                $level3_ids = explode(",", $userLevel0->user_level3_ids);
//                if (($key = array_search($referUser->id, $level3_ids)) !== false) {
//                    unset($level3_ids[$key]);
//                }
//                $level3_codes = explode(",", $userLevel0->user_level3_codes);
//                if (($key = array_search($referUser->code, $level3_codes)) !== false) {
//                    unset($level3_codes[$key]);
//                }
//                $level3_data = json_decode($userLevel0->user_level3_data, true);
//                if (array_key_exists($referUser->code, $level3_data)) {
//                    unset($level3_data[$referUser->code]);
//                }
//                $userLevel0->user_level3_ids   = implode(",", array_filter(array_unique($level3_ids)));
//                $userLevel0->user_level3_codes = implode(",", array_filter(array_unique($level3_codes)));
//                $userLevel0->user_level3_data  = json_encode($level3_data);
//                $userLevel0->save();
//
//                $userLevel1->level_number     = 1;
//                $userLevel1->user_level1_id   = null;
//                $userLevel1->user_level1_code = null;
//                $userLevel1->user_level1_name = null;
//
//                $level3_ids                    = explode(",", $userLevel1->user_level3_ids);
//                $level3_ids[]                  = $referUser->id;
//                $level3_codes                  = explode(",", $userLevel1->user_level3_codes);
//                $level3_codes[]                = trim($referUser->code);
//                $level3_data                   = json_decode($userLevel1->user_level3_data, true);
//                $level3_data[$referUser->code] = [
//                    'id'    => $referUser->id,
//                    'code'  => $referUser->code,
//                    'name'  => $referUser->name,
//                    'phone' => $referUser->phone,
//                ];
//                $userLevel1->user_level2_ids   = implode(",", array_filter(array_unique($level3_ids)));
//                $userLevel1->user_level2_codes = implode(",", array_filter(array_unique($level3_codes)));
//                $userLevel1->user_level2_data  = json_encode($level3_data);
//                $userLevel1->user_level3_ids   = $newUser->id;
//                $userLevel1->user_level3_codes = $newUser->code;
//                $userLevel1->user_level3_data  = json_encode([
//                    $newUser->code => [
//                        'id'    => $newUser->id,
//                        'code'  => $newUser->code,
//                        'name'  => $newUser->name,
//                        'phone' => $newUser->phone,
//                    ],
//                ]);
//                $userLevel1->save();
//                break;
//        }
//    }
//
//    private function accessLogin(User $user, $input = null)
//    {
//        if (!empty($input['name'])) {
//            $user_id = $user->id;
//            $now     = date("Y-m-d H:i:s", time());
//            $profile = Profile::model()->where('user_id', $user->id)->first();
//            if (empty($profile)) {
//                $profile = new Profile();
//            }
//            $full = explode(" ", $input['name']);
//
//            $profile->full_name  = $input['name'];
//            $profile->first_name = trim($full[count($full) - 1]);
//            unset($full[count($full) - 1]);
//            $profile->last_name      = trim(implode(" ", $full));
//            $profile->user_id        = $user_id;
//            $profile->email          = $input['email'] ?? null;
//            $profile->phone          = $input['phone'] ?? null;
//            $profile->home_phone     = $input['phone'] ?? null;
//            $profile->landline_phone = $input['phone'] ?? null;
//            $profile->gender         = !empty($input['gender']) ? $input['gender'] : 'O';
//            $profile->id_number      = $input['id_number'] ?? null;
//            $profile->marital_status = $input['marital_status'] ?? null;
//            $profile->occupation     = $input['occupation'] ?? null;
//            $profile->city_code      = $input['city_code'] ?? null;
//            $profile->district_code  = $input['district_code'] ?? null;
//            $profile->ward_code      = $input['ward_code'] ?? null;
//            $profile->address        = $input['address'] ?? null;
//            $profile->education      = $input['education'] ?? null;
//            $profile->education      = $input['education'] ?? null;
//            $profile->birthday       = !empty($input['birthday']) ? date('Y-m-d', strtotime($input['birthday'])) : null;
//            $profile->created_by     = $user->id;
//            $profile->created_at     = $now;
//            $profile->updated_by     = $user->id;
//            $profile->updated_at     = $now;
//            $profile->save();
//        }
//    }
//
//    private function assignDistributor(User $user)
//    {
//        $distributors = User::model()->select([
//            'users.id',
//            'users.code',
//            'users.name',
//            'p.city_code',
//            'p.district_code',
//            'p.ward_code',
//        ])->join('profiles as p', 'p.user_id', '=', 'users.id')
//            ->where('company_id', $user->company_id)
//            ->where('group_code', USER_GROUP_DISTRIBUTOR)
//            ->get()->toArray();
//
//        if (empty($distributors)) {
//            return true;
//        }
//
//        // Find by Ward
//        $key = array_search($user->ward_code, array_column($distributors, 'ward_code'));
//
//        // Find by District
//        if (empty($key)) {
//            $key = array_search($user->district_code, array_column($distributors, 'district_code'));
//        }
//
//        // Find by City
//        if (empty($key)) {
//            $key = array_search($user->city_code, array_column($distributors, 'city_code'));
//        }
//
//        $key = (int)$key;
//
//        $user->distributor_id   = $distributors[$key]['id'];
//        $user->distributor_code = $distributors[$key]['code'];
//        $user->distributor_name = $distributors[$key]['name'];
//        $user->save();
//
//        return true;
//    }

    /**
     * @param array $errors
     * @param string $msg
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    private function syncError($type, $errors = [], $msg = 'Something went wrong!', $code = 200)
    {
        $errors = !is_array($errors) ? [$errors] : $errors;
        return response()->json([
            'dataStatus'  => $type,
            'isSuccess'   => 'F',
            'status_code' => $code,
            'message'     => $msg,
            'errors'      => $errors,
        ], $code);
    }

    /**
     * @param array $data
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    private function syncSuccess($type, $msg = 'Something went wrong!', $code = 200)
    {
        return response()->json([
            'dataStatus'  => $type,
            'isSuccess'   => 'S',
            'status_code' => $code,
            'message'     => $msg,
            'data'        => [],
        ]);
    }
}