<?php


namespace App\V1\Models;


use App\Batch;
use App\Product;
use App\ShipOrder;
use App\ShipOrderDetail;
use App\Supports\Message;
use App\TM;
use App\Warehouse;
use Illuminate\Support\Facades\DB;

class ShipOrderModel extends AbstractModel
{
    public function __construct(ShipOrder $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $code = $this->getAutoCode();
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $shipOrder = ShipOrder::find($id);
            if (empty($shipOrder)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $shipOrder->order_id = array_get($input, 'order_id', $shipOrder->order_id);
            $shipOrder->code = array_get($input, 'code', $shipOrder->code);
            $shipOrder->order_code = array_get($input, 'order_code', $shipOrder->order_code);
            $shipOrder->order_id = array_get($input, 'order_id', $shipOrder->order_id);
            $shipOrder->status = array_get($input, 'status', $shipOrder->status);
            $shipOrder->company_id = array_get($input, 'company_id', $shipOrder->company_id);
            $shipOrder->store_id = array_get($input, 'store_id', $shipOrder->store_id);
            $shipOrder->customer_id = TM::getCurrentCompanyId();
            $shipOrder->customer_name = array_get($input, 'customer_name', $shipOrder->customer_name);
            $shipOrder->customer_code = array_get($input, 'customer_code', $shipOrder->customer_code);
            $shipOrder->customer_email = array_get($input, 'customer_email', $shipOrder->customer_email);
            $shipOrder->customer_phone = array_get($input, 'customer_phone', $shipOrder->customer_phone);
            $shipOrder->created_date = !empty($input['created_date']) ? date('Y-m-d',
                strtotime($input['created_date'])) : $shipOrder->created_date;
            $shipOrder->approver = array_get($input, 'approver', $shipOrder->approver);
            $shipOrder->approver_name = array_get($input, 'approver_name', $shipOrder->approver_name);
            $shipOrder->qty_equal = array_get($input, 'qty_equal', $shipOrder->qty_equal);
            $shipOrder->count_qty_ship = array_get($input, 'count_qty_ship', $shipOrder->count_qty_ship);
            $shipOrder->description = array_get($input, 'description', $shipOrder->description);
            $shipOrder->shipping_address = array_get($input, 'shipping_address', $shipOrder->shipping_address);
            $shipOrder->real_date = !empty($input['real_date']) ? date('Y-m-d',
                strtotime($input['real_date'])) : $shipOrder->real_date;
            $shipOrder->total_price = array_get($input, 'total_price', $shipOrder->total_price);
            $shipOrder->qty_equal_shipped_order = array_get($input, 'qty_equal_shipped_order',
                $shipOrder->qty_equal_shipped_order);
            $shipOrder->payment_method = array_get($input, 'payment_method', $shipOrder->payment_method);
            $shipOrder->payment_method_name = array_get($input, 'payment_method_name', $shipOrder->payment_method_name);
            $shipOrder->status_name = array_get($input, 'status_name', $shipOrder->status_name);
            $shipOrder->payment_status_name = array_get($input, 'payment_status_name', $shipOrder->payment_status_name);
            $shipOrder->payment_status = array_get($input, 'payment_status', $shipOrder->payment_status);
            $shipOrder->shipping_address_full_name = array_get($input, 'shipping_address_full_name',
                $shipOrder->shipping_address_full_name);
            $shipOrder->shipping_address_phone = array_get($input, 'shipping_address_phone',
                $shipOrder->shipping_address_phone);
            $shipOrder->street_address = array_get($input, 'street_address', $shipOrder->street_address);
            $shipOrder->shipping_address_city_code = array_get($input, 'shipping_address_city_code',
                $shipOrder->shipping_address_city_code);
            $shipOrder->shipping_address_city = array_get($input, 'shipping_address_city',
                $shipOrder->shipping_address_city);
            $shipOrder->shipping_address_district_code = array_get($input, 'shipping_address_district_code',
                $shipOrder->shipping_address_district_code);
            $shipOrder->shipping_address_district = array_get($input, 'shipping_address_district',
                $shipOrder->shipping_address_district);
            $shipOrder->shipping_address_ward_code = array_get($input, 'shipping_address_ward_code',
                $shipOrder->shipping_address_ward_code);
            $shipOrder->shipping_address_ward = array_get($input, 'shipping_address_ward',
                $shipOrder->shipping_address_ward);
            $shipOrder->exp = !empty($input['exp']) ? date('Y-m-d',
                strtotime($input['exp'])) : $shipOrder->exp;
            $shipOrder->mfg = !empty($input['mfg']) ? date('Y-m-d',
                strtotime($input['mfg'])) : $shipOrder->mfg;
            $shipOrder->is_active = array_get($input, 'is_active', $shipOrder->is_active);
            $shipOrder->save();
        } else {
            $param = [
                'code'                           => $code,
                'order_code'                     => $input['order_code'],
                'order_id'                       => $input['order_id'],
                'status'                         => array_get($input, 'status', null),
                'company_id'                     => TM::getCurrentCompanyId(),
                'store_id'                       => $input['store_id'],
                'customer_id'                    => $input['customer_id'],
                'customer_name'                  => array_get($input, 'customer_name', null),
                'customer_code'                  => array_get($input, 'customer_code', null),
                'customer_email'                 => array_get($input, 'customer_email', null),
                'customer_phone'                 => array_get($input, 'customer_phone', null),
                'created_date'                   => date("Y-m-d",
                    strtotime(array_get($input, 'created_date', date("Y-m-d", null)))),
                'approver'                       => $input['approver'],
                'approver_name'                  => array_get($input, 'approver_name', null),
                'qty_equal'                      => array_get($input, 'qty_equal', null),
                'count_qty_ship'                 => array_get($input, 'count_qty_ship', null),
                'description'                    => array_get($input, 'description', null),
                'shipping_address'               => array_get($input, 'shipping_address', null),
                'real_date'                      => date("Y-m-d",
                    strtotime(array_get($input, 'real_date', date("Y-m-d", null)))),
                'total_price'                    => array_get($input, 'total_price', null),
                'qty_equal_shipped_order'        => array_get($input, 'qty_equal_shipped_order', null),
                'payment_method'                 => array_get($input, 'payment_method', null),
                'payment_method_name'            => array_get($input, 'payment_method_name', null),
                'status_name'                    => array_get($input, 'status_name', null),
                'payment_status_name'            => array_get($input, 'payment_status_name', null),
                'payment_status'                 => array_get($input, 'payment_status', null),
                'shipping_address_full_name'     => array_get($input, 'shipping_address_full_name', null),
                'shipping_address_phone'         => array_get($input, 'shipping_address_phone', null),
                'street_address'                 => array_get($input, 'street_address', null),
                'shipping_address_city_code'     => array_get($input, 'shipping_address_city_code', null),
                'shipping_address_city'          => array_get($input, 'shipping_address_city', null),
                'shipping_address_district_code' => array_get($input, 'shipping_address_district_code', null),
                'shipping_address_district'      => array_get($input, 'shipping_address_district', null),
                'shipping_address_ward_code'     => array_get($input, 'shipping_address_ward_code', null),
                'shipping_address_ward'          => array_get($input, 'shipping_address_ward', null),
                'exp'                            => !empty($input['exp']) ? date("Y-m-d",
                    strtotime(array_get($input, 'exp'))) : null,
                'mfg'                            => !empty($input['mfg']) ? date("Y-m-d",
                    strtotime(array_get($input, 'mfg'))) : null,
                'is_active'                      => array_get($input, 'is_active', 1),
            ];
            $shipOrder = $this->create($param);
        }

        // Create|Update ShipOrderDetail
        $shipOrderDetailId = $shipOrder->id;
        if (!empty($input['details'])) {
            $allShipOrderDetail = ShipOrderDetail::model()->where('ship_id', $shipOrderDetailId)->get()->toArray();
            $allShipOrderDetail = array_pluck($allShipOrderDetail, 'id', 'id');
            $allShipOrderDetailDelete = $allShipOrderDetail;
            foreach ($input['details'] as $key => $item) {
                $id = $item['id'] ?? null;
                if (!empty($allShipOrderDetailDelete[$id])) {
                    unset($allShipOrderDetailDelete[$id]);
                }
                $shipOderDetail = ShipOrderDetail::find($id);
                $shipOrderDetailProduct = Product::find($item['product_id']);
                $shipOrderDetailBatch = Batch::find($item['batch_id']);
                $shipOrderDetailWarehouse = Warehouse::find($item['warehouse_id']);
                if (empty($shipOderDetail)) {
                    $param = [
                        'ship_id'                 => $shipOrderDetailId,
                        'order_detail_id'         => $item['order_detail_id'],
                        'company_id'              => TM::getCurrentCompanyId(),
                        'product_id'              => $item['product_id'],
                        'product_code'            => $shipOrderDetailProduct->code,
                        'product_name'            => $shipOrderDetailProduct->name,
                        'warehouse_id'            => $item['warehouse_id'],
                        'warehouse_code'          => $shipOrderDetailWarehouse->code,
                        'warehouse_name'          => $shipOrderDetailWarehouse->name,
                        'batch_id'                => $item['batch_id'],
                        'batch_code'              => $shipOrderDetailBatch->code,
                        'batch_name'              => $shipOrderDetailBatch->name,
                        'product_unit'            => array_get($item, 'product_unit', null),
                        'product_unit_name'       => array_get($item, 'product_unit_name', null),
                        'store_id'                => $item['store_id'],
                        'count_qty_ship'          => array_get($item, 'count_qty_ship', null),
                        'sum_qty_product_shipped' => array_get($item, 'sum_qty_product_shipped', null),
                        'available_qty'           => array_get($item, 'available_qty', null),
                        'ship_qty'                => array_get($item, 'ship_qty', null),
                        'shipped_qty'             => array_get($item, 'shipped_qty', null),
                        'qty'                     => array_get($item, 'qty', null),
                        'price'                   => array_get($item, 'price', null),
                        'is_active'               => array_get($item, 'is_active', 1),
                        'discount'                => array_get($item, 'discount', null),
                        'total'                   => array_get($item, 'total', null),
                        'item_id'                 => array_get($item, 'item_id', null),
                    ];
                    $shipOrderDetailModel = new ShipOrderDetail();
                    $shipOrderDetailModel->create($param);
                } else {
                    $shipOderDetail->ship_id = $shipOrderDetailId;
                    $shipOderDetail->order_detail_id = $item['order_detail_id'];
                    $shipOderDetail->company_id = TM::getCurrentCompanyId();
                    $shipOderDetail->product_id = $item['product_id'];
                    $shipOderDetail->product_code = $shipOrderDetailProduct->code;
                    $shipOderDetail->product_name = $shipOrderDetailProduct->name;
                    $shipOderDetail->warehouse_id = $item['warehouse_id'];
                    $shipOderDetail->warehouse_code = $shipOrderDetailWarehouse->code;
                    $shipOderDetail->warehouse_name = $shipOrderDetailWarehouse->name;
                    $shipOderDetail->batch_id = $item['batch_id'];
                    $shipOderDetail->batch_code = $shipOrderDetailBatch->code;
                    $shipOderDetail->batch_name = $shipOrderDetailBatch->name;
                    $shipOderDetail->product_unit = array_get($item, 'product_unit', null);
                    $shipOderDetail->product_unit_name = array_get($item, 'product_unit_name', null);
                    $shipOderDetail->store_id = $item['store_id'];
                    $shipOderDetail->count_qty_ship = array_get($item, 'count_qty_ship', null);
                    $shipOderDetail->sum_qty_product_shipped = array_get($item, 'count_qty_ship', null);
                    $shipOderDetail->available_qty = array_get($item, 'available_qty', null);
                    $shipOderDetail->ship_qty = array_get($item, 'ship_qty', null);
                    $shipOderDetail->shipped_qty = array_get($item, 'shipped_qty', null);
                    $shipOderDetail->qty = array_get($item, 'qty', null);
                    $shipOderDetail->price = array_get($item, 'price', null);
                    $shipOderDetail->is_active = array_get($item, 'is_active', 1);
                    $shipOderDetail->discount = array_get($item, 'discount', null);
                    $shipOderDetail->total = array_get($item, 'total', null);
                    $shipOderDetail->item_id = array_get($item, 'item_id', null);
                    $shipOderDetail->updated_at = date('Y-m-d H:i:s', time());
                    $shipOderDetail->updated_by = TM::getCurrentUserId();
                    $shipOderDetail->save();
                }
            }
            if (!empty($allShipOrderDetailDelete)) {
                ShipOrderDetail::model()->whereIn('id', array_values($allShipOrderDetailDelete))->delete();
            }
            return $shipOrder;
        }
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $query->where('company_id', TM::getCurrentCompanyId());

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

    public function getAutoCode()
    {
        $y = date("Y", time());
        $m = date("m", time());
        $d = date("d", time());
        $lastCode = DB::table('ship_orders')
            ->select('code')->where('code', 'like', "$y$m$d%")->orderBy('id', 'desc')->first();
        $index = "001";
        if (!empty($lastCode)) {
            $index = (int)substr($lastCode->code, -3);
            $index = str_pad(++$index, 3, "0", STR_PAD_LEFT);
        }
        return "$y$m$d$index";
    }
}