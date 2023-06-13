<?php
/**
 * User: kpistech2
 * Date: 2019-06-30
 * Time: 01:07
 */

namespace App\V1\Controllers;

use App\Category;
use App\Exports\CustomerPointExport;
use App\Exports\OrderDetailExport;
use App\Exports\PriceDetailExport;
use App\Exports\PrintOrderBillExport;
use App\Exports\ReportInventoryExport;
use App\Exports\SaleByProductExport;
use App\Exports\SaleTotalByCustomerExport;
use App\Exports\SaleTotalByMonthExport;
use App\File;
use App\Inventory;
use App\MembershipRank;
use App\Order;
use App\OrderDetail;
use App\OrderHistory;
use App\Price;
use App\Product;
use App\Role;
use App\ShipOrder;
use App\ShippingOrder;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\UserGroup;
use App\V1\Models\OrderModel;
use App\V1\Traits\ReportTrait;
use App\WarehouseDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends BaseController
{
    use ReportTrait;

    /**
     * ReportController constructor.
     */
    protected $orderModel;

    /**
     * ReportController constructor.
     */
    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    public function reportOrderByDay(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", "Date from"));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", "Date to"));
        }
        try {
            $date   = date('YmdHis', time());
            $orders = Order::model()->select([
                DB::raw("sum(od.qty) as qty"),
                'p.name as product_name',
                'od.price',
                'od.price_down',
            ])->join('order_details as od', 'od.order_id', '=', 'orders.id')
                ->join('products as p', 'p.id', '=', 'od.product_id')
                ->where('orders.completed_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
                ->where('orders.completed_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
                ->groupBy(['product_id', 'od.price_down'])->get()->toArray();

            $dataPrint = [];
            $total     = 0;
            foreach ($orders as $key => $order) {
                $total_price = $order['qty'] * (!empty($order['price_down']) ? $order['price_down'] : $order['price']);
                $total       += $total_price;
                $dataPrint[] = [
                    'stt'          => ++$key,
                    'product_name' => $order['product_name'],
                    'qty'          => $order['qty'],
                    'price'        => $order['price'],
                    'price_down'   => $order['price_down'],
                    'total_price'  => $total_price,
                ];
            }
            $input['total']     = $total;
            $input['dataTable'] = $dataPrint;
            $this->writeExcelOrder("Report-Order-By-Day_$date", storage_path('Report') . "/ReportOrderByDay", $input);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportOrderGrand(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", "Date from"));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", "Date to"));
        }
        try {
            $orders = Order::with(['customer', 'details.product'])
                ->where('orders.created_at', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
                ->where('orders.created_at', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
                ->where('store_id', TM::getCurrentStoreId())
                ->get();

            $input['orders'] = $orders;

            $this->writeExcelOrderGrand("Report-Order-Grand_" . date('YmdHis', time()), storage_path('Report') . "/ReportOrderGrand", $input);
        } catch (\Exception $ex) {
            throw $ex;
//            $response = TM_Error::handle($ex);
//            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportPartnerTurnover($id, Request $request)
    {
        $input = $request->all();
        $user  = User::find($id);
        try {
            $date   = date('YmdHis', time());
            $orders = Order::model()->select([
                DB::raw("sum(od.qty) as qty"),
                'p.name as product_name',
                'p.type',
                'od.price',
                'od.price_down',
                'orders.code as order_code',
                'orders.completed_date as date'
            ])->join('order_details as od', 'od.order_id', '=', 'orders.id')
                ->join('products as p', 'p.id', '=', 'od.product_id')
                ->where('orders.completed_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
                ->where('orders.completed_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
                ->where('orders.partner_id', $id)
                ->groupBy(['product_id', 'od.price_down'])->get()->toArray();

            $dataPrint = [];
            $total     = 0;
            foreach ($orders as $key => $order) {
                $total_price = $order['qty'] * (!empty($order['price_down']) ? $order['price_down'] : $order['price']);
                $total       += $total_price;
                $dataPrint[] = [
                    'stt'          => ++$key,
                    'product_name' => $order['product_name'],
                    'type'         => PRODUCT_TYPE_NAME[$order['type']],
                    'order_code'   => $order['order_code'],
                    'price'        => $order['price'],
                    'price_down'   => $order['price_down'],
                    'qty'          => $order['qty'],
                    'total_price'  => $total_price,
                    'date'         => date('d-m-Y', strtotime($order['date'])),
                ];
            }
            $input['total']     = $total;
            $input['name']      = object_get($user, "profile.full_name");
            $input['phone']     = $user->phone;
            $input['email']     = object_get($user, "profile.email");
            $input['dataTable'] = $dataPrint;
            $this->writeExcelPartnerTurnover("Report-Partner-Turnover_$date",
                storage_path('Report') . "/ReportPartnerTurnover", $input);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportProduct(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", "Date from"));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", "Date to"));
        }
        try {
            $products    = Product::model()->get()->toArray();
            $date        = date('YmdHis', time());
            $dataProduct = [
                [
                    'STT',
                    'Mã Sản Phẩm/Dịch vụ',
                    'Tên Sản Phẩm/Dịch vụ',
                    'Giá bán/Phí Dịch vụ',
                    'Số lượng đặt hàng',
                    'Số lượng tồn kho',
                    'Doanh số',
                ],
            ];

            foreach ($products as $key => $product) {
                $orderProduct  = Order::model()
                    ->select([DB::raw("sum(od.qty) as qty"), DB::raw("sum(od.total) as total")])
                    ->join('order_details as od', 'od.order_id', 'orders.id')
                    ->where('product_id', $product['id'])
                    ->whereDate('orders.created_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
                    ->whereDate('orders.created_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
                    ->get()->toArray();
                $dataProduct[] = [
                    'stt'       => ++$key,
                    'code'      => $product['code'],
                    'name'      => $product['name'],
                    'price'     => $product['price'],
                    'order'     => $orderProduct[0]['qty'] ?? 0,
                    'inventory' => $product['qty'] - ($orderProduct[0]['qty'] ?? 0),
                    'turnover'  => $orderProduct[0]['total'] ?? 0,
                ];
            }
            $this->ExcelExport("ReportProduct_$date", storage_path('Export') . "/ReportProduct", $dataProduct);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportUser(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", "Date from"));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", "Date to"));
        }
        try {
            $users    = User::model()
                ->whereIn('type', [USER_TYPE_CUSTOMER, USER_TYPE_PARTNER])
                ->whereDate('created_at', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
                ->whereDate('created_at', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
                ->get();
            $date     = date('YmdHis', time());
            $dataUser = [
                [
                    'STT',
                    'Vai Trò',
                    'Tỉnh/Thành Phố',
                    'Mã Người Dùng',
                    'Email',
                    'Số Điện Thoại',
                    'Họ Và Tên',
                    'Họ Và Tên Lót',
                    'Tên',
                    'Ngày Sinh',
                    'Giới Tính',
                    'Trạng Thái',
                    'Xác Thực',
                    'Số Đơn',
                    'Số Thẻ',
                    'Mã Giới Thiệu',
                    'Tạo Lúc',
                    'Thời gian tạo đơn cuối cùng',
                    'Đánh giá khách hàng',
                    'Hạng Khách hàng',
                    'Điểm tích luỹ',
                ],
            ];

            foreach ($users as $key => $user) {
                $typeUser = $user['type'] == "USER" ? null : ($user['type'] == USER_TYPE_PARTNER ? 'partner' : 'customer');
                if (empty($typeUser)) {
                    return $this->response->errorBadRequest(Message::get("V002", "User"));
                }
                $countOrder = Order::model()
                    ->where('status', ORDER_STATUS_COMPLETED)
                    ->select([DB::raw("count(orders.id) as qty_orders"), 'created_date'])
                    ->where($typeUser . "_id", $user['id'])
                    ->orderBy('created_date')->get()->toArray();
                $orders     = Order::model()->where($typeUser . "_id", $user['id'])
                    ->whereNotNull($typeUser . "_star")
                    ->where($typeUser . "_star", '>', '0')
                    ->where($typeUser . "_star", '!=', '')
                    ->get()->toArray();
                if (!empty($orders)) {
                    $stars = [];
                    foreach ($orders as $order) {
                        $stars[] = $order[$typeUser . "_star"];
                    }
                    $avg = round(array_sum($stars) / count($stars), 1);
                }

                $dataUser[] = [
                    'stt'          => ++$key,
                    'role'         => $this->idToRoleName($user['role_id']),
                    'address'      => array_get($user, "profile.city.type") . " " . array_get($user, "profile.city.name"),
                    'user_code'    => $user['code'],
                    'email'        => array_get($user, "profile.email"),
                    'phone'        => $user['phone'],
                    'full_name'    => array_get($user, "profile.full_name"),
                    'last_name'    => array_get($user, "profile.last_name"),
                    'first_name'   => array_get($user, "profile.first_name"),
                    'birthday'     => !empty(array_get($user, "profile.birthday")) ? date('d-m-Y', strtotime(array_get($user, "profile.birthday"))) : null,
                    'gender'       => config('constants.STATUS.GENDER')[strtoupper(object_get($user, "profile.gender", 'O'))],
                    'status'       => STATUS_ACCOUNT_USER[$user['is_active']],
                    'verified'     => VERIFIED[array_get($user, "profile.personal_verified", 0)],
                    'qty_order'    => $countOrder[0]['qty_orders'] ?? 0,
                    'card'         => $user['card_ids'],
                    'ref_code'     => $user['ref_code'],
                    'created_at'   => date('d-m-Y', strtotime($user['created_at'])),
                    'created_date' => $countOrder[0]['created_date'],
                    'rating'       => $avg ?? 0,
                    'ranking'      => array_get($user, "membership.name"),
                    'point'        => $user['point'],
                ];
            }
            $this->ExcelExport("ReportUser_$date", storage_path('Export') . "/ReportUser", $dataUser);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportOrder(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", "Date from"));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", "Date to"));
        }
        $orders = Order::model()
            ->whereDate('orders.created_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
            ->whereDate('orders.created_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
            ->get();
        try {
            $date      = date('YmdHis', time());
            $dataOrder = [
                [
                    'STT',
                    'Tên Khách Hàng',
                    'Mã Đơn Hàng',
                    'Trạng Thái',
                    'Thời Gian Tạo',
                    'Số Lượng Sản Phẩm',
                    'Tổng Tiền',
                    'Phương Thức Thanh Toán',
                    'Mã KM',
                    'Khuyến Mãi',
                    'Người Nhận',
                    'Số Điện Thoại',
                    'Địa Chỉ',
                    'Phường/Xã',
                    'Quận/Huyện',
                    'Tỉnh/Thành Phố',
                    'Ghi Chú',
                    'Kho Hàng',
                    'Danh Mục Sản Phẩm',
                    'Thời Gian Hoàn Thành',
                    'Đối Tác Nhân Sự Thực Hiện',
                    'Đánh Giá Khách Hàng',
                ],
            ];
            $i         = 0;
            foreach ($orders as $key => $order) {
                $details = $order->details;
                foreach ($details as $detail) {
                    $dataOrder[] = [
                        'stt'              => ++$i,
                        'customer_name'    => array_get($order, "customer.profile.full_name"),
                        'code'             => $order->code,
                        'status'           => $order->status,
                        'created_date'     => date('d-m-Y H:i', strtotime($order->created_date)),
                        'qty_product'      => array_get($detail, "qty", null),
                        'total_price'      => $detail['qty'] * (!empty($detail['price_down']) ? $detail['price_down'] : $detail['price']),
                        'payment_method'   => $order->payment_method,
                        'coupon_code'      => $order->coupon_code,
                        'promotion_title'  => array_get($order, "promotionOrder.title"),
                        'receiver'         => array_get($order, "customer.profile.full_name"),
                        'phone'            => $order->phone,
                        'address'          => $order->shipping_address,
                        'wards'            => explode(",", $order->shipping_address)[1],
                        'districts'        => explode(",", $order->shipping_address)[2],
                        'cities'           => explode(",", $order->shipping_address)[3],
                        'note'             => $order->comment_for_customer,
                        'warehouse'        => array_get($detail, "product.storeProduct.name", null),
                        'category_product' => !empty(array_get($detail, "product.category_ids")) ? $this->getNameCategory(array_get($detail, "product.category_ids", null)) : null,
                        'complete_date'    => !empty($order->completed_date) ? date('d-m-Y H:i', strtotime($order->completed_date)) : null,
                        'partner_name'     => array_get($order, "partner.profile.full_name"),
                        'partner_star'     => $order->partner_star ?? 0,
                    ];
                }
            }
            $this->ExcelExport("ReportOrder_$date", storage_path('Export') . "/ReportOrder", $dataOrder);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    private function getNameCategory($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $category = Category::model()->select(['name'])->whereIn('id', explode(",", $ids))->get()->toArray();
        $category = array_pluck($category, 'name');
        $category = implode(', ', $category);
        return $category;
    }

    public function reportPartner(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", "Date from"));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", "Date to"));
        }
        $orders = Order::model()
            ->whereDate('orders.created_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
            ->whereDate('orders.created_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
            ->whereNotNull('partner_id')
            ->get();
        try {
            $date = date('YmdHis', time());
            $data = [
                [
                    'STT',
                    'Đối Tác Thực Hiện',
                    'Tên Công Ty/Tên Shop/Khách Hàng',
                    'Mã Đơn Hàng',
                    'Trạng Thái Thực Tế',
                    'Tổng Tiền',
                    'Thu Nhập',
                    'Thời Gian Nhận Đơn',
                    'Thời Gian Hoàn Thành',
                    'Đánh Gía Dịch Vụ',
                    'Điểm Đạt Được',
                ],
            ];
            foreach ($orders as $key => $order) {
                $details      = $order->details;
                $orderHistory = OrderHistory::model()
                    ->where('order_id', $order->id)
                    ->get()->toArray();
                if (!empty($details)) {
                    $total_price = $details->sum('total');
                }
                $data[] = [
                    'stt'            => ++$key,
                    'partner_id'     => array_get($order, "partner.profile.full_name"),
                    'customer_name'  => array_get($order, "customer.profile.full_name"),
                    'code'           => $order->code,
                    'status'         => $order->status,
                    'total'          => $total_price ?? 0,
                    'income'         => null,
                    'date_comming'   => !empty($orderHistory) ? date('d-m-Y H:i', strtotime($orderHistory[1]['created_at'])) : null,
                    'date_completed' => !empty($orderHistory) ? date('d-m-Y H:i', strtotime($orderHistory[3]['created_at'])) : null,
                    'rate'           => $order->partner_star ?? 0,
                    'point'          => $order->total_price > 0 ? round($order->total_price / 10000) : null,

                ];
            }
            $this->ExcelExport("ReportPartner_$date", storage_path('Export') . "/ReportPartner", $data);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    private function idToRoleName($role_id)
    {
        $role = Role::find($role_id);
        if (empty($role)) {
            return [];
        }
        return $role->name;
    }

    public function reportInventory(Request $request)
    {
        $input = $request->all();
        $date = date('YmdHis', time());
        try {
            $query = WarehouseDetail::with("product")
                ->where('company_id', TM::getCurrentCompanyId());
            if (!empty($input['product_id'])) {
                $query->where('product_id', $input['product_id']);
            }
            if (!empty($input['product_code'])) {
                $query->where('product_code', 'LIKE', "%{$input['product_code']}%");
            }
            if (!empty($input['product_name'])) {
                $query->where('product_name', 'LIKE', "%{$input['product_name']}%");
            }
            if (!empty($input['warehouse_id'])) {
                $query->where('warehouse_id', $input['warehouse_id']);
            }
            if (!empty($input['warehouse_code'])) {
                $query->where('warehouse_code', 'LIKE', "%{$input['warehouse_code']}%");
            }
            if (!empty($input['warehouse_name'])) {
                $query->where('warehouse_name', 'LIKE', "%{$input['warehouse_name']}%");
            }
            if (!empty($input['unit_id'])) {
                $query->where('unit_id', $input['unit_id']);
            }
            if (!empty($input['unit_code'])) {
                $query->where('unit_code', 'LIKE', "%{$input['unit_code']}%");
            }
            if (!empty($input['unit_name'])) {
                $query->where('unit_name', 'LIKE', "%{$input['unit_name']}%");
            }
            if (!empty($input['batch_id'])) {
                $query->where('batch_id', $input['batch_id']);
            }
            if (!empty($input['batch_code'])) {
                $query->where('batch_code', 'LIKE', "%{$input['batch_code']}%");
            }
            if (!empty($input['batch_name'])) {
                $query->where('batch_name', 'LIKE', "%{$input['batch_code']}%");
            }
            $result = $query->get();
            $data = [];
            foreach ($result as $key => $item) {
                $data[] = [
                    'key'            => ++$key,
                    'product_code'   => $item->product_code,
                    'product_name'   => $item->product_name,
                    'warehouse_name' => $item->warehouse_name,
                    'unit_name'      => $item->unit_name,
                    'batch_name'     => $item->batch_name,
                    'quantity'       => $item->quantity,
                    'price'          => $price = Arr::get($item, 'product.price', 0),
                    'total'          => $item->quantity * $price,
                    'note'           => null,
                ];
            }
            return \Excel::download(new ReportInventoryExport($data), 'Report_Inventory_' . $date . '.xlsx');

//            $this->ExcelExport("ReportInventory", storage_path('Export') . "/ReportInventory", $data);


        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportListReceiveDeliver(Request $request)
    {
        $input = $request->all();
        if (!isset($input['type'])) {
            return $this->response->errorBadRequest(Message::get("V009", "#Type"));
        }
        if (empty($input['from'])) {
            return $this->response->errorBadRequest(Message::get("V001", "Date From"));
        }
        if (empty($input['to'])) {
            return $this->response->errorBadRequest(Message::get("V001", "Date To"));
        }
        try {
            $type        = INVENTORY_CODE_PREFIX[$input['type']];
            $inventories = Inventory::model()
                ->select([
                    'inventories.code as code',
                    'inventories.transport as transport',
                    'inventories.description as description',
                    'inventories.date as date',
                    'inventories.status as status',
                    'inventory_details.quantity as quantity',
                    'inventory_details.price as price',
                    'inventory_details.exp as exp',
                    'profiles.full_name as full_name',
                    'products.code as product_code',
                    'products.name as product_name',
                    'warehouses.code as warehouses_code',
                    'batches.code as batch_code',
                    'units.name as unit_name',
                ])->join('inventory_details', 'inventory_details.inventory_id', '=', 'inventories.id')
                ->join('users', 'users.id', '=', 'inventories.user_id')
                ->join('profiles', 'profiles.user_id', '=', 'users.id')
                ->join('products', 'products.id', '=', 'inventory_details.product_id')
                ->join('warehouses', 'warehouses.id', '=', 'inventory_details.warehouse_id')
                ->join('units', 'units.id', '=', 'inventory_details.unit_id')
                ->join('batches', 'batches.id', '=', 'inventory_details.batch_id')
                ->where('inventories.type', $input['type'])
                ->where('inventories.company_id', TM::getCurrentCompanyId())
                ->whereNull('products.deleted_at')
                ->whereNull('warehouses.deleted_at')
                ->whereNull('units.deleted_at')
                ->whereDate('inventories.date', '>=', date("Y-m-d", strtotime($input['from'])))
                ->whereDate('inventories.date', '<=', date("Y-m-d", strtotime($input['to'])));

            if (!empty($input['warehouse_code'])) {
                $inventories->where('warehouses.code', 'LIKE', "%{$input['warehouse_code']}%");
            }
            $inventories = $inventories->get();
            $data        = [];
            $data[]      = [
                'STT',
                'Số chứng từ',
                'Ngày chứng từ',
                'Mã nhập kho',
                'Diễn giải',
                'Phương thức vận chuyển',
                'Trạng thái',
                'Người lập',
                'Mã sản phẩm',
                'Tên sản phẩm',
                'Mã lô',
                'SL',
                'Đơn giá',
                'ĐVT',
                'Hạn sử dụng',
            ];
            $i           = 0;
            foreach ($inventories as $inventory) {
                $data[] = [
                    'order'           => ++$i,
                    'code'            => $inventory->code,
                    'date'            => !empty($inventory->date) ? date('d-m-Y', strtotime($inventory->date)) : null,
                    'warehouses_code' => $inventory->warehouses_code,
                    'description'     => $inventory->description,
                    'transport'       => $inventory->transport,
                    'status'          => INVENTORY_STATUS_NAME[$inventory->status],
                    'full_name'       => $inventory->full_name,
                    'product_code'    => $inventory->product_code,
                    'product_name'    => $inventory->product_name,
                    'batch_code'      => $inventory->batch_code,
                    'quantity'        => $inventory->quantity,
                    'price'           => $inventory->price,
                    'unit_name'       => $inventory->unit_name,
                    'exp'             => !empty($inventory->exp) ? date('d-m-Y', strtotime($inventory->exp)) : null,
                ];
            }
            $input['dataTable'] = $data;
            $this->ExcelExport("ReportListReceiveDeliver_$type", storage_path('Export') . "/ReportListReceiveDeliver", $data);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportShippingOrder(Request $request)
    {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", Message::get('from')));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", Message::get('to')));
        }
        try {
            $query = ShippingOrder::with('details')
                ->where('company_id', TM::getCurrentCompanyId())
                ->whereBetween('created_at', [date('Y-m-d', strtotime($input['from'])), date('Y-m-d', strtotime($input['to']))]);
            if (!empty($input['product_code'])) {
                $query->whereHas('details', function ($q) use ($input) {
                    $q->where('product_code', $input['product_code']);
                });
            }
            if (!empty($input['product_name'])) {
                $query->whereHas('details', function ($q) use ($input) {
                    $q->where('product_name', $input['product_name']);
                });
            }
            $data        = $query->distinct()->get();
            $dataPrint   = [];
            $dataPrint[] = [
                'STT',
                'LOẠI',
                'MÃ ĐƠN HÀNG',
                'MÃ VẬN ĐƠN',
                'TRẠNG THÁI',
                'PHÍ GIAO HÀNG',
                'PHÍ ĐẶT HÀNG',
                'THỜI GIAN ĐẶT HÀNG',
                'THỜI GIAN GIAO',
                'GHI CHÚ',
                'MÃ SẢN PHẨM',
                'TÊN SẢN PHẨM',
                'ĐƠN VỊ TÍNH',
                'KHO',
                'LÔ',
                'SỐ LƯỢNG',
                'ĐƠN GIÁ',
                'CHIẾT KHẤU',
                'THÀNH TIỀN',
            ];
            $key         = 0;
            foreach ($data as $datum) {
                if (empty($datum->details)) {
                    continue;
                }
                foreach ($datum->details as $item) {
                    $dataPrint[] = [
                        'key'                    => ++$key,
                        'type'                   => Arr::get($datum, 'type', null),
                        'ship_code'              => Arr::get($datum, 'ship_code', null),
                        'code'                   => Arr::get($datum, 'code', null),
                        'status_text'            => Arr::get($datum, 'status_text', null),
                        'ship_fee'               => Arr::get($datum, 'ship_fee', null),
                        'pick_money'             => Arr::get($datum, 'pick_money', null),
                        'estimated_pick_time'    => Arr::get($datum, 'estimated_pick_time', null),
                        'estimated_deliver_time' => Arr::get($datum, 'estimated_deliver_time', null),
                        'description'            => Arr::get($datum, 'description', null),
                        'product_code'           => Arr::get($item, 'product_code', null),
                        'product_name'           => Arr::get($item, 'product_name', null),
                        'unit_name'              => Arr::get($item, 'unit_name', null),
                        'warehouse_name'         => Arr::get($item, 'warehouse_name', null),
                        'batch_name'             => Arr::get($item, 'batch_name', null),
                        'shipped_qty'            => Arr::get($item, 'shipped_qty', null),
                        'price'                  => Arr::get($item, 'price', null),
                        'discount'               => Arr::get($item, 'discount', null),
                        'total_price'            => Arr::get($item, 'total_price', null),
                    ];
                }
            }
            $this->ExcelExport("Report_Shipping_Order", storage_path('Export') . "/ReportShippingOrder", $dataPrint);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function exportPriceDetail($id, Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $date  = date('YmdHis', time());
        try {
            $price  = Price::model()->where('id', $id)->where('company_id', TM::getCurrentCompanyId())->first();
            $result = $price->details()->whereHas('product', function ($q) use ($input) {
                if (!empty($input['product_code'])) {
                    $q->where('code', 'like', "%{$input['product_code']}%");
                }
                if (!empty($input['product_name'])) {
                    $q->where('name', 'like', "%{$input['product_name']}%");
                }
            })->with('product.file')
                ->get();
            $data   = [];
            foreach ($result as $key => $item) {
//                $imageFile = null;
//                if (!empty($item->product->file)) {
//                    $imageFile = public_path('/uploads/'.$item->product->file->file_name);
//                    if (!file_exists($imageFile)) {
//                        $imageFile = null;
//                    } else {
//                        try {
//                            // Setup Glide server
//                            $server = \League\Glide\ServerFactory::create([
//                                'source' => public_path(),
//                                'cache'  => public_path('cache'),
//                            ]);
//                            $imageFile = $server->makeImage('cache/uploads/'.$item->product->file->file_name, array('w' => 100));
//                            $imageFile = public_path($imageFile);
//                        } catch (\Exception $exception) {
//                        }
//                    }
//                }
                $data [] = [
                    'stt'          => ++$key,
                    //                    'product_image'=> $imageFile,
                    'product_code' => Arr::get($item, 'product.code', null),
                    'product_name' => Arr::get($item, 'product.name', null),
                    'price_name'   => Arr::get($price, 'name', null),
                    'from'         => !empty($price->from) ? date("d-m-Y", strtotime($price->from)) : null,
                    'to'           => !empty($price->to) ? date("d-m-Y", strtotime($price->to)) : null,
                    'unit_name'    => Arr::get($item, 'product.unit.name', null),
                    'price'        => $item->price,
                ];
            }
            $input['data'] = $data;
            //ob_start(); // and this
            return Excel::download(new PriceDetailExport($data), 'export_price_detail_' . $date . '.xlsx');;
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportOrderDetail(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $date  = date('YmdHis', time());
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", Message::get('from')));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", Message::get('to')));
        }
        try {
            $orderDetail = OrderDetail::select(
                'p.name as product_name',
                'p.code as product_code',
                'u.name as unit_name',
                'order_details.qty as qty',
                'order_details.total as total',
                'orders.code as order_code',
                'pc.full_name as nameCustomer',
                'staff.code as staffCode',
                'ps.full_name as staffName',
                'orders.created_at as order_created_at',
                'pt.promotion_name as promotion',
                'c.group_name as group_name',
                'orders.is_freeship as is_freeship',
                'orders.ship_fee as ship_fee',
                'c.code as codeUser'
            )
                ->join('orders', 'orders.id', '=', 'order_details.order_id')
                ->join('products as p', 'p.id', '=', 'order_details.product_id')
                ->join('units as u', 'u.id', '=', 'p.unit_id')
                ->join('users as c', 'c.id', '=', 'orders.customer_id')
                ->join('profiles as pc', 'pc.user_id', '=', 'c.id')
                ->join('users as staff', 'staff.id', '=', 'orders.seller_id')
                ->join('profiles as ps', 'ps.user_id', '=', 'staff.id')
                ->leftjoin('promotion_totals as pt', 'pt.order_id', '=', 'orders.id')
                ->where('orders.store_id', TM::getCurrentStoreId())
                ->whereBetween('orders.created_at', [date('Y-m-d', strtotime($input['from'])), date('Y-m-d', strtotime($input['to']))]);
            if (!empty($input['created_by_code'])) {
                $orderDetail->where('staff.code', $input['created_by_code']);
            }
            if (!empty($input['customer_code'])) {
                $orderDetail->where('c.code', $input['customer_code']);       
            }
            $orderDetails = $orderDetail->get()->toArray();
            $data         = [];
            foreach ($orderDetails as $value) {
                $data[] = [
                    'product_name'     => $value['product_name'] ?? null,
                    'product_code'     => $value['product_code'] ?? null,
                    'unit_name'        => $value['unit_name'] ?? null,
                    'qty'              => $value['qty'] ?? null,
                    'total'            => $value['total'] ?? null,
                    'order_code'       => $value['order_code'] ?? null,
                    'nameCustomer'     => $value['nameCustomer'] ?? null,
                    'staffCode'        => $value['staffCode'] ?? null,
                    'staffName'        => $value['staffName'] ?? null,
                    'order_created_at' => $value['order_created_at'] ?? null,
                    'promotion'        => $value['promotion'] ?? null,
                    'group_name'       => $value['group_name'] ?? null,
                    'ship_fee_user'    => $value['is_freeship'] == 0 ?  $value['ship_fee'] : 0,
                    'ship_fee_shop'    => $value['is_freeship'] == 1 ?  $value['ship_fee'] : 0,
                    'ship_fee'         => $value['ship_fee'] ?? null,
                    'codeUser'         => $value['codeUser'] ?? null,
                ];
            }
            //ob_start(); // and this
            return Excel::download(new OrderDetailExport($data, $input['from'], $input['to']), 'order_detail_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportSaleTotalByCustomer(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $date  = date('YmdHis', time());
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", Message::get('from')));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", Message::get('to')));
        }
        try {
            $order = Order::model()
                ->select(['profiles.full_name as customerName', 'users.phone as phone','orders.customer_code as customerCode', DB::raw("SUM(order_details.qty) as qty"), DB::raw("SUM(order_details.total) as total")])
                ->join('order_details', 'order_details.order_id', '=', 'orders.id')
                ->join('users', 'users.id', '=', 'orders.customer_id')
                ->join('profiles', 'profiles.user_id', '=', 'users.id')
                ->whereBetween('orders.created_at', [date('Y-m-d', strtotime($input['from'])), date('Y-m-d', strtotime($input['to']))]);
            if (!empty($input['customer_code'])) {
                $order->where('users.code', $input['customer_code']);
            }
            $orders = $order->where('orders.store_id', TM::getCurrentStoreId())
                ->groupBy('orders.customer_id')
                ->get()->toArray();
            //ob_start(); // and this
            return Excel::download(new SaleTotalByCustomerExport($orders, $input['from'], $input['to']), 'sale_total_by_customer_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportSaleTotalByMonth(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $date  = date('YmdHis', time());
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", Message::get('from')));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", Message::get('to')));
        }

        try {
            $orders = Order::model()
                ->select([DB::raw('count(id) as total_order'), DB::raw("SUM(total_price) as total_price")])
                ->selectRaw("MONTH(updated_at) as month")
                ->where('store_id', TM::getCurrentStoreId())
                ->whereBetween('updated_at', [date('Y-m-d', strtotime($input['from'])), date('Y-m-d', strtotime($input['to']))])
                ->groupBy('month')
                ->get()->toArray();
            //ob_start(); // and this
            return Excel::download(new SaleTotalByMonthExport($orders, $input['from'], $input['to']), 'sale_total_by_month_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportSaleByProduct(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $date  = date('YmdHis', time());
        if (empty($input['from'])) {
            return $this->responseError(Message::get("V001", Message::get('from')));
        }
        if (empty($input['to'])) {
            return $this->responseError(Message::get("V001", Message::get('to')));
        }

        try {
            $orders = OrderDetail::model()
                ->select([
                    'order_details.product_id as product_ids',
                    'p.name as product_name',
                    'p.code as product_code',
                    'u.name as unit_name',
                    DB::raw("SUM(order_details.price * order_details.qty) as total_price"),
                    DB::raw("SUM(order_details.qty) as total_qty"),
                    DB::raw('count(order_details.order_id) as total_orders'),
                ])
                ->join('orders as o', 'o.id', '=', 'order_details.order_id')
                ->join('products as p', 'p.id', '=', 'order_details.product_id')
                ->join('units as u', 'u.id', '=', 'p.unit_id')
                ->whereBetween('o.updated_at', [date('Y-m-d', strtotime($input['from'])), date('Y-m-d', strtotime($input['to']))])
                ->groupBy('product_ids')
                ->where('o.store_id', TM::getCurrentStoreId())
                ->where(function ($q) use ($input) {
                    if (!empty($input['product_id'])) {
                        $q->where('order_details.product_id', $input['product_id']);
                    }
                    if (!empty($input['category_id'])) {
                        $q->orWhere(DB::raw("CONCAT(',',p.category_ids,',')"), 'like', "%,{$input['category_id']},%");
                    }
                })->get()->toArray();
            //ob_start(); // and this
            return Excel::download(new SaleByProductExport($orders, $input['from'], $input['to']), 'sale_by_product_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function reportCustomerPoint(Request $request)
    {
        //ob_end_clean();
        $input     = $request->all();
        $date      = date('YmdHis', time());
        if (empty($input['group_code'])) {
            if (empty($input['user_code'])) {
                return $this->responseError(Message::get("V001", Message::get('group_code_and_user_code')));
            }
        }

        if (!empty($input['group_code'])) {
            $checkGroupCode = UserGroup::model()->where('code', $input['group_code'])->where('company_id', TM::getCurrentCompanyId())->first();
            if (empty($checkGroupCode)) {
                return $this->responseError(Message::get("V002", Message::get('group_code')));
            }
        }
        if (!empty($input['user_code'])) {
            $checkUserCode = User::model()->where('code', $input['user_code'])->where('store_id', TM::getCurrentStoreId())->first();
            if (empty($checkUserCode)) {
                return $this->responseError(Message::get("V002", Message::get('user_code')));
            }
        }
        $groupName = $checkGroupCode->name ?? null;
        $userCode = $checkUserCode->profile->full_name ?? null;

        try {
            $customers = User::model()
                ->select(['r.name as rankName', 'users.id as userId', 'users.code as userCode', 'p.full_name as userName', DB::raw('SUM(o.customer_point) as customerPoint')])
                ->join('profiles as p', 'p.user_id', '=', 'users.id')
                ->leftJoin('orders as o', 'o.customer_id', '=', 'users.id')
                ->leftJoin('membership_ranks as r', 'r.id', '=', 'users.ranking_id')
                ->where('users.store_id', TM::getCurrentStoreId())
                ->where('users.company_id', TM::getCurrentCompanyId());
            if (!empty($input['group_code'])) {
                $customers = $customers->join('user_groups', 'user_groups.id', '=', 'users.group_id');
            }

            $results = $customers->where(function ($q) use ($input) {
                if (!empty($input['group_code'])) {
                    $q->where("user_groups.code", $input['group_code']);
                }
                if (!empty($input['user_code'])) {
                    $q->orWhere('users.code', $input['user_code']);
                }
            });
            $data = $results->groupBy('userId')->get()->toArray();
            //ob_start(); // and this
            return Excel::download(new CustomerPointExport($data, $userCode, $groupName), 'report_customer_point_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function printOrderBill($id, Request $request)
    {
        $input['customers_pay'] = $request->input('customers_pay');
        if (empty($input['customers_pay'])) {
            return $this->responseError(Message::get("V001", Message::get('customers_pay')));
        }
        $date = date('YmdHis', time());
        try {
            $orders = Order::with(['store', 'customer', 'details.product'])->where('orders.id', $id)->get();
            $input['orders'] = $orders;
            return \Excel::download(new PrintOrderBillExport($input), 'Print_order_bill_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            throw $ex;
//            return $this->response->errorBadRequest($response["message"]);
        }
    }
}