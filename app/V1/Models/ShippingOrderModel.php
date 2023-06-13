<?php
/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:20 PM
 */

namespace App\V1\Models;


use App\Batch;
use App\Order;
use App\OrderDetail;
use App\OrderHistory;
use App\OrderStatus;
use App\OrderStatusHistory;
use App\Product;
use App\ShippingHistoryStatus;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\User;
use App\Warehouse;
use App\WarehouseDetail;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ShippingOrderModel extends AbstractModel
{
    public function __construct(ShippingOrder $model = null)
    {
        parent::__construct($model);
    }

    public function sendOrder($orderId, $input)
    {

    }

    public function createInventory($order, $input, $type)
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
                'user_id'     => TM::getCurrentUserId(),
                'company_id'  => TM::getCurrentCompanyId(),
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

    public function createShippingOrder($order, $request)
    {
        $input = $request->all();
        if (empty($input['status'])){
            $input['status'] = SHIP_STATUS_SHIPPING;
        }
        if (empty($input['transport'])){
            $input['transport'] = 'road';
        }
        if (empty($input['pick_money'])){
            $input['pick_money'] = 0;
        }
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
            $warehouse = [];
            foreach ($order->details as $key => $detail) {
                $order_details[$detail->id] = $detail->toArray();
                array_push($warehouse, $detail->product);
                $warehouse[$key]['order_detail_id'] = $detail->id;
                $warehouse[$key]['warehouse_id'] = $detail->product->warehouse->warehouse_id;
                $warehouse[$key]['batch_id'] = $detail->product->warehouse->batch_id;
            }
            $products            = [];
            $shippingDetailParam = [];
            $transport           = 'fly';
            $status              = !empty($input['status'] ) && $input['status'] == SHIP_STATUS_SHIPPED ? SHIP_STATUS_SHIPPED : SHIP_STATUS_SHIPPING;
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
                    'company_id'   => TM::getCurrentCompanyId(),
                ])->first();
                if (empty($inventory) || $inventory->quantity < $input_detail['ship_qty']) {
                    throw new \Exception(Message::get("V051", $item['code']));
                }
                WarehouseDetail::model()->where([
                    'product_id'   => $order_detail['product_id'],
                    'warehouse_id' => $input_detail['warehouse_id'],
                    'batch_id'     => $input_detail['batch_id'],
                    'unit_id'      => $input_detail['unit_id'],
                    'company_id'   => TM::getCurrentCompanyId(),
                ])->update(['quantity'=>$inventory->quantity - $order_detail['qty']]);
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
                    'created_by'      => TM::getCurrentUserId(),
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
//            $order->shipping_info_json = !empty($response['order']) ? json_encode($response['order']) : null;
            $order->status = $status;
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
            $shippingOrder->delivery_status        = "Đang giao hàng";
            $shippingOrder->company_id             = TM::getCurrentCompanyId();
            $shippingOrder->result_json            = json_encode($order);
            $shippingOrder->created_at             = date("Y-m-d H:i:s");
            $shippingOrder->created_by             = TM::getCurrentUserId();
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
                'company_id' => TM::getCurrentCompanyId(),
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

    public function updateShippingOrder($input)
    {
        $id            = $input['id'];
        $shippingOrder = ShippingOrder::model()->where([
            'id'         => $id,
            'company_id' => TM::getCurrentCompanyId()
        ])->first();
        if (empty($shippingOrder)) {
            throw new \Exception(Message::get("V003", "ID: #$id"));
        }
        $shippingOrder->status = $input['status'] ?? $shippingOrder->status;
        $shippingOrder->save();
        $shippingOrderDetailId = $shippingOrder->id;
        $shippingOrderId       = $shippingOrder->order_id;
        // Update Shipping Order Details
        if (!empty($shippingOrder) && !empty($input['details'])) {
            foreach ($input['details'] as $item) {
                $shippingOrderDetail = ShippingOrderDetail::model()->where([
                    'shipping_order_id' => $shippingOrderDetailId,
                    'product_id'        => $item['product_id']
                ])->first();
                $orderDetail         = OrderDetail::model()->where([
                    'order_id'   => $shippingOrder->order_id,
                    'product_id' => $item['product_id']
                ])->first();
                // Update shipping order status SHIPPING
                if ($input['status'] == ORDER_STATUS_SHIPPING) {
                    // update order detail
                    $orderDetail->waiting_qty = Arr::get($item, 'ship_qty', $orderDetail->waiting_qty);
                    $orderDetail->save();

                    // update shipping order details
                    $shippingOrderDetail->ship_qty    = Arr::get($item, 'ship_qty', $shippingOrderDetail->ship_qty);
                    $shippingOrderDetail->waiting_qty = Arr::get($item, 'ship_qty', $shippingOrderDetail->ship_qty);
                    $shippingOrderDetail->save();
                }
                // Update shipping order status COMPLETED OR CANCELED OR COMPLETED
                if ($input['status'] == ORDER_STATUS_SHIPPED || $input['status'] == ORDER_STATUS_CANCELED || $input['status'] == ORDER_STATUS_COMPLETED) {
                    // update order detail
                    $orderDetail->shipped_qty = Arr::get($item, 'ship_qty');
                    $orderDetail->save();

                    // update shipping order details
                    $shippingOrderDetail->ship_qty    = Arr::get($item, 'ship_qty');
                    $shippingOrderDetail->shipped_qty = Arr::get($item, 'ship_qty');
                    $shippingOrderDetail->save();

                }
                $orders = Order::find($shippingOrderId);
                // dd($orders);
                switch ($input['status']) {
                    case ORDER_STATUS_COMPLETED:
                        $orders->status = ORDER_STATUS_COMPLETED;
                        break;
                    case ORDER_STATUS_SHIPPED:
                        $orders->status = ORDER_STATUS_SHIPPED;
                        $param_shipping = [
                            'shipping_id'      => $orders->code,
                            'status_code'      => ORDER_STATUS_SHIPPED,
                            'text_status_code' => ORDER_STATUS_NAME[ORDER_STATUS_SHIPPED] ?? null,
                        ];
                        ShippingHistoryStatus::create($param_shipping);
                        break;
                    case ORDER_STATUS_CANCELED:
                        $orders->status = ORDER_STATUS_CANCELED;
                        break;
                }
                if (empty($input['payment_status'])) {
                    $input['payment_status'] = 0;
                }
                $orders->payment_status = $input['payment_status'];
                $orders->save();
            }
        }

        (new OrderModel())->updateOrderStatusHistory($shippingOrder->order);
        return $shippingOrder;
    }


    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['count_print'])) {
            if ($input['count_print'] == 1) {
                $query->where('count_print', '>', 0);
            }
            if ($input['count_print'] == 2) {
                $query->where('count_print', 0);
            }
        }
        $roleCurrentGroup = TM::getCurrentRoleGroup();
//        $query->where('company_id',TM::getCurrentCompanyId());
            $query->whereHas('order', function ($q) use ($input,$roleCurrentGroup) {
            $q->where('store_id', TM::getCurrentStoreId());
                if ($roleCurrentGroup != USER_ROLE_GROUP_ADMIN) {
                $q->where(function ($q2) use ($roleCurrentGroup) {
                    switch ($roleCurrentGroup) {
                        case USER_ROLE_GROUP_MANAGER:
                            $distributorCode = User::model()->where('distributor_center_code', TM::info()['code'])->pluck('code')->toArray();
                            $distributorCode[] = TM::info()['code'];
                            $q2->where(function ($q3) use ($distributorCode) {
                                $q3->whereIn('orders.distributor_code', $distributorCode);
                                $q3->where('orders.status_crm','!=', ORDER_STATUS_CRM_PENDING);
                            });
                            break;
                        case USER_ROLE_GROUP_EMPLOYEE:
                            if(TM::info()['role_code'] == USER_GROUP_DISTRIBUTOR){
                                $q2->where(function ($q) {
                                    $q->where([
                                        'orders.distributor_code' => TM::info()['code'],
                                        // 'orders.status_crm' => ORDER_STATUS_CRM_ADAPPROVED,
                                    ]);
                                   $q->where('orders.status_crm','!=',ORDER_STATUS_CRM_PENDING);
                                });
                            }
                            if(TM::info()['role_code'] == USER_GROUP_HUB){
                                $q2->where(function ($q) {
                                    $q->where([
                                        'orders.distributor_code' => TM::info()['code'],
//                                        'orders.status_crm' => ORDER_STATUS_CRM_ADAPPROVED,
                                    ]);
                                    $q->where( 'orders.status_crm','!=',ORDER_STATUS_CRM_PENDING);
                                });
                            }
                            break;
                    }
//                    $q2->where('orders.distributor_id', TM::getCurrentUserId());
//                        ->orWhere('orders.distributor_code', TM::info()['id'])
//                        ->orWhere('orders.partner_id', TM::getCurrentUserId());
                });
            } else {
                if (!empty($input['distributor_id'])) {
                    $q->where('distributor_id', $input['distributor_id']);
                }
                if (!empty($input['distributor_code'])) {
                    $q->where('distributor_code', $input['distributor_code']);
                }
                if (!empty($input['distributor_name'])) {
                    $q->where('distributor_name', $input['distributor_name']);
                }
                if (!empty($input['sync_status'])) {
                    $q->where('sync_status', $input['sync_status']);
                }
                if (!empty($input['search_sync_status'])) {
                    $q->where('search_sync_status', $input['search_sync_status']);
                }
            }

        });
        if (!empty($input['customer_name'])) {
            $query->whereHas('order', function ($q) use ($input) {
                $q->where('customer_name', 'like', "%{$input['customer_name']}%");
            });
        }
        if (!empty($input['cusomer_phone '])) {
            $query->whereHas('order', function ($q) use ($input) {
                $q->where('cusomer_phone ',$input['customer_name']);
            });
        }
        if (!empty($input['status'])) {
            $query->where('status', 'like', "%{$input['status']}%");
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
}
