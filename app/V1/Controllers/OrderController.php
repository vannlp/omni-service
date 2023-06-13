<?php

/**
 * User: Administrator
 * Date: 21/12/2018
 * Time: 09:31 PM
 */

namespace App\V1\Controllers;

use App\District;
use App\Exports\ExportOrders2;
use App\Exports\OrderListExport;
use App\Jobs\SendMailCancelRequestJob;
use App\LogShippingOrder;
use App\OrderStatusHistory;
use App\Role;
use App\Permission;
use App\RolePermission;
use App\ShippingAddress;
use App\ShippingHistoryStatus;
use App\V1\Library\CDP;
use App\V1\Library\GRAB;
use App\V1\Library\OrderSyncDMS;
use App\V1\Library\VNP;
use App\V1\Library\VTP;
use App\V1\Library\Accesstrade;
use App\V1\Models\ShippingOrderModel;
use Dingo\Api\Http\Response;
use GuzzleHttp\Client;
use App\Setting;
use App\Session;
use App\UserCompany;
use App\UserStore;
use App\V1\Transformers\Order\OrderListTransformer;
use App\Cart;
use App\Category;
use App\City;
use App\Company;
use App\ConfigShipping;
use App\Coupon;
use App\CouponCodes;
use App\CouponHistory;
use App\CustomerInformation;
use App\Exports\ExportOrders;
use App\Jobs\SendCustomerMailNewOrderJob;
use App\Jobs\SendCustomerMailUpdateOrderStatusJob;
use App\Jobs\SendHUBMailNewOrderJob;
use App\Jobs\SendMailOrderApprovedCanceledJob;
use App\Jobs\SendStoreMailNewOrderJob;
use App\Jobs\SendStoreMailUpdateOrderStatusJob;
use App\MembershipRank;
use App\Notify;
use App\Order;
use App\OrderDetail;
use App\OrderHistory;
use App\OrderStatus;
use App\Product;
use App\Profile;
use App\PromotionProgram;
use App\PromotionTotal;
use App\Store;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Supports\TM_PDF;
use App\TM;
use App\User;
use App\UserGroup;
use App\UserSession;
use App\UserStatusOrder;
use App\V1\Models\OrderHistoryModel;
use App\V1\Models\PaymentHistoryModel;
use App\V1\Models\ProductModel;
use App\V1\Models\PromotionTotalModel;
use App\V1\Models\UserModel;
use App\V1\Models\WalletHistoryModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Transformers\Order\OrderDetailTransformer;
use App\V1\Transformers\Order\OrderProductPurchasedTransformer;
use App\V1\Transformers\Order\OrderTransformer;
use App\V1\Models\OrderModel;
use App\V1\Transformers\Order\UserHUBTransformer;
use App\V1\Transformers\Product\ProductClientTransformer;
use App\V1\Validators\Order\OrderAssignEnterprisesValidator;
use App\V1\Validators\OrderAdminUpdateValidator;
use App\V1\Validators\OrderCreateValidator;
use App\V1\Validators\OrderUpdateStatusValidator;
use App\V1\Validators\OrderUpdateValidator;
use App\V1\Validators\UpdateStatusItemInOrderValidator;
use App\Wallet;
use App\Ward;
use Carbon\Carbon;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Exports\OrderExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\OrderByPromotionExport;
use App\NotificationHistory;
use App\PermissionRP;
use App\RolePermissionRP;
use App\Supports\DataUser;
use App\UserRP;
use App\V1\Validators\Order\AdminConfirmOrderValidator;
use App\WarehouseDetail;
use Google\Service\Spanner\GetDatabaseDdlResponse;
class OrderController extends BaseController
{
    use ControllerTrait;

    /**
     * @var OrderModel
     */
    protected $model;
    protected $shippingType;

    /**
     * OrderController constructor.
     */
    public function __construct()
    {
        /** @var OrderModel model */
        $this->model        = new OrderModel();
        $this->shippingType = new ShippingOrderModel();
    }

    public function search(Request $request, OrderTransformer $orderTransformer)
    {
        $input = $request->all();

        if ($request->is('*orders/hub')) {
            $input['hub_id'] = TM::getCurrentUserId();
        }
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $input['store_id']   = TM::getCurrentStoreId();
        $order               = $this->model->search($input, [
            'partner',
            'customer',
            'details',
            'promotionTotals',
            'statusHistories.createdBy',
        ], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($order, $orderTransformer);
    }

    public function listOrder(Request $request, OrderListTransformer $orderListTransformer)
    {
        $input = $request->all();

        if ($request->is('*orders/hub')) {
            $input['hub_id'] = TM::getCurrentUserId();
        }
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $input['store_id']   = TM::getCurrentStoreId();
        //        if (TM::getCurrentRole() == USER_ROLE_SHIPPER) {
        //            $distributorId = DistributorHasShipper::model()->where('shipper_id', TM::getCurrentUserId())->first();
        //            if (empty($distributorId)) {
        //                return ['data' => []];
        //            }
        //            $input['distributoId'] = $distributorId->distributor_id;
        //        }
        $order = $this->model->search($input, [
            'partner',
            'distributor',
            'getStatus',
            'customer',
            'details',
            'promotionTotals',
            'statusHistories.createdBy',
        ], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($order, $orderListTransformer);
    }


    public function listMyOrder(Request $request, OrderTransformer $orderTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $order = $this->model->searchMyOrder(
            $input,
            [
                'hub',
                'partner',
                'distributor',
                'customer:id,code,name,email,phone',
                'details',
                'details.product:id,code,name,slug,price_down,down_to,unit_id,thumbnail,down_from,down_to',
                'details.product.getUnit:id,code,name',
                'details.product.file:id,code',
                'promotionTotals',
                'statusHistories',
                'shippingStatusHistories',
                'getShippingInfoByPhone',
            ],
            $limit
        );
        Log::view($this->model->getTable());
        return $this->response->paginator($order, $orderTransformer);
    }

    public function detail($id, OrderTransformer $orderTransformer)
    {
        $order = Order::model()->select('id')->where('id', $id)->first();
        if (empty($order)) {
            return ['data' => []];
        }
        $order = Order::model()->with([
            'customer',
            'store',
            'details.product.unit',
            'statusHistories.createdBy',
        ])->where('id', $order->id)->first();
        Log::view($this->model->getTable());
        return $this->response->item($order, $orderTransformer);
    }

    public function getClientOrder($id, OrderTransformer $orderTransformer)
    {
        
        $result = Str::contains($id, '-');
        if ($result) {
            $idEx = explode('-', $id);
            $id = end($idEx);
        }
        $order = Order::model()->select('id')->where('id', (int)$id)->where('customer_id', TM::getCurrentUserId())->first();
        if (empty($order)) {
            return ['data' => []];
        }
        $company = Company::model()->where('id', TM::getCurrentCompanyId())->first();
        $order   = Order::model()->with([
            'customer',
            'store',
            'details.product.unit',
            'statusHistories.createdBy',
        ])->where('id', $order->id)->first();
        Log::view($this->model->getTable());
        return $this->response->item($order, $orderTransformer);
    }

    public function getMyOrder($id, OrderTransformer $orderTransformer)
    {
        $order = Order::model()->with([
            'statusHistories.createdBy',
        ])->where('id', $id)->first();

        if (empty($order) || $order->request_assign_to != TM::getCurrentUserId()) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($order, $orderTransformer);
    }

    /**
     * Get products
     *
     * @param Request $request
     * @return \Dingo\Api\Http\Response|void
     */
    public function getProducts(Request $request)
    {
        $input             = $request->all();
        $input['store_id'] = TM::getCurrentStoreId();

        $customer = User::select('group_id', 'area_id',)->where([
            'id'         => $request->input('customer_id'),
            'company_id' => TM::getCurrentCompanyId(),
        ])->first();

        if (empty($customer)) {
            return $this->responseError(Message::get("V002", Message::get("customer_id")));
        }

        $group = UserGroup::select('id')->where('id', $customer->group_id)->first();

        if (!empty($group)) {
            if ($group->is_view) {
                $input['area_id'] = $customer->area_id;
            }
            $input['group_id'] = $customer->group_id;
        } else {
            $group = UserGroup::where(['company_id' => TM::getCurrentCompanyId()])->where('is_default', 1)->first();
            if (!empty($group)) {
                $input['group_id'] = $group->id;
            }
        }
        $limit = array_get($input, 'limit', 20);

        $request->merge($input);

        $products = (new ProductModel())->searchClient($input, [
            'brand:id,name',
            'area:id,name',
            'stores:id,name',
            'storeOrigin:id,name',
            'unit:id,name',
        ], $limit);

        $promotionProgram = (new PromotionProgram())->getPromotionProgram(TM::getCurrentCompanyId());

        return $this->response->paginator($products, new ProductClientTransformer($promotionProgram));
    }

    public function getUserHUB(Request $request)
    {
        $roleCurrentGroup = TM::getCurrentRoleGroup();
        $users            = User::whereHas('group', function ($query) use ($roleCurrentGroup) {
            if ($roleCurrentGroup == USER_ROLE_GROUP_ADMIN) {
                $query->whereIn('user_groups.code', [USER_GROUP_DISTRIBUTOR, USER_GROUP_HUB, USER_GROUP_DISTRIBUTOR_CENTER]);
            }
        })
            ->where(function ($q) use ($roleCurrentGroup) {
                if ($roleCurrentGroup != USER_ROLE_GROUP_ADMIN) {
                    $groupCode = TM::info()['group_code'];
                    if (!empty($groupCode)) {
                        switch ($groupCode) {
                            case USER_GROUP_DISTRIBUTOR_CENTER:
                                $q->where('code', TM::info()['code']);
                                $q->orWhere('distributor_center_code', TM::info()['code']);
                                break;
                            default:
                                $q->where('code', TM::info()['code']);
                        }
                    }
                }
            })
            ->where('store_id', TM::getCurrentStoreId())
            ->where('company_id', TM::getCurrentCompanyId())
            ->where('is_active', 1)
            ->where('account_status', 'approved')
            ->whereNull('deleted_at')
            ->where(function ($query) use ($request) {
                if (!empty($request->input('name'))) {
                    $query->where('name', 'like', '%' . $request->input('name') . '%');
                }

                if (!empty($request->input('phone'))) {
                    $query->where('phone', 'like', '%' . $request->input('phone') . '%');
                }
            })
            ->paginate($request->input('limit', 20));

        return $this->response->paginator($users, new UserHUBTransformer());
    }

    /**
     * Update status order by hub
     *
     * @param $id
     * @param Request $request
     * @return array
     */
    public function updateStatusOrderByHUB($id, Request $request)
    {
        $input       = $request->all();
        $input['id'] = $id;
        (new OrderUpdateStatusValidator())->validate($input);
        try {
            DB::beginTransaction();
            $order       = Order::model()->where('id', $id)->select(
                'id',
                'status',
                'status_text',
                'code',
                'customer_point',
                'customer_id',
                'distributor_deny_ids',
                'distributor_id',
                'distributor_code',
                'distributor_name',
                'distributor_email',
                'canceled_date',
                'canceled_by',
                'canceled_reason',
                'shipping_address_city_code',
                'shipping_address_district_code',
                'shipping_address_ward_code'
            )->first();
            $orderStatus = OrderStatus::model()->where([
                'company_id' => TM::getCurrentCompanyId(),
                'code'       => $input['status'],
            ])->select('name')->first();
            if (empty($orderStatus)) {
                return $this->response->errorBadRequest(Message::get("V002", "status"));
            }
            $status = $input['status'];
            switch ($status) {
                case ORDER_STATUS_APPROVED:
                    $order->status      = $status;
                    $order->status_text = $orderStatus->name ?? null;
                    $email              = Arr::get($order, 'customer.email');
                    $data               = [
                        'to'         => $email,
                        'order_code' => $order->code,
                        'name'       => Arr::get($order, 'customer.profile.full_name'),
                        'status'     => "ĐÃ ĐƯỢC DUYỆT",
                        'msg'        => "Đơn hàng [$order->code] của bạn đã được phê duyệt.",
                    ];
                    if (!empty($email) && env('SEND_EMAIL', 0) == 1) {
                        dispatch(new SendMailOrderApprovedCanceledJob($data));
                    }
                    break;
                case ORDER_STATUS_CANCELED:
                    $order->customer_point = null;

                    // Find New Distributor
                    $deniedUsers   = explode(",", $order->distributor_deny_ids);
                    $deniedUsers[] = $order->distributor_id;
                    $deniedUsers   = array_filter(array_unique($deniedUsers));
                    if (count($deniedUsers) >= 2) {
                        $order->distributor_id    = null;
                        $order->distributor_code  = null;
                        $order->distributor_name  = null;
                        $order->distributor_email = null;
                        $order->status            = ORDER_STATUS_CANCELED;
                        $order->status_text       = $orderStatus->name;
                        $order->canceled_date     = date("Y-m-d H:i:s", time());
                        $order->canceled_by       = TM::getCurrentUserId();
                        $order->canceled_reason   = "Nhà phân phối từ chối 2 lần";
                        break;
                    }
                    $userModel   = new UserModel();
                    $distributor = $userModel->findDistributor2(
                        $order->customer_id,
                        $order->shipping_address_city_code,
                        $order->shipping_address_district_code,
                        $order->shipping_address_ward_code,
                        $deniedUsers
                    );

                    if (!empty($distributor['email']) && env('SEND_EMAIL', 0) == 1) {
                        $company = Company::model()->where('id', TM::getCurrentCompanyId())->select('avatar', 'email', 'name')->first();
                        $this->dispatch(new SendHUBMailNewOrderJob($distributor['email'], [
                            'logo'         => $company->avatar,
                            'support'      => $company->email,
                            'company_name' => $company->name,
                            'order'        => $order,
                            'link_to'      => TM::urlBase("/user/order/" . $order->id),
                        ]));
                    }
                    $order->distributor_deny_ids = implode(",", $deniedUsers);
                    $order->distributor_id       = $distributor['id'] ?? null;
                    $order->distributor_code     = $distributor['code'] ?? null;
                    $order->distributor_name     = $distributor['name'] ?? null;
                    $order->distributor_email    = $distributor['email'] ?? null;
                    break;
                default:
                    $order->status      = $status;
                    $order->status_text = $orderStatus->name ?? null;
            }

            $order->save();

            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("orders.update-success", $order->code)];
    }

    public function create(
        Request $request,
        OrderCreateValidator $orderCreateValidator,
        OrderTransformer $orderTransformer
    )
    {
        $input = $request->all();
        //        $input['shipping_address'] = !empty($input['shipping_address']) ? $input['shipping_address'] : $input['street_address'];
        $orderCreateValidator->validate($input);
        try {
            DB::beginTransaction();
            $input['code'] = $this->getAutoOrderCode();
            $order         = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $order->id . "-" . $order->code);

            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);
            DB::commit();
            $order = Order::model()->with([
                'customer',
                'store',
                'partner',
                'details.product.unit',
                'details.product',
                'approverUser',
            ])->where('id', $order->id)->first();

            // Send Email
            if (env('SEND_EMAIL', 0) == 1) {
                $company = Company::model()->where('id', TM::getCurrentCompanyId())->select('avatar', 'email', 'name')->first();
                dispatch(new SendCustomerMailNewOrderJob($order->customer->email, [
                    'logo'         => $company->avatar,
                    'support'      => $company->email,
                    'company_name' => $company->name,
                    'order'        => $order,
                ]));
                dispatch(new SendStoreMailNewOrderJob($order->store->email_notify, [
                    'logo'         => $company->avatar,
                    'support'      => $company->email,
                    'company_name' => $company->name,
                    'order'        => $order,
                ]));
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($order, $orderTransformer);
    }

    public function createV2(
        Request $request) 
    {
        $input = $request->all();

        try {
            DB::beginTransaction();
            
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function update(
        $id,
        Request $request,
        OrderUpdateValidator $orderUpdateValidator,
        OrderTransformer $orderTransformer
    )
    {
        $input       = $request->all();
        $input['id'] = $id;
        $orderUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $order = Order::model()
                ->where('id', $id)
                ->select('status', 'id', 'code', 'access_trade_id', 'conversion_id', 'customer_id', 'customer_point', 'distributor_id', 'status_crm', 'completed_date')
                ->first();
            if (empty($order)) {
                return $this->response->errorBadRequest(Message::get("orders.not-exist", "#$id"));
            }
            // Create History
            if ($order->status != $input['status']) {
                OrderHistory::insert([
                    'order_id'   => $order->id,
                    'status'     => $input['status'],
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'created_by' => TM::getCurrentUserId(),
                ]);
            }
            if ($order->status != $input['status']) {
                ShippingHistoryStatus::insert([
                    'shipping_id'      => $order->code,
                    'status_code'      => $input['status'],
                    'text_status_code' => ORDER_STATUS_NAME[$input['status']],
                    'created_at'       => date("Y-m-d H:i:s", time()),
                    'created_by'       => TM::getCurrentUserId(),
                ]);
            }
            $order = $this->model->upsert($input);
            #UPDATE[ACCESSTRADE]
            if (!empty($input['crm_check']) && $input['crm_check'] == 'confirmCOD') {
                if (!empty($order->access_trade_id)) {
                    $status = ORDER_STATUS_APPROVED;
                    $reason = ORDER_STATUS_NEW_NAME['APPROVED'];
                    Accesstrade::update($order, $status, $reason);
                }
                if ($order->status == "NEW") {
                    try {
                        $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
                        if (!empty($syncDMS)) {
                            $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                            if (!empty($pushOrderDms['errors'])) {
                                foreach ($pushOrderDms['errors'] as $item) {
                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                                }
                            } else {
                                if (!empty($pushOrderDms)) {
                                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                                }
                                if (empty($pushOrderDms)) {
                                    Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                                }
                            }
                        }
                        Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
                    } catch (\Exception $exception) {
                        Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
                    }
                }
            }
            if ($order->status == ORDER_STATUS_CANCELED) {
                $order->customer_point = null;
                $order->save();
                #UPDATE[ACCESSTRADE]
                if (!empty($order->access_trade_id)) {
                    $status = ORDER_STATUS_REJECTED;
                    $reason = ORDER_STATUS_NEW_NAME['CANCELED'];
                    Accesstrade::update($order, $status, $reason);
                }
                //Push Status Order Cancel
//                try {
//                    $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
//                    $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
//                    if (!empty($dataUpdateDMS)) {
//                        $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
//                        if (!empty($pushOrderStatusDms['errors'])) {
//                            foreach ($pushOrderStatusDms['errors'] as $item) {
//                                Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
//                            }
//                        } else {
//                            if (!empty($pushOrderStatusDms)) {
//                                Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
//                            }
//                            if (empty($pushOrderStatusDms)) {
//                                Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
//                            }
//                        }
//
//                    }
//                    Order::where('code', $order->code)->update(['log_status_order_dms' => json_encode($dataUpdateDMS) ?? []]);
//                } catch (\Exception $exception) {
//                    Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
//                }
            }
            $distributor = User::model()->where('id', $order->distributor_id)->select('email')->first();
            $company     = Company::model()->where('id', TM::getCurrentCompanyId())->select('avatar', 'email', 'name')->first();
            if ($order->status_crm == "APPROVED") {
                $time                 = date('Y-m-d H:i:s', time());
                $order2               = Order::with(['customer', 'distributor', 'store', 'details.product.unit'])->where('id', $order->id)->first();
                $order2['number_day'] = round((strtotime($time) - strtotime($order2->created_at)) / (60 * 60 * 24));
                $order2['number_h']   = round((strtotime($time) - strtotime($order2->created_at)) / (60 * 60));
                if (!empty($distributor)) {
                    $this->dispatch(new SendHUBMailNewOrderJob($distributor->email, [
                        'logo'         => $company->avatar,
                        'support'      => $company->email,
                        'company_name' => $company->name,
                        'order'        => $order2,
                        'order_id'     => $order2->code,
                        'link_to'      => TM::urlBase("/user/order/" . $order2->id),
                    ]));
                }
            }
            if ($order->status == ORDER_STATUS_COMPLETED) {
                $order->completed_date = date("Y-m-d H:i:s", time());
                $order->save();
                $user        = User::findOrFail($order->customer_id);
                $user->point += $order->customer_point;
                //                $ranking            = MembershipRank::model()
                //                    ->where('point', '=<', $user->point)
                //                    ->where('company_id', TM::getCurrentCompanyId())
                //                    ->orderBy('point')
                //                    ->first();
                //                $user->ranking_id   = $ranking->id;
                //                $user->ranking_code = $ranking->code;
                $user->save();
                try {
                    $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
                    $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
                    if (!empty($dataUpdateDMS)) {
                        $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                        if (!empty($pushOrderStatusDms['errors'])) {
                            foreach ($pushOrderStatusDms['errors'] as $item) {
                                Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                            }
                        } else {
                            if (!empty($pushOrderStatusDms)) {
                                Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                            }
                            if (empty($pushOrderStatusDms)) {
                                Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                            }
//                            Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                        }

                    }
                    Order::where('code', $order->code)->update(['log_status_order_dms' => json_encode($dataUpdateDMS) ?? []]);
                } catch (\Exception $exception) {
                    Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "CREATE-ORDER", 0, null);
                }

                #CDP
                CDP::pushOrderCdp($order,'update - OrderController - line:515');
            }
            Log::update($this->model->getTable(), "#ID:" . $order->id . "-" . $order->code, null, $order->code);

            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);
            // Notify
            $this->sendNotifyUpdateStatus($order);
            /// Sync dms
            /// END Sync dms
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        try {
            // if (!empty($input['hub_id']) && $originOrder->hub_id != $input['hub_id']) {
            //     if(env('SEND_EMAIL', 0) == 1){
            //         $company = Company::model()->where('id', TM::getCurrentCompanyId())->first();
            //     $this->dispatch(new SendHUBMailNewOrderJob($order->hub->email, [
            //         'logo'         => $company->avatar,
            //         'support'      => $company->email,
            //         'company_name' => $company->name,
            //         'order'        => $order,
            //     ]));
            //     }
            // }
        } catch (\Exception $exception) {
        }

        return ['status' => Message::get("orders.update-success", $order->code), 'data' => $order];
    }

    public function updateCompleteOrder(Request $request)
    {
        $input      = $request->all();
        $order_code = [];
        try {
            DB::beginTransaction();
            $orderIds = explode(",", $input['order_code']);
            $orders   = Order::model()->whereIn("code", $orderIds)->where('company_id', TM::getCurrentCompanyId())
                ->select('code', 'id', 'status', 'customer_id')->get();
            if (count($orders) == 0) {
                return $this->response->errorBadRequest(Message::get("V003", Message::get("orders")));
            }
            foreach ($orders as $order) {
                if ($order->status != ORDER_STATUS_SHIPPED) {
                    return $this->responseError(Message::get('V002', Message::get('status') . " [$order->code]"));
                }
                OrderHistory::insert([
                    'order_id'   => $order->id,
                    'status'     => ORDER_STATUS_COMPLETED,
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'created_by' => TM::getCurrentUserId(),
                ]);
                ShippingHistoryStatus::insert([
                    'shipping_id'      => $order->code,
                    'status_code'      => ORDER_STATUS_COMPLETED,
                    'text_status_code' => ORDER_STATUS_NAME[ORDER_STATUS_COMPLETED],
                    'created_at'       => date("Y-m-d H:i:s", time()),
                    'created_by'       => TM::getCurrentUserId(),
                ]);
                $orderModel = $this->model->updateMany($order->id);
                if ($orderModel->status == ORDER_STATUS_COMPLETED) {
                    $orderModel->payment_status = 1;
                    $orderModel->completed_date = date("Y-m-d H:i:s", time());
                    $orderModel->save();

                    try {
                        $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
                        $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($orderModel->code), "C", ORDER_STATUS_COMPLETED);
                        if (!empty($dataUpdateDMS)) {
                            $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                            if (!empty($pushOrderStatusDms['errors'])) {
                                foreach ($pushOrderStatusDms['errors'] as $item) {
                                    Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                                }
                            } else {
                                if (!empty($pushOrderStatusDms)) {
                                    Log::logSyncDMS($orderModel->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                                }
                                if (empty($pushOrderStatusDms)) {
                                    Log::logSyncDMS($orderModel->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                                }
//                            Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                            }

                        }
                        Order::where('code', $orderModel->code)->update(['log_status_order_dms' => json_encode($dataUpdateDMS) ?? []]);
                    } catch (\Exception $exception) {
                        Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "CREATE-ORDER", 0, null);
                    }

                    #CDP
                    CDP::pushOrderCdp($order,'updateCompleteOrder - OrderController - line: 710');

                    $user        = User::findOrFail($orderModel->customer_id);
                    $user->point += $orderModel->customer_point;
                    $user->save();
                }
                Log::update($this->model->getTable(), "#ID:" . $orderModel->id . "-" . $orderModel->code, null, $orderModel->code);

                // Update Order Status History
                $this->model->updateOrderStatusHistory($orderModel);
                // Notify
                $this->sendNotifyUpdateStatus($orderModel);
                $order_code[] = $orderModel->code;
                #UPDATE[ACCESSTRADE]
                // try {
                //     if (!empty($order->access_trade_id)) {
                //         $status         = ORDER_STATUS_APPROVED;
                //         $reason         = ORDER_STATUS_NEW_NAME['APPROVED'];
                //         Accesstrade::update($order, $status, $reason);
                //     }
                // } catch (\Exception $e) {
                // }
            }
            $order_code = implode(',', $order_code);
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
        return ['status' => Message::get("orders.update-success", $order_code)];
    }

    public function updateAdminCRM(Request $request)
    {
        $input    = $request->all();
        $orderIds = explode(",", $input['order_code']);
        $orders   = Order::model()->whereIn("code", $orderIds)->where('company_id', TM::getCurrentCompanyId())
            ->select('id', 'code', 'status', 'status_crm')->get();
        if (count($orders) == 0) {
            return $this->response->errorBadRequest(Message::get("V003", Message::get("orders")));
        }
        DB::beginTransaction();
        try {
            $order_code = [];
            foreach ($orders as $order) {
                if ($order->status != ORDER_STATUS_NEW || $order->status_crm != ORDER_STATUS_CRM_APPROVED) {
                    return $this->responseError(Message::get('V002', Message::get('status') . " [$order->code]"));
                }
                //                if($order->distributor->group_code != USER_GROUP_HUB){
                //                    return $this->response->errorBadRequest(Message::get('orders.not-distributor',$order->code));
                //                }
                $order_code[] = $order->code;
                $order = $this->model->updateMany($order->id);
                #CDP
                try {
                    CDP::pushOrderCdp($order, 'updateAdminCRM - OrderController - line:803');
                } catch (\Exception $e) {
                }
            }
            $order_code = implode(',', $order_code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            return $ex->getMessage();
        }
        return ['status' => Message::get("orders.update-success", $order_code)];
    }

    public function updateNotDetailByCode(
        $code,
        Request $request
    )
    {
        $strCode            = $code;
        $Arrcode            = explode(',', $code);
        $input              = $request->all();
        $orderPaymentMethod = Order::whereIn('code', $Arrcode)->pluck('payment_method', 'code')->toArray();
        foreach ($orderPaymentMethod as $key => $paymentMethodOfCode) {
            if ($paymentMethodOfCode !== "bank_transfer") {
                return $this->responseError(Message::get('V002', Message::get('payment_method') . " [$key]"));
            }
        }
        try {
            DB::beginTransaction();
            Order::whereIn('code', $Arrcode)
                ->update([
                    'transfer_confirmation' => 1,
                    'payment_status'        => 1
                ]);
            // Log::update($this->model->getTable(), "#ID:" . $order->id . "-" . $order->code, null, $order->code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        // return ['status' => Message::get("orders.update-success", $order->code), 'data' => $order];
        return ['status' => Message::get("R002", $strCode)];
    }

    public function adminUpdate(
        $id,
        Request $request,
        OrderAdminUpdateValidator $orderAdminUpdateValidator,
        OrderTransformer $orderTransformer
    )
    {
        $input       = $request->all();
        $input['id'] = $id;
        $orderAdminUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $order = Order::find($id);
            if (empty($order)) {
                return $this->response->errorBadRequest(Message::get("orders.not-exist", "#$id"));
            }
            $orderStatus = OrderStatus::model()->where([
                'company_id' => TM::getCurrentCompanyId(),
                'code'       => $input['status'],
            ])->first();
            if (empty($orderStatus)) {
                return $this->response->errorBadRequest(Message::get("V002", "status"));
            }

            // Create History
            if ($order->status != $input['status']) {
                OrderHistory::insert([
                    'order_id'   => $order->id,
                    'status'     => $input['status'],
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'created_by' => TM::getCurrentUserId(),
                ]);
            }
            $status             = $order->status;
            $oldPartnerId       = $order->partner_id;
            $order->partner_id  = $input['partner_id'];
            $order->status      = $input['status'];
            $order->status_text = $orderStatus->name;
            $order->save();

            $this->adminAssignPartner($order, $oldPartnerId);

            if ($status == ORDER_STATUS_RECEIVED && $input['status'] == ORDER_STATUS_COMPLETED) {
                $point                 = round($order->total_price / 10000);
                $order->customer_point = $order->partner_point = $point;
                $order->completed_date = date("Y-m-d H:i:s", time());
                $order->save();
                $users = User::whereIn('id', [$order->customer_id, $order->partner_id]);
                foreach ($users as $user) {
                    $user->point      += $point;
                    $ranking          = MembershipRank::model()->where('point', '>=', $user->point)->orderBy('point')->first();
                    $user->ranking_id = $ranking->id;
                    $user->save();
                    unset($user);
                }
            }

            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);

            Log::update($this->model->getTable(), "#ID:" . $order->id . "-" . $order->code, null, $order->code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("orders.update-success", $order->code)];
    }

    public function updateOrderStatus($code, Request $request)
    {
        $input = $request->all();
        $order = Order::where('code', $code)->first();
        if(empty($order)){
            return $this->response->errorBadRequest(Message::get("orders.not-exist", "#$code"));
        }
        $status = OrderStatus::model()->select('id', 'code', 'name')->where([
                'company_id' => TM::getCurrentCompanyId(),
                ])->get()->toArray();
        if(!in_array($input['status'], array_column($status, 'code'))){
            return $this->responseError(Message::get("V002", "Status"));
        }
        $statusData[0] = 'NEW';
        foreach($status as $value){
            if(!empty(ORDER_STATUS_NEXT[$value['code']])){
                $statusData[] = ORDER_STATUS_NEXT[$value['code']];
            }
        }
        try{
            DB::beginTransaction();
            $statusHistory = $order->statusHistories->pluck('order_status_code')->toArray();
            $orderHistory = $order->orderHistory->pluck('status')->toArray();

            if(in_array('COMPLETED', $statusHistory) || in_array('COMPLETED', $orderHistory)){
                return $this->responseError(Message::get("order_status_histories.update-failed"));
            }
            if(in_array('CANCELED', $statusHistory) || in_array('CANCELED', $orderHistory)){
                return $this->responseError(Message::get("order_status_histories.canceled"));
            }

            // update order status
            $order->status      = $input['status'];
            $order->status_text = array_get(array_column($status, 'name', 'code'), $input['status']);
            $order->save();

            if ($order->status == ORDER_STATUS_COMPLETED){
                #CDP
                try {
                    CDP::pushOrderCdp($order, 'updateOrderStatus - OrderController - line:942');
                } catch (\Exception $e) {
                }
            }

            // write history
            if($input['status'] != 'CANCELED'){
                foreach($statusData as $value){
                    if(!in_array($value, $statusHistory)){
                        OrderStatusHistory::create([
                            'order_id' => $order->id,
                            'order_status_id' => array_get(array_column($status, 'id', 'code'), $value),
                            'order_status_code' => $value,
                            'order_status_name' => array_get(array_column($status, 'name', 'code'), $value),
                            'created_by' => TM::getCurrentUserId(),
                        ]);
                    }
                    if(!in_array($value, $orderHistory)){
                        OrderHistory::create([
                            'order_id' => $order->id,
                            'status' => $value,
                            'created_by' => TM::getCurrentUserId(),
                        ]);
                    }
                    if($value == $input['status']){
                        break;
                    }
                }
            }
            
            if($input['status'] == 'CANCELED'){
                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'order_status_id' => array_get(array_column($status, 'id', 'code'), $input['status']),
                    'order_status_code' => $input['status'],
                    'order_status_name' => array_get(array_column($status, 'name', 'code'), $input['status']),
                    'created_by' => TM::getCurrentUserId(),
                ]);
                OrderHistory::create([
                    'order_id' => $order->id,
                    'status' => $input['status'],
                    'created_by' => TM::getCurrentUserId(),
                ]);
            }
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("order_status_histories.update-success", $order->code), 'data' => $order];
    }

    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $order = Order::find($id);
            if (empty($order)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Order detail
            OrderDetail::model()->where('order_id', $id)->delete();
            // 2. Delete Order
            $order->delete();
            Log::delete($this->model->getTable(), "#ID:" . $order->id . "-" . $order->code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("orders.delete-success", $order->code)];
    }

    //Transaction history
    public function paymentHistory($userId)
    {
        // $user = User::find($userId)->select('type');
        $user = User::where('id', $userId)->select('type')->first();
        if (empty($user)) {
            return $this->response->errorBadRequest(Message::get("V003", "ID #$userId"));
        }

        $total         = 0;
        $count         = 0;
        $rate          = 0;
        $orderCannel   = 0;
        $orderComplete = 0;
        $orderReceive  = 0;
        $totalOrder    = 0;
        if ($user->type == USER_TYPE_CUSTOMER) {
            $orders = Order::model()->where('customer_id', $userId)->select('total_price')->where('status', ORDER_STATUS_COMPLETED)->get();
            if (count($orders) > 0) {
                foreach ($orders as $key => $order) {
                    $count = $count + 1;
                    $total += $order->total_price;
                }
            }
            $orderComplete = Order::model()->where('customer_id', $userId)->where(
                'status',
                ORDER_STATUS_COMPLETED
            )->count();
            $orderCannel   = Order::model()->where('customer_id', $userId)->where(
                'status',
                ORDER_STATUS_CANCELED
            )->count();
            $totalOrder    = $orderComplete + $orderCannel;
            if ($totalOrder > 0) {
                $rate = $orderCannel / $totalOrder * 100;
            }
        }
        if ($user->type == USER_TYPE_PARTNER) {
            $totalOrder    = Order::model()->where('partner_id', $userId)->count();
            $orderCannel   = Order::model()->where('partner_id', $userId)->where(
                'status',
                ORDER_STATUS_CANCELED
            )->count();
            $orderComplete = Order::model()->where('partner_id', $userId)->where(
                'status',
                ORDER_STATUS_COMPLETED
            )->count();
            if ($totalOrder > 0) {
                $rate = $orderCannel / $totalOrder * 100;
            }
        }
        $data = [
            'total_pay'             => $total,
            'transaction_completed' => $orderComplete,
            'transaction_canceled'  => $orderCannel,
            'rate_canceled'         => $rate,
            'transaction_received'  => $totalOrder - $orderCannel,
        ];
        return response()->json(['data' => $data]);
    }

    public function listHistory($id)
    {
        $orderHistoryModel = new OrderHistoryModel();
        $histories         = $orderHistoryModel->search(['order_id' => $id]);

        return response()->json(['data' => $histories]);
    }

    ################################## PARTNER ###################################

    public function listPartner($id, Request $request)
    {
        try {
            $order = Order::find($id);
            if (empty($order)) {
                throw new \Exception(Message::get("V003", "Order #$id"));
            }

            $deniedIds       = !empty($order->denied_ids) ? explode(",", $order->denied_ids) : [];
            $partnerForOrder = $this->model->getPartnerForOrder($order->lat, $order->long, $deniedIds);

            return response()->json(['data' => $partnerForOrder]);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $order;
    }

    public function assignPartner($id)
    {
        try {
            $order = Order::find($id);
            if (empty($order)) {
                throw new \Exception(Message::get("V003", "Order #$id"));
            }

            $partnerFound = $this->requestPartner($order);

            if (!empty($partnerFound)) {
                DB::beginTransaction();
                $order->request_assign_to = $partnerFound->user_id;
                $order->save();
                DB::commit();
            }
            return response()->json(['data' => $partnerFound]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function partnerResponse($id, Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $order = Order::where('id', $id)->select('id', 'long', 'lat', 'code', 'partner_id', 'status', 'total_price', 'district_fee', 'denied_ids', 'customer_id', 'partner_revenue_total', 'partner_ship_fee', 'shine_revenue_total')->first();
            if (empty($order)) {
                throw new \Exception(Message::get("V003", "Order #$id"));
            }

            // Check Order Received by Other Partner
            if (!empty($order->partner_id) || $order->status == ORDER_STATUS_RECEIVED) {
                throw new \Exception(Message::get("V039"));
            }

            if (empty($input['received']) || strtoupper($input['received']) == "NO") {
                // Deny
                $denieds           = !empty($order->denied_ids) ? explode(",", $order->denied_ids) : [];
                $denieds[]         = TM::getCurrentUserId();
                $denieds           = array_unique($denieds);
                $order->denied_ids = implode(",", $denieds);
                $order->save();

                // Continue Request
                $this->requestPartner($order);
                $userStatus = "CANCELED";
            } elseif (strtoupper($input['received']) == "YES") {
                $userStatus = "RECEIVED";

                // Partner Revenue
                $objectType          = TM::getMyPartnerType();
                $totalPartnerFee     = 0;
                $totalPartnerRevenue = 0;
                if ($objectType && in_array($objectType, [USER_PARTNER_TYPE_ENTERPRISE, USER_PARTNER_TYPE_PERSONAL])) {
                    $objectType    = strtolower($objectType);
                    $details       = $order->details;
                    $allProductIds = array_column($details->toArray(), 'product_id');
                    $products      = Product::model()->select(['id', 'personal_object', 'enterprise_object'])
                        ->whereIn('id', $allProductIds)->get()->pluck(null, 'id')->toArray();

                    foreach ($details as $detail) {
                        $price          = !empty($detail->real_price) ? $detail->real_price : $detail->price;
                        $qty            = object_get($detail, 'qty', 0);
                        $partnerRate    = array_get($products, $detail->product_id . ".{$objectType}_object");
                        $partnerRevenue = $partnerRate > 0 ? ($qty * $price * (float)$partnerRate / 100) : null;
                        if ($totalPartnerFee == 0) {
                            $totalPartnerFee = $partnerRate == "fee" ? ((int)$order->district_fee) : 0;
                        }
                        $shineRate                     = $partnerRate === null || $partnerRate == "fee" ? 100 : (100 - $partnerRate);
                        $totalPartnerRevenue           += $partnerRevenue;
                        $detail->partner_revenue_rate  = $partnerRate;
                        $detail->partner_revenue_total = $partnerRevenue;
                        $detail->shine_revenue_rate    = $shineRate;
                        $detail->status                = ORDER_STATUS_PENDING;
                        $detail->updated_at            = date('Y-m-d H:i:s', time());
                        $detail->updated_by            = TM::getCurrentUserId();
                        $detail->save();
                    }
                }

                // Update Revenue
                $order->partner_revenue_total = $totalPartnerRevenue;
                $order->partner_ship_fee      = $totalPartnerFee;
                $order->shine_revenue_total   = $order->total_price - $totalPartnerRevenue;

                // Update Partner for Order
                $order->partner_id = TM::getCurrentUserId();
                $order->status     = ORDER_STATUS_RECEIVED;
                $order->save();

                // Send Notification
                $device = UserSession::model()->where('user_id', $order->customer_id)
                    ->select('device_token',)
                    ->where('deleted', '0')->first();
                $device = $device->device_token ?? null;
                if ($device) {
                    $receiver = Profile::model()->where('user_id', TM::getCurrentUserId())
                        ->select('id', 'user_id', 'full_name', 'phone', 'lat', 'long')->first();
                    // Send Notification
                    $fields  = [
                        'data'         => [
                            'type'         => "RECEIVED-PARTNER",
                            'order_id'     => $order->id,
                            'user_id'      => $receiver->user_id,
                            'full_name'    => $receiver->full_name,
                            'phone'        => $receiver->phone,
                            'lat'          => $receiver->lat,
                            'long'         => $receiver->long,
                            "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                        ],
                        'notification' => [
                            'title' => "Đã có đối tác nhận đơn của bạn",
                            'sound' => 'shame',
                            'body'  => $receiver->full_name . " đã nhận đơn của bạn",
                        ],
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
                        throw new \Exception("FCM Error: " . curl_error($ch));
                    }
                    curl_close($ch);
                }
            }

            if (isset($userStatus)) {
                // Add User Status Order
                UserStatusOrder::insert([
                    'user_id'    => TM::getCurrentUserId(),
                    'order_id'   => $order->id,
                    'status'     => $userStatus,
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'created_by' => TM::getCurrentUserId(),
                ]);
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => "Successfully"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function requestPartner(Order $order)
    {
        $deniedIds       = !empty($order->denied_ids) ? explode(",", $order->denied_ids) : [];
        $partnerForOrder = $this->model->getPartnerForOrder($order->lat, $order->long, $deniedIds, 1);
        if (!empty($partnerForOrder)) {
            $userId = $partnerForOrder->user_id;
            $device = UserSession::model()->where('user_id', $userId)->where('deleted', '0')->first();
            $device = $device->device_token ?? null;
            if ($device) {
                // Send Notification
                $fields  = [
                    'data'         => [
                        'type'         => "ASSIGN-PARTNER",
                        'id'           => $order->id,
                        //                        'order'    => $order->toArray(),
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                    ],
                    'notification' => [
                        'title' => "Đơn hàng mới",
                        'sound' => 'shame',
                        'body'  => "Bạn nhận được đơn hàng: " . $order->code,
                    ],
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
                    throw new \Exception("FCM Error: " . curl_error($ch));
                }
                curl_close($ch);
            }
        }

        return $partnerForOrder;
    }

    public function updateStatus($id, Request $request)
    {
        $input = $request->all();
        if (empty($input['status'])) {
            return $this->responseError(Message::get("V001", "Status"));
        }

        if (
        !in_array($input['status'], [
            'NEW',
            'PENDING',
            'RECEIVED',
            'COMMING',
            'ARRIVED',
            'INPROGRESS',
            'COMPLETED',
        ])
        ) {
            return $this->responseError(Message::get("V002", "Status"));
        }

        try {
            DB::beginTransaction();
            $order = Order::where('id', $id)->select('id', 'code', 'customer_id', 'company_id', 'partner_id', 'status', 'customer_point', 'partner_point', 'status', 'status_text', 'total_price', 'completed_date', 'payment_method', 'payment_status')
                ->first();
            if (empty($order)) {
                return $this->responseError(Message::get("V002", "Order #$id"));
            }

            if ($input['status'] != ORDER_STATUS_RECEIVED && $order->partner_id != TM::getCurrentUserId()) {
                return $this->responseError(Message::get("V003", "Order #$id"));
            }

            // Check to reject if status is COMPLETED
            if ($order->status == ORDER_STATUS_COMPLETED) {
                return $this->responseError(Message::get("user_profiles.profile-change-error"));
            }

            if ($input['status'] != ($status = $this->getNextStatus($order))) {
                return $this->responseError(Message::get("V006", Message::get("status"), $status));
            }


            if ($input['status'] == ORDER_STATUS_CANCELED) {
                $order->customer_point = null;
            }

            $orderStatus = OrderStatus::model()->where([
                'company_id' => TM::getCurrentCompanyId(),
                'code'       => $input['status'],
            ])->select('name')->first();

            if (empty($orderStatus)) {
                return $this->response->errorBadRequest(Message::get("V002", "status"));
            }

            $order->status      = $input['status'];
            $order->status_text = $orderStatus->name;
            $order->save();

            // Order History
            $profile = Profile::model()->where('user_id', TM::getCurrentUserId())
                ->select('lat', 'long')->first();

            $orderHistory             = new OrderHistory();
            $orderHistory->order_id   = $id;
            $orderHistory->status     = $input['status'];
            $orderHistory->created_at = date("Y-m-d H:i:s", time());
            $orderHistory->created_by = TM::getCurrentUserId();
            if (!empty($profile->lat)) {
                $orderHistory->lat = $profile->lat;
            }
            if (!empty($profile->long)) {
                $orderHistory->long = $profile->long;
            }
            $orderHistory->save();

            if ($input['status'] == ORDER_STATUS_COMPLETED) {
                $this->completeOrder($order);
            }
            if ($order->status == ORDER_STATUS_COMPLETED && $order->payment_method == PAYMENT_METHOD_ONLINE) {
                $order->payment_status = 1;
                $order->save();
            }
            // Update Point for CUSTOMER & PARTNER
            if ($order->status == ORDER_STATUS_COMPLETED) {
                $hourComplete = date('H', strtotime($order->completed_date));
                $point        = round($order->total_price / 10000);
                $users        = User::model()->whereIn('id', [$order->customer_id, $order->partner_id])
                    ->select('id', 'type', 'point', 'ranking_id', 'ranking_code')->get();
                foreach ($users as $user) {
                    $pointRate = $user->membership->point_rate ?? 1;
                    if ($user->type == USER_TYPE_CUSTOMER && $order->payment_method != PAYMENT_METHOD_CASH) {
                        $point *= 2;
                    }
                    if ($user->type == USER_TYPE_PARTNER && $hourComplete >= 6 && $hourComplete <= 9) {
                        $point *= 2.5;
                    }
                    if ($user->type == USER_TYPE_PARTNER && ($hourComplete < 6 || $hourComplete > 9)) {
                        $point *= 2;
                    }
                    $point            *= $pointRate;
                    $user->point      += $point;
                    $userType         = $user->type == USER_TYPE_CUSTOMER ? 'customer_point' : 'partner_point';
                    $order->$userType = $point;
                    $ranking_new      = MembershipRank::model()->where('point', '<=', $user->point)->where(
                        'type',
                        $user->type
                    )->select('id', 'code')
                        ->orderBy(
                            'point',
                            'DESC'
                        )->first();
                    if (!empty($ranking_new)) {
                        $user->ranking_id   = $ranking_new->id;
                        $user->ranking_code = $ranking_new->code;
                    }
                    $user->save();
                    unset($user);
                }
                $order->completed_date = date("Y-m-d H:i:s", time());
                $order->save();
            }
            //Update Order Details If Order Status = COMPLETED
            if ($input['status'] == ORDER_STATUS_COMPLETED) {
                OrderDetail::model()->where('order_id', $order->id)->update(['status' => ORDER_STATUS_COMPLETED]);
            }
            $this->sendNotifyUpdateStatus($order);

            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);
            DB::commit();

            $company = Company::model()->where('id', TM::getCurrentCompanyId())
                ->select('avatar', 'email', 'name')->first();
            $order   = Order::model()->with(['customer', 'store', 'details.product.unit'])->where(
                'id',
                $order->id
            )->first();
            if (env('SEND_EMAIL', 0) == 1) {
                $this->dispatch(new SendCustomerMailUpdateOrderStatusJob($order->customer->email, [
                    'logo'         => $company->avatar,
                    'support'      => $company->email,
                    'company_name' => $company->name,
                    'order'        => $order,
                ]));
                $this->dispatch(new SendStoreMailUpdateOrderStatusJob($order->store->email_notify, [
                    'logo'         => $company->avatar,
                    'support'      => $company->email,
                    'company_name' => $company->name,
                    'order'        => $order,
                ]));
            }

            return response()->json(['status' => 'success', 'message' => "Order Status updated successfully"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function viewStatusHistory($id)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('id', $id)->select('id')->first();
            if (empty($order)) {
                return $this->responseError(Message::get("V002", "Order #$id"));
            }

            $orderHistories = OrderHistory::model()->select([
                'o.code',
                'order_histories.lat',
                'order_histories.long',
                'order_histories.status',
                'order_histories.created_by as user_id',
                'p.full_name as full_name',
            ])->join('profiles as p', 'p.user_id', '=', 'order_histories.created_by')
                ->join('orders as o', 'o.id', '=', 'order_histories.order_id')
                ->where('order_id', $id)->orderBy('order_histories.id', 'desc')->get()->toArray();

            return response()->json(['status' => 'success', 'data' => $orderHistories]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function rateOrder($id, Request $request)
    {
        $input = $request->all();
        if (empty($input['rating_star'])) {
            return $this->responseError(Message::get("V001", "Star"), 422);
        }
        if ($input['rating_star'] < 0 || $input['rating_star'] > 5) {
            return $this->responseError(Message::get("V002", "Star"), 422);
        }
        try {
            DB::beginTransaction();
            $order = Order::find($id);
            if (empty($order)) {
                return $this->responseError(Message::get("V002", "Order #$id"), 422);
            }

            if ($order->partner_star > 0) {
                return $this->responseError(Message::get("unique", "Rating"));
            }

            $user       = TM::info();
            $userPrefix = $user['type'] == "USER" ? '' : ($user['type'] == USER_TYPE_PARTNER ? 'customer' : 'partner');

            if ($userPrefix) {
                $order->{$userPrefix . "_star"}     = $input['rating_star'];
                $order->{"comment_for_$userPrefix"} = $input['rating_comment'] ?? null;
                $order->save();
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => "Rate Successfully"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function completeOrder(Order $order)
    {
        $userWallet = Wallet::model()->where('user_id', TM::getCurrentUserId())
            ->select('balance', 'id')->first();
        if (!empty($userWallet)) {
            $userWallet->balance = $userWallet->balance + $order->total_price;
            $userWallet->save();
        }

        $this->model->updateProductSold($order);

        // Update Order Status History
        $this->model->updateOrderStatusHistory($order);
    }

    private function sendNotifyUpdateStatus($order)
    {
        $statusTitle = OrderStatus::model()->select([
            'code',
            'name',
        ])->where(['company_id' => TM::getCurrentCompanyId()])
            ->get()->pluck('name', 'code')->toArray();

        try {
            $title = "Đơn hàng #" . ($order->code) . " " . ($statusTitle[$order->status]);
            //Notify
            $notify                       = new Notify();
            $notify->title                = "Đơn hàng " . ($statusTitle[$order->status]);
            $notify->body                 = $title;
            $notify->type                 = "ORDER";
            $notify->target_id            = null;
            $notify->product_search_query = null;
            $notify->notify_for           = 'ORDER';
            $notify->delivery_date        = date('Y-m-d H:i:s', time());
            $notify->frequency            = 'ASAP';
            $notify->user_id              = $order->customer_id;
            $notify->company_id           = TM::getCurrentCompanyId();
            $notify->sent                 = 0;
            $notify->is_active            = 1;
            $notify->created_at           = date('Y-m-d H:i:s', time());
            $notify->created_by           = $order->customer_id;
            $notify->updated_at           = date('Y-m-d H:i:s', time());
            $notify->updated_by           = $order->customer_id;
            $notify->save();

            //Get Device
            $userSession = UserSession::model()->where('user_id', $userId = $order->customer_id)->where(
                'deleted',
                '0'
            )->select('device_token')->first();
            $device      = $userSession->device_token ?? null;

            if (empty($device)) {
                return false;
            }


            $notificationHistory              = new NotificationHistory();
            $notificationHistory->title       = "Đơn hàng " . ($statusTitle[$order->status]);
            $notificationHistory->body        = $title;
            $notificationHistory->message     = $title;
            $notificationHistory->notify_type = "ORDER";
            $notificationHistory->type        = "ORDER";
            $notificationHistory->extra_data  = '';
            $notificationHistory->receiver    = $device;
            $notificationHistory->action      = 1;
            $notificationHistory->item_id     = $order->id;
            $notificationHistory->created_at  = date('Y-m-d H:i:s', time());
            $notificationHistory->created_by  = $order->customer_id;
            $notificationHistory->updated_at  = date('Y-m-d H:i:s', time());
            $notificationHistory->updated_by  = $order->customer_id;
            $notificationHistory->save();


            DB::table('notification_histories')->where('id', $notificationHistory->id)->update([
                'created_by' => $order->customer_id,
                'updated_by' => $order->customer_id,
            ]);
            $notificationHistory = $notificationHistory->toArray();
            $action              = ["click_action" => "FLUTTER_NOTIFICATION_CLICK"];
            $notificationHistory = array_merge($notificationHistory, $action);

            $notification                        = ['title' => "Đơn hàng " . ($statusTitle[$order->status]), 'body' => $title];
            $notificationHistory["click_action"] = "FLUTTER_NOTIFICATION_CLICK";
            $notificationHistory["order_status"] = $order->status;
            $fields                              = [
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

    private function sendNotifyConfirmOrder($request, $order)
    {
        try {
            if (TM::getCurrentUserId()) {
                $company_id = TM::getCurrentCompanyId();
            } else {
                $headers = $request->headers->all();
                if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                    $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                    if ($store_token_input && strlen($store_token_input) == 64) {
                        $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                        if (!$store) {
                            return $this->responseError(Message::get("token_invalid"));
                        }
                        $company_id = $store->company_id;
                    }
                }
            }
            if (!empty($order->distributor_code)) {

                $notify = new Notify();
                $param  = [
                    'title'                => "Thông báo đơn hàng mới",
                    'body'                 => "Nhà phân phối [$order->distributor_name] được gán đơn hàng #$order->code từ khách hàng [$order->customer_name].",
                    'type'                 => "ORDER",
                    'target_id'            => $input['target_id'] ?? null,
                    'product_search_query' => $input['product_search_query'] ?? null,
                    'notify_for'           => "ORDER",
                    'delivery_date'        => date("Y-m-d H:i:s", time()),
                    'frequency'            => "ASAP",
                    'user_id'              => $order->distributor_id,
                    'company_id'           => $company_id,
                    'created_by'           => $order->distributor_id,
                    'updated_by'           => $order->distributor_id,
                    'created_at'           => date("Y-m-d H:i:s", time()),
                    'updated_at'           => date("Y-m-d H:i:s", time()),
                ];
                $notify->insert($param);
                $device = UserSession::model()->where('user_id', $order->distributor_id)->where('deleted', '0')->first();
                $device = $device->device_token ?? null;
                if ($device) {
                    // Send Notification
                    $fields  = [
                        'data'         => [
                            'type'         => "ORDER",
                            'id'           => $order->id,
                            "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                        ],
                        'notification' => [
                            'title' => "Thông báo đơn hàng mới",
                            'sound' => 'shame',
                            'body'  => "Nhà phân phối [$order->distributor_name] được gán đơn hàng #$order->code từ khách hàng [$order->customer_name].",
                        ],
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
                        throw new \Exception("FCM Error: " . curl_error($ch));
                    }
                    curl_close($ch);
                }
            }
        } catch (\Exception $ex) {
            throw $ex;
        }
    }

    public function cancelOrder($orderId, Request $request)
    {
        $input = $request->all();
        try {
            if (empty($input['canceled_reason'])) {
                return $this->responseError(Message::get("V001", "Lý do hủy đơn"));
            }
            DB::beginTransaction();
            //            $type     = TM::getMyUserType();
            //            $typeUser = $type == USER_TYPE_PARTNER ? 'partner' : ($type == USER_TYPE_CUSTOMER ? 'customer' : null);
            //            if (empty($typeUser)) {
            //                return $this->responseError(Message::get("users.not-allow-access", Message::get("users")));
            //            }

            //            $order = Order::model()->where($typeUser . "_id", TM::getCurrentUserId())->where('id', $orderId)->first();
            $user  = User::find(TM::getCurrentUserId());
            $order = Order::model()->where('id', $orderId);
            if ($user->role_id == 4) {
                $order = $order->where('customer_id', $user->id);
            }
            $order = $order->first();
            if (empty($order)) {
                return $this->responseError(Message::get("V002", "Order #$orderId"));
            }

            if ($order->status == ORDER_STATUS_COMPLETED) {
                return $this->responseError(Message::get("orders.update-block", "Order #$orderId"));
            }

            $orderStatus = OrderStatus::model()->where([
                'company_id' => TM::getCurrentCompanyId(),
                'code'       => ORDER_STATUS_CANCELED,
            ])->first();
            if (empty($orderStatus)) {
                return $this->response->errorBadRequest(Message::get("V002", "status"));
            }
            if (!empty($input['canceled_reason']['images'])) {
                $input['canceled_reason']['images'] = env('GET_FILE_URL') . $input['canceled_reason']['images'][0]['id'] ?? null;
            }
            $order->status        = ORDER_STATUS_CANCELED;
            $order->status_text   = $orderStatus->name;
            $order->canceled_date = date("Y-m-d H:i:s", time());
            $order->canceled_by   = TM::getCurrentUserId();
            if ($user->role_id == 4) {
                $order->canceled_reason       = $input['canceled_reason'];
                $order->canceled_reason_admin = null;
            }
            if ($user->role_id != 4) {
                $order->canceled_reason       = null;
                $order->canceled_reason_admin = $input['canceled_reason'];
            }
            if (!empty($order->coupon_code)) {
                $coupon_id = Coupon::model()->where('code', $order->coupon_code)->value('id');

                $coupon = CouponCodes::model()->where('coupon_id', $coupon_id)->where('code', $order->coupon_discount_code)->first();

                // $order_used = !empty($coupon->order_userd) ? explode(",", $coupon->order_userd) : [];
                // foreach(explode(",", $coupon->order_used) as $dh){
                //     if($order->code != $dh){
                //         $order_used[] = $dh ?? null;
                //     }
                // }   

                $coupon_new            = new CouponCodes();
                $coupon_new->coupon_id = $coupon->coupon_id;
                $coupon_new->code      = $coupon->code;
                $coupon_new->is_active = $coupon->is_active == 1 ? 0 : $coupon->is_active;
                $coupon_new->type      = $coupon->type;
                $coupon_new->discount  = $coupon->discount;
                // $coupon_new->order_used = implode(",", $order_used);
                $coupon_new->order_used     = $coupon->order_used;
                $coupon_new->limit_discount = $coupon->limit_discount;
                $coupon_new->save();

                $coupon->delete();

            }
            $order->save();
            // $param = [
            //     "orderNumber"       => $order->code,
            //     "status"            => 'C',
            // ];
            // $client = new Client();
            // $response = $client->put(env("DMS_ORDER") . "/SaleOrder", [
            //     'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . env("TOKEN_DMS_ORDER")],
            //     'body'    => json_encode($param)
            // ]);

            // $response = !empty($response) ? $response->getBody()->getContents() : null;
            // $status = !empty($response) ? $response : "Đồng bộ thành công!!";
            //            $this->sendMailCancelRequest($order);
            $this->addQtyWarehouseCancel($order->details);
            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);

            DB::commit();
            try {
                $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
                $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
                if (!empty($dataUpdateDMS)) {
                    $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                    if (!empty($pushOrderStatusDms['errors'])) {
                        foreach ($pushOrderStatusDms['errors'] as $item) {
                            Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                        }
                    } else {
                        if (!empty($pushOrderStatusDms)) {
                            Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                        }
                        if (empty($pushOrderStatusDms)) {
                            Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                        }
//                        Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                    }

                }
                Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS) ?? []]);
            } catch (\Exception $exception) {
                Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "CREATE-ORDER", 0, null);
            }
            #UPDATE[ACCESSTRADE] huy don
            if (!empty($order->access_trade_id)) {
                $status = ORDER_STATUS_REJECTED;
                $reason = $input['canceled_reason']['value'] ?? $input['canceled_reason'];
                Accesstrade::update($order, $status, $reason);
            }
            return response()->json(['status' => Message::get('R020', "[$order->code]")]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function addQtyWarehouseCancel($orderId)
    {
        try {
            DB::beginTransaction();
            foreach ($orderId as $detail) {
                $inventory = WarehouseDetail::model()->select('quantity')->where([
                    'product_id' => $detail->product_id,
                    // 'warehouse_id' => $input_detail['warehouse_id'],
                    // 'batch_id'     => $input_detail['batch_id'],
                    // 'unit_id'      => $input_detail['unit_id'],
                    'company_id' => TM::getCurrentCompanyId(),
                ])->first();
                WarehouseDetail::model()->where([
                    'product_id' => $detail->product_id,
                    // 'warehouse_id' => $input_detail['warehouse_id'],
                    // 'batch_id'     => $input_detail['batch_id'],
                    // 'unit_id'      => $input_detail['unit_id'],
                    'company_id' => TM::getCurrentCompanyId(),
                ])->update(['quantity' => $inventory->quantity + $detail->qty]);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function payOrder($orderId, Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $type = TM::getMyUserType();
            if ($type != USER_TYPE_CUSTOMER) {
                return $this->responseError(Message::get("users.not-allow-access", Message::get("users")));
            }

            $order = Order::model()->where("customer_id", TM::getCurrentUserId())->where('id', $orderId)->first();
            if (empty($order)) {
                return $this->responseError(Message::get("V003", "Order #$orderId"));
            }

            if ($order->paid === 1) {
                return $this->responseError(Message::get("orders.paid", "#$orderId"));
            }

            $wallet = Wallet::model()->where('user_id', TM::getCurrentUserId())->first();
            if (empty($wallet)) {
                return $this->responseError(Message::get("V003", Message::get("wallets")));
            }

            if ($wallet->balance < $order->total_price) {
                return $this->responseError(Message::get("wallets.not-enough"));
            }

            $oldBalance         = $wallet->balance;
            $wallet->balance    -= $order->total_price;
            $wallet->updated_at = date("Y-m-d H:i:s", time());
            $wallet->updated_by = TM::getCurrentUserId();
            $wallet->save();


            $transId = "ORDER: #" . $order->code;
            $payDate = date("Y-m-d H:i:s", time());
            // Wallet History
            $walletHistory = new WalletHistoryModel();
            $walletHistory->create([
                "wallet_id"      => $wallet->id,
                "transaction_id" => $transId,
                "date"           => $payDate,
                "balance"        => $oldBalance,
                "decrease"       => $order->total_price,
                "created_at"     => date("Y-m-d H:i:s", time()),
                "created_by"     => TM::getCurrentUserId(),
            ]);

            // Create Payment History
            $paymentHistoryModel = new PaymentHistoryModel();
            $paymentHistoryModel->create([
                'transaction_id' => $transId,
                'date'           => $payDate,
                'type'           => PAYMENT_TYPE_PAYMENT,
                'method'         => PAYMENT_METHOD_WALLET,
                'status'         => PAYMENT_STATUS_SUCCESS,
                'content'        => Message::get("orders.paid", "#" . $order->code),
                'total_pay'      => $order->total_price,
                'balance'        => null,
                'user_id'        => TM::getCurrentUserId(),
                'data'           => null,
                'note'           => Message::get("V031"),
            ]);

            $order->paid       = 1;
            $order->updated_at = date("Y-m-d H:i:s", time());
            $order->updated_by = TM::getCurrentUserId();
            $order->save();

            // Update Order Status History
            $this->model->updateOrderStatusHistory($order);
            DB::commit();

            return response()->json(['status' => 'success', 'message' => Message::get("V031")]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    /**
     * @param Order $order
     */
    private function sendMailCancelRequest(Order $order)
    {
        $approve = "Dai Ho";
        $user    = TM::info();
        $data    = [
            'approver'    => $approve,
            'userType'    => TM::getMyUserType(),
            'user'        => $user['full_name'],
            'orderCode'   => $order->code,
            'reason'      => $order->canceled_reason,
            'phone'       => $user['phone'],
            'email'       => $user['email'],
            'orderStatus' => $order->status,
            'link_to'     => TM::urlBase("/#/orders/" . $order->id),
        ];
        if (env('SEND_EMAIL', 0) == 1) {
            $this->dispatch(new SendMailCancelRequestJob($data));
        }
    }

    /**
     * @param Order $order
     * @return mixed|null
     */
    private function getNextStatus(Order $order)
    {
        $nextStatus = [
            'NEW'        => 'RECEIVED',
            'RECEIVED'   => 'COMMING',
            'COMMING'    => 'ARRIVED',
            'ARRIVED'    => 'INPROGRESS',
            'INPROGRESS' => 'COMPLETED',
        ];

        return $nextStatus[$order->status] ?? null;
    }

    private function adminAssignPartner(Order &$order, $oldPartner)
    {
        if ($oldPartner != $order->partner_id && $order->status = ORDER_STATUS_NEW) {
            // Partner Revenue
            $objectType          = object_get($order, 'partner.type');
            $totalPartnerFee     = 0;
            $totalPartnerRevenue = 0;
            if ($objectType && in_array($objectType, [USER_PARTNER_TYPE_ENTERPRISE, USER_PARTNER_TYPE_PERSONAL])) {
                $objectType    = strtolower($objectType);
                $details       = $order->details;
                $allProductIds = array_column($details->toArray(), 'product_id');
                $products      = Product::model()->select(['id', 'personal_object', 'enterprise_object'])
                    ->whereIn('id', $allProductIds)->get()->pluck(null, 'id')->toArray();

                foreach ($details as $detail) {
                    $price          = !empty($detail->real_price) ? $detail->real_price : $detail->price;
                    $qty            = object_get($detail, 'qty', 0);
                    $partnerRate    = array_get($products, $detail->product_id . ".{$objectType}_object");
                    $partnerRevenue = $partnerRate > 0 ? ($qty * $price * (float)$partnerRate / 100) : null;
                    if ($totalPartnerFee == 0) {
                        $totalPartnerFee = $partnerRate == "fee" ? ((int)$order->district_fee) : 0;
                    }
                    $shineRate                     = $partnerRate === null || $partnerRate == "fee" ? 100 : (100 - $partnerRate);
                    $totalPartnerRevenue           += $partnerRevenue;
                    $detail->partner_revenue_rate  = $partnerRate;
                    $detail->partner_revenue_total = $partnerRevenue;
                    $detail->shine_revenue_rate    = $shineRate;
                    $detail->updated_at            = date('Y-m-d H:i:s', time());
                    $detail->updated_by            = TM::getCurrentUserId();
                    $detail->save();
                }
            }

            // Update Revenue
            $order->partner_revenue_total = $totalPartnerRevenue;
            $order->partner_ship_fee      = $totalPartnerFee;
            $order->shine_revenue_total   = $order->total_price - $totalPartnerRevenue;

            // Update Partner for Order
            $order->status = ORDER_STATUS_RECEIVED;
            $order->save();

            // Send Notification
            $devices = UserSession::model()->select(['user_id', 'device_token'])
                ->whereIn('user_id', [$order->customer_id, $order->partner_id])
                ->where('deleted', '0')->get()->pluck("device_token", "user_id")->toArray();
            if ($devices) {
                $receiver = Profile::model()->whereIn('user_id', [$order->customer_id, $order->partner_id])
                    ->get()->pluck(null, 'user_id')->toArray();
                foreach ($receiver as $id => $user) {
                    if (empty($devices[$id])) {
                        continue;
                    }

                    $title = $id == $order->customer_id ? "Đã có đối tác nhận đơn của bạn" : "Đơn hàng mới";
                    $type  = $id == $order->customer_id ? "RECEIVED-PARTNER" : "ASSIGN-PARTNER";
                    $body  = $id == $order->customer_id ? $user['full_name'] . " đã nhận đơn của bạn" : "Bạn nhận được đơn hàng: " . $order->code;

                    // Send Notification
                    $fields = [
                        'to'           => $devices[$id],
                        'data'         => [
                            'type'     => $type,
                            'order_id' => $order->id,
                            'order'    => $order->toArray(),

                            'user_id'      => $id,
                            'full_name'    => $user['full_name'],
                            'phone'        => $user['phone'],
                            'lat'          => $user['lat'],
                            'long'         => $user['long'],
                            "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                        ],
                        'notification' => ['title' => $title, 'body' => $body,],

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
                        throw new \Exception("FCM Error: " . curl_error($ch));
                    }
                    curl_close($ch);

                    // Notification History
                    $notificationHistory              = new NotificationHistory();
                    $notificationHistory->title       = $title;
                    $notificationHistory->body        = $title;
                    $notificationHistory->message     = $title;
                    $notificationHistory->notify_type = "ORDER";
                    $notificationHistory->type        = "ORDER";
                    $notificationHistory->extra_data  = '';
                    $notificationHistory->receiver    = $devices[$id];
                    $notificationHistory->action      = 1;
                    $notificationHistory->item_id     = $order->id;
                    $notificationHistory->save();

                    DB::table('notification_histories')->where('id', $notificationHistory->id)->update([
                        'created_by' => $id,
                        'updated_by' => $id,
                    ]);
                }
            }
        }
    }

    public function clientGetMyOrder(Request $request, OrderTransformer $orderTransformer)
    {
        $session_id = $request->input('session_id');
        $order      = Order::model()->where('session_id', $session_id)->first();
        if (!$order || !$session_id) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($order, $orderTransformer);
    }

    public function confirmOrder(Request $request)
    {
        $input    = $request->all();
        $store_id = null;

        // Tạm chặn không cho đặt hàng từ APP
        if (get_device() == 'APP' && empty($active_status)) {
            return $this->responseError('Hiện tại app Nutifood tạm ngưng phát triển, quý khách vui lòng truy cập qua website Nutifoodshop.com để đặt hàng');
        }

        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
            $result     = $this->userConfirmOrder($request);
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return $this->responseError(Message::get("token_invalid"));
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                    $groupGuest = UserGroup::where(['company_id' => $company_id, 'code' => 'GUEST'])->first();
                    if (!empty($input['order_channel'] && $input['order_channel'] == 'POS')) {
                        foreach (['order_channel', 'phone', 'full_name'] as $item) {
                            if (empty($input[$item])) {
                                return $this->responseError(Message::get("V001", Message::get($item)));
                            }
                        }
                    } else {
                        foreach (['order_channel', 'street_address', 'phone', 'full_name'] as $item) {
                            if (empty($input[$item])) {
                                return $this->responseError(Message::get("V001", Message::get($item)));
                            }
                        }
                    }

                    $endCode = "_{$company_id}_{$store_id}";
                    DB::beginTransaction();
                    // Create Temp User
                    $user = User::model()->where([
                        'phone'      => $input['phone'],
                        'company_id' => $company_id,
                        'store_id'   => $store_id
                    ])->first();

                    if ($user) {
                        if ($user->is_active == '1') {
                            return $this->responseError(Message::get("unique_phone", Message::get("phone")));
                        }
                        $user->company_id = $company_id;
                        $user->store_id   = $store_id;
                        $user->email      = $input['email'] ?? null;
                        $user->name       = $input['full_name'];
                        $user->role_id    = USER_ROLE_GUEST_ID;
                        $user->type       = USER_TYPE_CUSTOMER;
                        $user->group_id   = $groupGuest->id;
                        $user->group_code = $groupGuest->code;
                        $user->group_name = $groupGuest->name;
                        $user->save();

                        $profile = Profile::model()->where('user_id', $user->id)->first();
                        if (!$profile) {
                            $profile             = new Profile();
                            $profile->user_id    = $user->id;
                            $profile->phone      = $input['phone'];
                            $profile->is_active  = 1;
                            $profile->created_by = $user->id;
                        }
                    } else {
                        $user              = new User();
                        $user->phone       = $input['phone'];
                        $user->password    = "NOT-VERIFY-ACCOUNT";
                        $user->email       = $input['email'] ?? null;
                        $user->code        = $input['phone'] . $endCode;
                        $user->name        = $input['full_name'];
                        $user->role_id     = USER_ROLE_GUEST_ID;
                        $user->note        = "Mua hàng không đăng nhập";
                        $user->type        = USER_TYPE_CUSTOMER;
                        $user->register_at = date("Y-m-d H:i:s");
                        $user->company_id  = $company_id;
                        $user->store_id    = $store_id;
                        $user->group_id    = $groupGuest->id;
                        $user->group_code  = $groupGuest->code;
                        $user->group_name  = $groupGuest->name;
                        $user->is_active   = 0;
                        $user->created_by  = null;
                        $user->save();

                        $profile             = new Profile();
                        $profile->user_id    = $user->id;
                        $profile->phone      = $input['phone'];
                        $profile->is_active  = 1;
                        $profile->created_by = $user->id;
                    }

                    $profile->email      = $user->email;
                    $full                = explode(" ", $input['full_name']);
                    $profile->full_name  = $input['full_name'];
                    $profile->first_name = trim($full[count($full) - 1]);
                    unset($full[count($full) - 1]);
                    $profile->last_name     = trim(implode(" ", $full));
                    $profile->address       = $input['street_address'] ?? null;
                    $profile->city_code     = $input['city_code'] ?? null;
                    $profile->district_code = $input['district_code'] ?? null;
                    $profile->ward_code     = $input['ward_code'] ?? null;
                    $profile->updated_by    = $user->id;
                    $profile->save();

                    //                    $this->updateCompanyStore($user);
                    $result = $this->clientConfirmOrder($request, $user);
                    DB::commit();
                }
            }
        }
        return response()->json([
            'status' => 'success',
            'data'   => $result
        ]);
    }

    private function updateCompanyStore(User $user)
    {
        // Delete Old Company
        UserCompany::model()->where('user_id', $user->id)->delete();
        $role    = Role::model()->where('id', $user->role_id)->first();
        $company = Company::model()->where('id', $user->company_id)->first();
        $store   = Store::model()->where('id', $user->store_id)->first();

        $userCompany = UserCompany::model()->where(['user_id' => $user->id, 'company_id' => $company->id]);

        // Create New Company
        $time         = date('Y-m-d H:i:s', time());
        $paramCompany = [
            'user_id'      => $user->id,
            'user_code'    => $user->code,
            'user_name'    => $user->name,
            'company_id'   => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name,
            'role_id'      => $role->id,
            'role_code'    => $role->code,
            'role_name'    => $role->name,
            'created_at'   => $time,
            'created_by'   => TM::getCurrentUserId(),
            'updated_at'   => $time,
            'updated_by'   => TM::getCurrentUserId(),
        ];
        UserCompany::insert($paramCompany);


        //Update User Store
        $paramUserStore = [
            'user_id'      => $user->id,
            'user_code'    => $user->code,
            'user_name'    => $user->name,
            'company_id'   => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name,
            'role_id'      => $role->id,
            'role_code'    => $role->code,
            'role_name'    => $role->name,
            'store_id'     => $store->id,
            'store_code'   => $store->code,
            'store_name'   => $store->name,
            'created_at'   => $time,
            'created_by'   => TM::getCurrentUserId(),
            'updated_at'   => $time,
            'updated_by'   => TM::getCurrentUserId(),
        ];

        UserStore::insert($paramUserStore);
    }

    private function userConfirmOrder(Request $request)
    {
        // gioi han mua hang trong ngay
        $limit_order_day = Setting::model()->where('code', 'LIMITORDER')->first();

        !empty($limit_order_day) ? $data = json_decode($limit_order_day->data) : null;

        if ($data) {
            $now   = date('Y-m-d');
            $order = Order::whereDate('created_at', $now)
                ->where('customer_id', TM::getCurrentUserId())
                ->get();
            if (count($order) >= $data[0]->value) {
                return $this->responseError("Bạn chỉ được phép mua tối đa " . $data[0]->value . " đơn hàng trong ngày");
            }
        }
        //end

        $input = $request->all();
        Session::where('session_id', $input['session_id'])->update(['phone' => $input['phone']]);
        $userId = TM::getCurrentUserId();
        $cart   = Cart::with('details.product.priceDetail')->where('user_id', $userId)->first();
        if (empty($cart)) {
            return $this->responseError(Message::get("V003", Message::get('carts')));
        }

        if (empty($input['order_channel'])) {
            return $this->responseError(Message::get("V001", Message::get('order_channel')));
        }
        if (empty($input['phone'])) {
            $this->response->errorBadRequest(Message::get("V009", Message::get('phone')));
        }
        if (empty($input['full_name'])) {
            $this->response->errorBadRequest(Message::get("V009", Message::get('name')));
        }

        $city                      = City::where('code', $input['city_code'])->first();
        $city_name                 = Arr::get($city, 'type') . " " . Arr::get($city, 'name');
        $district                  = District::where('code', $input['district_code'])->first();
        $district_name             = Arr::get($district, 'type') . " " . Arr::get($district, 'name');
        $ward                      = Ward::where('code', $input['ward_code'])->first();
        $ward_name                 = Arr::get($ward, 'type') . " " . Arr::get($ward, 'name');
        $input['shipping_address'] = "{$input['street_address']}, {$ward_name}, {$district_name}, {$city_name}";


        //Update Info Cart
        $cart->address     = $input['shipping_address'] ?? null;
        $cart->description = $input['note'] ?? null;
        $cart->phone       = $input['phone'] ?? null;
        $cart->save();

        //        if (!empty($input['order_channel']) && $input['order_channel'] == ORDER_CHANNEL_WEB) {
        //            if (empty($cart->payment_method)) {
        //                $this->response->errorBadRequest(Message::get("V001", Message::get("payment_method")));
        //            }
        //            if (empty($cart->shipping_method)) {
        //                $this->response->errorBadRequest(Message::get("V001", Message::get("shipping_method")));
        //            }
        //            if (empty($cart->shipping_address_id)) {
        //                $this->response->errorBadRequest(Message::get("V001", Message::get("shipping_address")));
        //            }
        //        }

        $totalPrice    = 0;
        $originalPrice = 0;
        $subTotalPrice = 0;
        $totalTmp = 0;
        //        $freeShip      = false;
        $customerPoint = null;
        $date = date('Y-m-d H:i:s', time());
        $promotion_flashsale = PromotionProgram::model()->where('promotion_type', 'FLASH_SALE')
            ->where('start_date', "<=", $date)->where('end_date', '>=', $date)
            ->where('status', 1)->where('deleted', 0)->where('company_id', TM::getCurrentCompanyId())->get();
        foreach ($cart->details as $detail) {
            //Check Qty Flashsale
            $limit_qty_flash_sale = null;
            $min_qty_flash_sale = null;
            foreach ($promotion_flashsale ?? [] as $flashsale) {
                if ($flashsale->act_type == 'sale_off_on_products') {
                    if (!empty($flashsale->act_products) && $flashsale->act_products != "[]") {
                        $prod_promo = json_decode($flashsale->act_products);
                        $act_products = array_pluck(json_decode($flashsale->act_products), 'product_code');
                        $check_prod = array_search($detail->product_code, $act_products);

                        if(is_numeric($check_prod)){
                            if(!empty($flashsale->limit_qty_flash_sale) && $detail->quantity > $flashsale->limit_qty_flash_sale){
                                throw new \Exception("Tổng số lượng được mua tối đa là $flashsale->limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.",400);
                            }

                            if (empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale <= 0 && !empty($prod_promo[$check_prod]->limit_qty_flash_sale) && $prod_promo[$check_prod]->limit_qty_flash_sale > 0) {
                                if ($detail->quantity > $prod_promo[$check_prod]->limit_qty_flash_sale) {
                                    $limit_qty_flash_sale = $prod_promo[$check_prod]->limit_qty_flash_sale ?? 0;
                                    throw new \Exception("Tổng số lượng được mua tối đa là $limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                }
                            }
                            if (empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale <= 0 && !empty($prod_promo[$check_prod]->min_qty_sale) && $prod_promo[$check_prod]->min_qty_sale > 0) {
                                if ($detail->quantity < $prod_promo[$check_prod]->min_qty_sale) {
                                    $min_qty_sale = $prod_promo[$check_prod]->min_qty_sale ?? 0;
                                    throw new \Exception("Tổng số lượng được mua tối thiểu là $min_qty_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                }
                            }
                        }
                    }
                }
                if ($flashsale->act_type == 'sale_off_on_categories') {
                    if (!empty($flashsale->act_categories) && $flashsale->act_categories != "[]") {
                        $Category = !empty($flashsale->act_categories) ? array_pluck(json_decode($flashsale->act_categories), 'category_id') : [];
                        foreach (json_decode($flashsale->act_categories) as $act_category) {
                            $check = array_intersect($Category, explode(',', $detail->product_category));
                            if (!empty($check)) {
                                if(!empty($flashsale->limit_qty_flash_sale) && $detail->quantity > $act_category->limit_qty_flash_sale){
                                    throw new \Exception("Tổng số lượng được mua tối đa là $act_category->limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.",400);
                                }

                                if (empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale <= 0 && !empty($act_category->limit_qty_flash_sale) && $act_category->limit_qty_flash_sale > 0) {
                                    if ($detail->quantity > $act_category->limit_qty_flash_sale) {
                                        $limit_qty_flash_sale = $prod_promo[$check_prod]->limit_qty_flash_sale ?? 0;
                                        throw new \Exception("Tổng số lượng được mua tối đa là $limit_qty_flash_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                    }
                                }
                                if (empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale <= 0 && !empty($act_category->min_qty_sale) && $act_category->min_qty_sale > 0) {
                                    if ($detail->quantity < $act_category->min_qty_sale) {
                                        $min_qty_sale = $prod_promo[$check_prod]->min_qty_sale ?? 0;
                                        throw new \Exception("Tổng số lượng được mua tối thiểu là $min_qty_sale trên toàn bộ sản phẩm trong giỏ hàng đối với chương trình $flashsale->name. Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng.");
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            }

            $priceProduct = Arr::get($detail->product, 'priceDetail.price', Arr::get($detail->product, 'price', 0));
            $originalPrice += $priceProduct;

            $totalTmp += $priceProduct * $detail->quantity;
        }

        $settingMinAmtSet =  Setting::model()->where(['code' => 'LIMITCARTTOTAL', 'company_id' => TM::getCurrentCompanyId()])->value('data');
        if (!empty($settingMinAmtSet)){
            $settingMinAmt = array_pluck(json_decode($settingMinAmtSet), null,'key');
            $settingMinAmtStatus = $settingMinAmt['STATUS']->value ?? 0;
            if ($settingMinAmtStatus == 1){
                $settingMinAmt = $settingMinAmt['MINCARTTOTAL']->value ?? 0;
                if ($settingMinAmt != 0 && $totalTmp < $settingMinAmt){
                    throw new \Exception('Tổng tiền đơn hàng không đúng!');
                }
            }

        }

        foreach ($cart->total_info ?? [] as $key => $item) {
            switch ($item['code']) {
                case 'sub_total':
                    $subTotalPrice = $item['value'];
                    break;
                case 'total':
                    $totalPrice = $item['value'];
                    break;
                default:
                    //                    if (!empty($item['act_type']) && $item['act_type'] == 'free_shipping') {
                    //                        $freeShip = true;
                    //                    }

                    if (!empty($item['act_type']) && $item['act_type'] == 'accumulate_point') {
                        $customerPoint = $item['value'];
                    }
                    break;
            }
        }
        try {
            DB::beginTransaction();
            $orderStatus = OrderStatus::model()->where([
                'company_id' => TM::getCurrentCompanyId(),
                'code'       => ORDER_STATUS_NEW,
            ])->first();
            if (empty($orderStatus)) {
                throw new \Exception(Message::get("V002", "status"));
            }
            $seller_id         = null;
            $settingAutoSeller = Setting::model()->select('data')->where(['code' => 'CRMAUTO', 'company_id' => TM::getCurrentCompanyId()])->first();
            if (!empty($settingAutoSeller) && !empty(json_decode($settingAutoSeller['data'])[0]->value) && json_decode($settingAutoSeller['data'])[0]->value == 1) {
                $seller_id = $this->getAutoSeller(TM::getCurrentCompanyId());
            }
            $autoCode = $this->getAutoOrderCode();
            $lat_long = $cart->ship_address_latlong ? explode(",", $cart->ship_address_latlong) : null;

            // $userModel   = new UserModel();
            // $distributor = $userModel->findDistributor2($userId, $city->id, $district->id, $ward->id);
            // if (!empty($distributor)) {
            //     $distributorToUser = User::model()
            //         ->where([
            //             'code'       => $distributor['code'],
            //             'company_id' => TM::getCurrentCompanyId(),
            //             'store_id'   => TM::getCurrentStoreId(),
            //         ])->first();
            // }
            // if (!empty($cart->voucher_code)) {
            //     $coupon           = Coupon::model()->where('code', $cart->voucher_code)->first();
            //     $coupon->discount = $coupon->discount - $cart->voucher_value_use ?? 0;
            //     $coupon->update();
            // }
            //            if (!empty($cart->coupon_code)) {
            //                $detail = RotationDetail::join('rotation_results as rr', 'rr.code', 'rotation_details.rotation_code')
            //                    ->join('coupons', 'coupons.id', 'rr.coupon_id')
            //                    ->where('coupons.code', $cart->coupon_code)
            //                    ->where('user_id', TM::getCurrentUserId())->select('rotation_details.id')->delete();
            //            }

            $customer        = User::find($userId);
            $customer->point -= ($cart->point ?? 0);
            $customer->update();

            if ($input['city_code']) {
                $city_code_before      = $input['city_code'];
                $city_code_before_name = City::where('code', $city_code_before)->first();
                foreach ($cart->details as $detail) {
                    if (!$saleArea = $detail->product->sale_area) {
                        continue;
                    }
                    $saleArea = json_decode($saleArea, true);
                    $key      = array_search($city_code_before, array_column($saleArea, 'code'));
                    if (!is_numeric($key)) {
                        return $this->responseError("Sản phẩm [$detail->product_name] không được giao ở [$city_code_before_name->full_name]. Quý khách vui lòng gỡ bỏ sản phẩm này khỏi đơn hàng trước khi thanh toán. Mong quý khách thông cảm!");
                    }
                }
            }
        
            $order = Order::create([
                'code'                           => $autoCode,
                'order_type'                     => $input['order_type'] ?? null,
                'status'                         => ORDER_STATUS_NEW,
                'status_text'                    => $orderStatus->name,
                'customer_id'                    => $customer->id ?? null,
                'customer_point'                 => $customerPoint,
                'customer_name'                  => $customer->name,
                'customer_code'                  => $customer->code,
                'customer_phone'                 => $customer->phone,
                'customer_lat'                   => Arr::get($input, 'customer_lat', $cart->customer_lat),
                'customer_long'                  => Arr::get($input, 'customer_long', $cart->customer_long),
                'customer_postcode'              => Arr::get($input, 'customer_postcode', $cart->customer_postcode),
                'session_id'                     => $session_id ?? null,
                'note'                           => $cart->description,
                'phone'                          => $input['phone'],
                'street_address'                 => $input['street_address'] ?? null,
                'shipping_address_phone'         => $input['phone'],
                'shipping_address_full_name'     => $input['full_name'],
                'shipping_address'               => $cart->address,
                'shipping_address_id'            => $cart->shipping_address_id ?? null,
                'shipping_address_ward_code'     => $ward->code ?? null,
                'shipping_address_ward_type'     => $ward->type ?? null,
                'shipping_address_ward_name'     => $ward_name ?? null,
                'shipping_address_district_code' => $district->code ?? null,
                'shipping_address_district_type' => $district->type ?? null,
                'shipping_address_district_name' => $district_name ?? null,
                'shipping_address_city_code'     => $city->code ?? null,
                'shipping_address_city_type'     => $city->type ?? null,
                'shipping_address_city_name'     => $city_name ?? null,
                'payment_method'                 => $input['payment_method'] ?? $cart->payment_method,
                'shipping_method'                => $input['shipping_method'] ?? null,
                'shipping_method_code'           => $input['shipping_code'] ?? $cart->shipping_method_code,
                'shipping_method_name'           => $input['shipping_name'] ?? $cart->shipping_method_name,
                'shipping_service'               => $input['shipping_service'] ?? $cart->shipping_service,
                'shipping_service_name'          => $input['shipping_service_name'] ?? $cart->service_name,
                'shipping_note'                  => $input['shipping_note'] ?? $cart->shipping_note,
                'extra_service'                  => $input['extra_service'] ?? $cart->extra_service,
                'ship_fee'                       => $cart->ship_fee_down ?? 0,
                //                'ship_fee'                       => $input['ship_fee'] ?? $cart->ship_fee_down,
                'ship_fee_start'                 => $input['ship_fee_start'] ?? 0,
                'ship_fee_real'                  => $cart->ship_fee_real ?? 0,
                'estimated_deliver_time'         => $input['estimated_deliver_time'] ?? $cart->estimated_deliver_time,
                'lading_method'                  => $input['lading_method'] ?? $cart->lading_method,
                'total_weight'                   => $cart->total_weight ?? 0,
                'intersection_distance'          => $cart->intersection_distance ?? 0,
                'invoice_city_code'              => $input['invoice_city_code'] ?? null,
                'invoice_city_name'              => $input['invoice_city_name'] ?? null,
                'invoice_district_code'          => $input['invoice_district_code'] ?? null,
                'invoice_district_name'          => $input['invoice_district_name'] ?? null,
                'invoice_ward_code'              => $input['invoice_ward_code'] ?? null,
                'invoice_ward_name'              => $input['invoice_ward_name'] ?? null,
                'invoice_street_address'         => $input['invoice_street_address'] ?? null,
                'invoice_company_name'           => $input['invoice_company_name'] ?? null,
                'invoice_company_email'          => $input['invoice_company_email'] ?? null,
                'invoice_tax'                    => $input['invoice_tax'] ?? null,
                'invoice_company_address'        => $input['invoice_company_address'] ?? null,
                'created_date'                   => $cart->created_at,
                'delivery_time'                  => $cart->receiving_time,
                'latlong'                        => $cart->ship_address_latlong,
                'lat'                            => $lat_long[0] ?? 0,
                'long'                           => $lat_long[1] ?? 0,
                'coupon_code'                    => $cart->coupon_code ?? null,
                'coupon_discount_code'           => $cart->coupon_discount_code ?? null,
                'coupon_delivery_code'           => $cart->coupon_delivery_code ?? null,
                'delivery_discount_code'         => $cart->delivery_discount_code ?? null,
                'voucher_code'                   => $cart->voucher_code ?? null,
                'voucher_discount_code'          => $cart->voucher_discount_code ?? null,
                'point'                          => $cart->point,
                'ex_change_point'                => $cart->ex_change_point,
                'total_discount'                 => 0,
                'original_price'                 => $originalPrice,
                'total_price'                    => $totalPrice,
                'sub_total_price'                => $subTotalPrice,
                'saving'                         => $cart->saving ?? null,
                'access_trade_id'                => $cart->access_trade_id ?? null,
                'access_trade_click_id'          => $cart->access_trade_click_id ?? null,
                'order_source'                   => $cart->order_source ?? null,
                'is_freeship'                    => $cart->is_freeship ?? 0,
                'order_channel'                  => $input['order_channel'],
                'store_id'                       => TM::getCurrentStoreId(),
                'distributor_id'                 => Arr::get($input, 'distributor_id', $cart->distributor_id),
                'distributor_code'               => Arr::get($input, 'distributor_code', $cart->distributor_code),
                'distributor_name'               => Arr::get($input, 'distributor_name', $cart->distributor_name),
                'distributor_email'              => Arr::get($input, 'distributor_email', $cart->distributor_email),
                'distributor_phone'              => Arr::get($input, 'distributor_phone', $cart->distributor_phone),
                'distributor_postcode'           => Arr::get($input, 'distributor_postcode', $cart->distributor_postcode),
                'distributor_lat'                => Arr::get($input, 'distributor_lat', $cart->distributor_lat),
                'distributor_long'               => Arr::get($input, 'distributor_long', $cart->distributor_long),
                'is_active'                      => 1,
                'seller_id'                      => !empty($seller_id) ? $seller_id->id : null,
                'seller_code'                    => !empty($seller_id) ? $seller_id->code : null,
                'seller_name'                    => !empty($seller_id) ? $seller_id->name : null,
                'leader_id'                      => !empty($seller_id) ? $seller_id->parent_id : null,
                'company_id'                     => TM::getCurrentCompanyId(),
                'total_info'                     => json_encode($cart->total_info),
                'free_item'                      => !empty($cart->free_item) ? json_encode($cart->free_item) : null,
                'transfer_confirmation'          => $input['payment_method'] == 'bank_transfer' ? 0 : 1,
                'outvat'                         => !empty($input['invoice_company_name']) ? 1 : 0,
                'qr_scan'                        => $cart->qr_scan ?? 0,
                'status_crm'                     => ORDER_STATUS_CRM_PENDING,
                'cart_id'                        => $cart->id
            ]);
            foreach ($cart->details as $detail) {
                OrderDetail::create([
                    'order_id'           => $order->id,
                    'product_id'         => $detail->product_id,
                    'product_code'       => $detail->product_code,
                    'product_name'       => $detail->product_name,
                    'product_category'   => $detail->product_category,
                    'qty'                => $detail->quantity,
                    'qty_sale'           => $detail->qty_sale_re ?? null,
                    'price'              => $detail->price,
                    'discount'           => !empty($detail->special_percentage) && $detail->special_percentage > 0 && $detail->promotion_price <= 0 ? $detail->price * ($detail->special_percentage / 100) : $detail->promotion_price,
                    'special_percentage' => empty($detail->special_percentage) && !empty($detail->promotion_price) ? round(($detail->promotion_price / $detail->price) * 100) : round($detail->special_percentage),
                    'real_price'         => $detail->price,
                    'price_down'         => 0,
                    'total'              => $detail->total,
                    'note'               => $detail->note,
                    'status'             => ORDER_HISTORY_STATUS_PENDING,
                    'is_active'          => 1,
                    'combo_price_from'   => Arr::get($detail, 'getProduct.getProductCombo.price', 0)
                ]);
            }
            $inventory = WarehouseDetail::model()->select('quantity')->where([
                'product_id' => $detail->product_id,
                // 'warehouse_id' => $input_detail['warehouse_id'],
                // 'unit_id'      => $detail->unit_id,
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            WarehouseDetail::model()->where([
                'product_id' => $detail->product_id,
                // 'warehouse_id' => $input_detail['warehouse_id'],
                // 'unit_id'      => $detail->unit_id,
                'company_id' => TM::getCurrentCompanyId(),
            ])->update(['quantity' => $inventory->quantity - $detail->quantity]);
            if (!empty($cart->distributor_code)) {
                $distributor = User::model()->where('code', $cart->distributor_code)->first();
                if (!empty($distributor->qty_max_day)) {
                    $countOrderInDistributor           = Order::model()->where('distributor_code', $cart->distributor_code)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->count();
                    $distributor->qty_remaining_single = $distributor->qty_max_day - $countOrderInDistributor;
                    $distributor->update();
                }
            }
            // Update Sold Count
            $this->model->updateProductSold($order);

            if (!empty($cart->promotion_info)) {
                foreach ($cart->promotion_info as $promotion) {
                    $this->model->createPromotionTotal($promotion, $order, $cart);
                }
            }
            //Save Info Customer
            $cusInfo = CustomerInformation::where([
                'phone'    => $customer->phone,
                'store_id' => TM::getCurrentStoreId()
            ])->first();
            if ($cusInfo) {
                $cusInfo->name           = $customer->name ?? null;
                $cusInfo->phone          = $customer->phone ?? null;
                $cusInfo->email          = $customer->email ?? null;
                $cusInfo->address        = $input['street_address'] ?? null;
                $cusInfo->city_code      = $input['city_code'] ?? null;
                $cusInfo->store_id       = TM::getCurrentStoreId();
                $cusInfo->district_code  = $input['district_code'] ?? null;
                $cusInfo->ward_code      = $input['ward_code'] ?? null;
                $cusInfo->full_address   = $input['shipping_address'] ?? null;
                $cusInfo->street_address = $input['street_address'] ?? null;
                $cusInfo->note           = $input['note'] ?? null;
                $cusInfo->gender         = $input['gender'] ?? null;
                $cusInfo->update();
            } else {
                CustomerInformation::insert(
                    [
                        'name'           => $input['full_name'] ?? null,
                        'phone'          => $input['phone'] ?? null,
                        'email'          => $input['email'] ?? null,
                        'address'        => $input['street_address'] ?? null,
                        'city_code'      => $input['city_code'] ?? null,
                        'store_id'       => TM::getCurrentStoreId(),
                        'district_code'  => $input['district_code'] ?? null,
                        'ward_code'      => $input['ward_code'] ?? null,
                        'full_address'   => $input['shipping_address'] ?? null,
                        'street_address' => $input['street_address'] ?? null,
                        'note'           => $input['note'] ?? null,
                        'gender'         => $input['gender'] ?? null,
                    ]
                );
            }

            $couponHistory = CouponHistory::model()->where('order_id', $order->id)->first();
            if (!empty($cart->coupon_discount_code)) {

                $coupon_check = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                    ->where('coupon_codes.code', $cart->coupon_discount_code)->first();

                $coupon_order_used   = CouponCodes::where('code', $cart->coupon_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->coupon_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);

                $count_history = CouponHistory::model()->where('coupon_discount_code', $cart->coupon_discount_code)->count();
                if (($count_history + 1) >= $coupon_check->uses_total) {
                    DB::table('coupon_codes')->where('code', $cart->coupon_discount_code)->update(['is_active' => '1']);
                }

                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->coupon_name;
                $couponHistory->coupon_discount_code = $cart->coupon_discount_code;
                $couponHistory->coupon_code          = $cart->coupon_code;
                $couponHistory->total_discount       = $cart->coupon_price > $subTotalPrice ? $subTotalPrice : $cart->coupon_price;
                $couponHistory->save();
            }
            if (!empty($cart->delivery_discount_code)) {

                $coupon_check = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                    ->where('coupon_codes.code', $cart->delivery_discount_code)->first();

                $coupon_order_used   = CouponCodes::where('code', $cart->delivery_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->delivery_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);

                $count_history = CouponHistory::model()->where('coupon_discount_code', $cart->delivery_discount_code)->count();
                if (($count_history + 1) >= $coupon_check->uses_total) {
                    DB::table('coupon_codes')->where('code', $cart->delivery_discount_code)->update(['is_active' => '1']);
                }

                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->coupon_delivery_name;
                $couponHistory->coupon_discount_code = $cart->delivery_discount_code;
                $couponHistory->coupon_code          = $cart->coupon_delivery_code;
                $couponHistory->total_discount       = $cart->coupon_delivery_price;
                $couponHistory->save();
            }

            if (!empty($cart->voucher_discount_code)) {
                $voucher = DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->first();
                if (($voucher->discount - $subTotalPrice) <= 0) {
                    DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->update(['is_active' => '1']);
                }
                DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->update(['discount' => $voucher->discount - $cart->voucher_value_use]);
                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->voucher_title;
                $couponHistory->coupon_discount_code = $cart->voucher_discount_code;
                $couponHistory->coupon_code          = $cart->voucher_code;
                $couponHistory->total_discount       = $cart->voucher_value_use;
                $couponHistory->save();

                $coupon_order_used   = CouponCodes::where('code', $cart->voucher_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->voucher_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);
            }
            $paramQuoteGrab    = $cart->log_quote_grab ?? null;
            $responseQuoteGrab = $cart->log_quote_response_grab ?? null;
            try {
                $this->writeLogGrab($order->code, $paramQuoteGrab, $responseQuoteGrab);
            } catch (\Exception $exception) {

            }
            $cart->details->each(function ($detail) {
                $detail->delete();
            });
            $cart->delete();

            // //history coupon
            // if (!empty($order->coupon_code)) {
            //     $couponHistory              = new CouponHistory();
            //     $couponHistory->order_id    = $order->id;
            //     $couponHistory->user_id     = $order->customer_id;
            //     $couponHistory->coupon_code = $order->coupon_code;
            //     $couponHistory->save();
            // }

            //            // Send Email
            //    $company           = Company::model()->where('id', TM::getCurrentCompanyId())->first();
            //    $order             = Order::with(['customer', 'distributor', 'store', 'details.product.unit'])->where('id', $order->id)->first();
            //    $distributor_email = $distributor->email ?? null;
            //    $customer_email    = !empty($input['email']) ? $input['email'] : ($order->customer->email ?? null);
            //    $store_email       = $order->store->email_notify;
            //
            //            if (!empty($customer_email)) {
            //                dispatch(new SendCustomerMailNewOrderJob($customer_email, [
            //                    'logo'         => $company->avatar,
            //                    'support'      => $company->email,
            //                    'company_name' => $company->name,
            //                    'order'        => $order,
            //                ]));
            //            }
            //
            //            if (!empty($store_email)) {
            //                dispatch(new SendStoreMailNewOrderJob($store_email, [
            //                    'logo'         => $company->avatar,
            //                    'support'      => $company->email,
            //                    'company_name' => $company->name,
            //                    'order'        => $order,
            //                ]));
            //            }
            //
            // if (!empty($distributor_email)) {
            //    $this->dispatch(new SendHUBMailNewOrderJob($distributor_email, [
            //        'logo'         => $company->avatar,
            //        'support'      => $company->email,
            //        'company_name' => $company->name,
            //        'order'        => $order,
            //        'link_to'      => TM::urlBase("/user/order/" . $order->id),
            //    ]));
            // }
            //            $this->sendNotifyConfirmOrder($request, $order);

            DB::commit();
            #CREATE[ACCESSTRADE]
            try {
                $accesstrade_id = $cart->access_trade_id;
                $click_id       = $cart->access_trade_click_id;
                Accesstrade::create($order, $accesstrade_id, $click_id);
            } catch (\Exception $e) {
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message']);
        }

        return [
            'order_id'       => $order->id,
            'order_code'     => $order->code,
            'payment_method' => $order->payment_method,
            'lat'            => $order->lat,
            'long'           => $order->long,
            'total'          => $totalPrice,
        ];
    }

    private function clientConfirmOrder(Request $request, User $user)
    {  
        $input      = $request->all();
        $session_id = $request->input('session_id');
        Session::where('session_id', $session_id)->update(['phone' => $input['phone']]);
        try {
            $cart = Cart::with('details.product.priceDetail')->where(['session_id' => $session_id])->first();
            if (empty($cart)) {
                return $this->responseError(Message::get("V003", Message::get('carts')));
            }
            $orderStatus = OrderStatus::model()->where([
                'company_id' => $user->company_id,
                'code'       => ORDER_STATUS_NEW,
            ])->first();

            if (empty($orderStatus)) {
                return $this->response->errorBadRequest(Message::get("V002", "status"));
            }

            $totalPrice = $originalPrice = $subTotalPrice = 0;
            //            $freeShip      = false;
            $customerPoint     = null;
            $seller_id         = null;
            $settingAutoSeller = Setting::model()->select('data')->where(['code' => 'CRMAUTO', 'company_id' => TM::getCurrentCompanyId()])->first();
            if (!empty($settingAutoSeller) && !empty(json_decode($settingAutoSeller['data'])[0]->value) && json_decode($settingAutoSeller['data'])[0]->value == 1) {
                $seller_id = $this->getAutoSeller(TM::getCurrentCompanyId());
            }
            foreach ($cart->details as $detail) {
                $originalPrice += Arr::get(
                    $detail->product,
                    'priceDetail.price',
                    Arr::get($detail->product, 'price', 0)
                );
            }
            foreach ($cart->total_info as $item) {
                switch ($item['code']) {
                    case 'sub_total':
                        $subTotalPrice = $item['value'];
                        break;
                    case 'total':
                        $totalPrice = $item['value'];
                        break;
                    default:
                        if (!empty($item['act_type'])) {
                            //                            if ($item['act_type'] == 'free_shipping') {
                            //                                $freeShip = true;
                            //                            }

                            if ($item['act_type'] == 'accumulate_point') {
                                $customerPoint = $item['value'];
                            }
                        }
                        break;
                }
            }

            $city                      = City::where('code', $input['city_code'])->first();
            $city_name                 = Arr::get($city, 'type') . " " . Arr::get($city, 'name');
            $district                  = District::where('code', $input['district_code'])->first();
            $district_name             = Arr::get($district, 'type') . " " . Arr::get($district, 'name');
            $ward                      = Ward::where('code', $input['ward_code'])->first();
            $ward_name                 = Arr::get($ward, 'type') . " " . Arr::get($ward, 'name');
            $input['shipping_address'] = "{$input['street_address']}, {$ward_name}, {$district_name}, {$city_name}";
            // $input['shipping_address'] = "{$input['street_address']} - {$ward_name} - {$district_name} - {$city_name}";
            $autoCode = $this->getAutoOrderCode();
            $lat_long = $cart->ship_address_latlong ? explode(",", $cart->ship_address_latlong) : null;
            //            $userModel                 = new UserModel();
            //            $distributor               = $userModel->findDistributor2($user->id, $city->id, $district->id, $ward->id);
            //            $distributorToUser         = null;
            //
            //            if ($distributor) {
            //                $distributorToUser = User::model()
            //                    ->where([
            //                        'code'       => $distributor['code'],
            //                        'company_id' => $user->company_id,
            //                        'store_id'   => $user->store_id,
            //                    ])->first();
            //            }
            $distributorToUser = [];
            if (!empty($input['distributor_code']))
                $distributorToUser = User::model()
                    ->where([
                        'code'       => $input['distributor_code'],
                        'company_id' => $user->company_id,
                        'store_id'   => $user->store_id,
                    ])->first();
            
            $order = Order::create([
                'code'                           => $autoCode,
                //                'order_type'                     => ORDER_TYPE_GROCERY,
                'order_type'                     => ORDER_TYPE_GUEST,
                'status'                         => ORDER_STATUS_NEW,
                'status_text'                    => $orderStatus->name,
                'customer_id'                    => $user->id,
                'customer_name'                  => $user->name,
                'customer_code'                  => $user->code,
                'customer_email'                 => $user->email ?? null,
                'customer_phone'                 => $user->phone,
                'customer_lat'                   => Arr::get($input, 'customer_lat', $cart->customer_lat),
                'customer_long'                  => Arr::get($input, 'customer_long', $cart->customer_long),
                'customer_postcode'              => Arr::get($input, 'customer_postcode', $cart->customer_postcode),
                'session_id'                     => $session_id ?? null,
                'customer_point'                 => $customerPoint,
                'note'                           => $input['note'] ?? null,
                'phone'                          => $input['phone'],
                'street_address'                 => $input['street_address'] ?? null,
                'shipping_address'               => $input['shipping_address'] ?? null,
                'shipping_address_id'            => $cart->shipping_address_id,
                'shipping_address_ward_code'     => $ward->code ?? null,
                'shipping_address_ward_type'     => $ward->type ?? null,
                'shipping_address_ward_name'     => $ward_name ?? null,
                'shipping_address_district_code' => $district->code ?? null,
                'shipping_address_district_type' => $district->type ?? null,
                'shipping_address_district_name' => $district_name ?? null,
                'shipping_address_city_code'     => $city->code ?? null,
                'shipping_address_city_type'     => $city->type ?? null,
                'shipping_address_city_name'     => $city_name ?? null,
                'payment_method'                 => $input['payment_method'] ?? $cart->payment_method,
                'shipping_method'                => $input['shipping_method'] ?? $cart->shipping_method,
                'shipping_method_code'           => $input['shipping_code'] ?? $cart->shipping_method_code,
                'shipping_method_name'           => $input['shipping_name'] ?? $cart->shipping_method_name,
                'shipping_service'               => $input['shipping_service'] ?? $cart->shipping_service,
                'shipping_service_name'          => $input['shipping_service_name'] ?? $cart->service_name,
                'shipping_note'                  => $input['shipping_note'] ?? $cart->shipping_note,
                'extra_service'                  => $input['extra_service'] ?? $cart->extra_service,
                'saving'                         => $cart->saving ?? null,
                'ship_fee'                       => $input['ship_fee'] ?? $cart->ship_fee,
                'ship_fee_start'                 => $input['ship_fee_start'] ?? 0,
                // 'ship_fee_real'                  => $input['ship_fee_start'] ?? 0,
                'estimated_deliver_time'         => $input['estimated_deliver_time'] ?? $cart->estimated_deliver_time,
                'lading_method'                  => $input['lading_method'] ?? $cart->lading_method,
                'total_weight'                   => $cart->total_weight ?? 0,
                'intersection_distance'          => $cart->intersection_distance ?? 0,
                'invoice_city_code'              => $input['invoice_city_code'] ?? null,
                'invoice_city_name'              => $input['invoice_city_name'] ?? null,
                'invoice_district_code'          => $input['invoice_district_code'] ?? null,
                'invoice_district_name'          => $input['invoice_district_name'] ?? null,
                'invoice_ward_code'              => $input['invoice_ward_code'] ?? null,
                'invoice_ward_name'              => $input['invoice_ward_name'] ?? null,
                'invoice_street_address'         => $input['invoice_street_address'] ?? null,
                'invoice_company_name'           => $input['invoice_company_name'] ?? null,
                'invoice_company_email'          => $input['invoice_company_email'] ?? null,
                'invoice_tax'                    => $input['invoice_tax'] ?? null,
                'invoice_company_address'        => $input['invoice_company_address'] ?? null,
                'created_date'                   => $cart->created_at,
                'delivery_time'                  => $cart->receiving_time,
                'access_trade_id'                => $cart->access_trade_id ?? null,
                'access_trade_click_id'          => $cart->access_trade_click_id ?? null,
                'order_source'                   => $cart->order_source ?? null,
                'latlong'                        => $cart->ship_address_latlong,
                'lat'                            => $lat_long[0] ?? 0,
                'long'                           => $lat_long[1] ?? 0,
                'coupon_code'                    => $cart->coupon_code ?? null,
                'coupon_discount_code'           => $cart->coupon_discount_code ?? null,
                'coupon_delivery_code'           => $cart->coupon_delivery_code ?? null,
                'delivery_discount_code'         => $cart->delivery_discount_code ?? null,
                'voucher_code'                   => $cart->voucher_code ?? null,
                'voucher_discount_code'          => $cart->voucher_discount_code ?? null,
                'total_discount'                 => 0,
                'original_price'                 => $originalPrice,
                'total_price'                    => $totalPrice,
                'sub_total_price'                => $subTotalPrice,
                'is_freeship'                    => $cart->is_freeship ?? 0,
                'ship_fee_real'                  => $cart->ship_fee_real ?? 0,
                'order_channel'                  => $input['order_channel'],
                'distributor_id'                 => Arr::get($input, 'distributor_id', $cart->distributor_id),
                'distributor_code'               => Arr::get($input, 'distributor_code', $cart->distributor_code),
                'distributor_name'               => Arr::get($input, 'distributor_name', $cart->distributor_name),
                'distributor_email'              => Arr::get($input, 'distributor_email', $cart->distributor_email),
                'distributor_phone'              => Arr::get($input, 'distributor_phone', $cart->distributor_phone),
                'distributor_lat'                => Arr::get($input, 'distributor_lat', $cart->distributor_lat),
                'distributor_long'               => Arr::get($input, 'distributor_long', $cart->distributor_long),
                'distributor_postcode'           => Arr::get($input, 'distributor_postcode', $cart->distributor_postcode),
                'store_id'                       => $user->store_id,
                'is_active'                      => 1,
                //                'seller_id'                      => Arr::get($input, 'seller_id', null),
                //                'seller_code'                    => Arr::get($input, 'seller_code', null),
                //                'seller_name'                    => Arr::get($input, 'seller_name', null),
                'seller_id'                      => !empty($seller_id) ? $seller_id->id : null,
                'seller_code'                    => !empty($seller_id) ? $seller_id->code : null,
                'seller_name'                    => !empty($seller_id) ? $seller_id->name : null,
                'leader_id'                      => !empty($seller_id) ? $seller_id->parent_id : null,
                'total_info'                     => json_encode($cart->total_info),
                'free_item'                      => !empty($cart->free_item) ? ($cart->free_item) : null, 'outvat' => !empty($input['invoice_company_name']) ? 1 : 0,
                'qr_scan'                        => $cart->qr_scan ?? 0,
                'transfer_confirmation'          => $input['payment_method'] == 'bank_transfer' ? 0 : 1,
                'status_crm'                     => ORDER_STATUS_CRM_PENDING
            ]);

            foreach ($cart->details as $detail) {
                OrderDetail::create([
                    'order_id'           => $order->id,
                    'product_id'         => $detail->product_id,
                    'product_code'       => $detail->product_code,
                    'product_name'       => $detail->product_name,
                    'product_category'   => $detail->product_category,
                    'qty'                => $detail->quantity,
                    'qty_sale'           => $detail->qty_sale_re ?? null,
                    'price'              => $detail->price,
                    'discount'           => !empty($detail->special_percentage) && $detail->special_percentage > 0 && $detail->promotion_price <= 0 ? $detail->price * ($detail->special_percentage / 100) : $detail->promotion_price,
                    'special_percentage' => empty($detail->special_percentage) && !empty($detail->promotion_price) ? round(($detail->promotion_price / $detail->price) * 100) : round($detail->special_percentage),
                    'real_price'         => $detail->price,
                    'price_down'         => 0,
                    'total'              => $detail->total,
                    'note'               => $detail->note,
                    'status'             => ORDER_HISTORY_STATUS_PENDING,
                    'is_active'          => 1,
                ]);
            }
            if (!empty($cart->distributor_code)) {
                $distributor = User::model()->where('code', $cart->distributor_code)->first();
                if (!empty($distributor->qty_max_day)) {
                    $countOrderInDistributor           = Order::model()->where('distributor_code', $cart->distributor_code)->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])->count();
                    $distributor->qty_remaining_single = $distributor->qty_max_day - $countOrderInDistributor;
                    $distributor->update();
                }
            }
            // Update Sold Count
            $this->model->updateProductSold($order);

            if (!empty($cart->promotion_info) && $cart->promotion_info != "[]") {
                foreach ($cart->promotion_info as $promotion) {
                    $this->model->createPromotionTotal($promotion, $order, $cart);
                }
            }

            //Save Info Customer
            $cusInfo = CustomerInformation::where([
                'phone'    => "{$input['phone']}",
                'store_id' => $user->store_id
            ])->first();
            if ($cusInfo) {
                $cusInfo->name          = $input['full_name'] ?? null;
                $cusInfo->email         = $input['email'] ?? null;
                $cusInfo->phone         = $input['phone'] ?? null;
                $cusInfo->address       = $input['street_address'] ?? null;
                $cusInfo->city_code     = $input['city_code'] ?? null;
                $cusInfo->store_id      = $user->store_id ?? null;
                $cusInfo->district_code = $input['district_code'] ?? null;
                $cusInfo->ward_code     = $input['ward_code'] ?? null;
                $cusInfo->full_address  = $input['shipping_address'] ?? null;
                $cusInfo->update();
            } else {
                CustomerInformation::insert(
                    [
                        'name'          => $input['full_name'] ?? null,
                        'email'         => $input['email'] ?? null,
                        'phone'         => $input['phone'] ?? null,
                        'address'       => $input['street_address'] ?? null,
                        'city_code'     => $input['city_code'] ?? null,
                        'store_id'      => $user->store_id ?? null,
                        'district_code' => $input['district_code'] ?? null,
                        'ward_code'     => $input['ward_code'] ?? null,
                        'full_address'  => $input['shipping_address'] ?? null,
                    ]
                );
            }

            // Update users
            $updateUser = User::model()->where([
                'phone'      => $input['phone'],
                'store_id'   => $user->store_id,
                'company_id' => $user->company_id
            ])->first();

            if ($updateUser) {
                $last_name  = explode(" ", $input['full_name']);
                $last_name  = array_pop($last_name);
                $first_name = explode(" ", $input['full_name']);
                $first_name = array_shift($first_name);
                $short_name = "{$first_name} {$last_name}";
                if (!empty($input['email'])) {
                    if ($updateUser->email != $input['email']) {
                        $chekEmail = User::model()->where([
                            'email'    => $input['email'],
                            'store_id' => $user->store_id
                        ])->first();
                        if (!empty($chekEmail)) {
                            throw new \Exception(Message::get("V007", "Enail: #{$input['email']}"));
                        }
                    }
                }
                $updateUser->name = $input['full_name'] ?? $updateUser->name;
                // $updateUser->email = $input['email'] ?? $updateUser->email;

                //Send Sms code
                $codeSMS               = mt_rand(100000, 999999);
                $updateUser->password  = password_hash($codeSMS, PASSWORD_BCRYPT);
                $updateUser->is_active = 1;
                $this->sendSMSCode(Message::get('SMS-REGISTER-ORDER', $codeSMS), $user->phone);
                $updateUser->save();

                $profile = Profile::model()->where('user_id', $updateUser->id)->first();
                if (!empty($profile)) {
                    $profile->email         = $input['email'] ?? $profile->email;
                    $profile->first_name    = $first_name ?? $profile->first_name;
                    $profile->last_name     = $last_name ?? $profile->last_name;
                    $profile->short_name    = $short_name ?? $profile->short_name;
                    $profile->full_name     = $input['full_name'] ?? $profile->full_name;
                    $profile->city_code     = $input['city_code'] ?? $profile->city_code;
                    $profile->ward_code     = $input['ward_code'] ?? $profile->ward_code;
                    $profile->district_code = $input['district_code'] ?? $profile->district_code;
                    $profile->address       = $input['address'] ?? $updateUser->profile->address;
                    $profile->save();
                }
                $now = date("Y-m-d H:i:s", time());
                //Create Shipping Address
                ShippingAddress::insert(
                    [
                        'user_id'        => $updateUser->id,
                        'full_name'      => $updateUser->name,
                        'phone'          => $updateUser->phone,
                        'city_code'      => $input['city_code'] ?? null,
                        'district_code'  => $input['district_code'] ?? null,
                        'ward_code'      => $input['ward_code'] ?? null,
                        'street_address' => $input['street_address'] ?? null,
                        'company_id'     => $updateUser->company_id,
                        'store_id'       => $updateUser->store_id ?? null,
                        'is_default'     => 1,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]
                );
            }

            $couponHistory = CouponHistory::model()->where('order_id', $order->id)->first();
            if (!empty($cart->coupon_discount_code)) {

                $coupon_check = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                    ->where('coupon_codes.code', $cart->coupon_discount_code)->first();

                $coupon_order_used   = CouponCodes::where('code', $cart->coupon_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->coupon_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);

                $count_history = CouponHistory::model()->where('coupon_discount_code', $cart->coupon_discount_code)->count();
                if (($count_history + 1) >= $coupon_check->uses_total) {
                    DB::table('coupon_codes')->where('code', $cart->coupon_discount_code)->update(['is_active' => '1', 'order_used' => $coupon_order_used]);
                }

                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->coupon_name;
                $couponHistory->coupon_discount_code = $cart->coupon_discount_code;
                $couponHistory->coupon_code          = $cart->coupon_code;
                $couponHistory->total_discount       = $cart->coupon_price > $subTotalPrice ? $subTotalPrice : $cart->coupon_price;
                $couponHistory->save();
            }
            if (!empty($cart->delivery_discount_code)) {

                $coupon_check = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                    ->where('coupon_codes.code', $cart->delivery_discount_code)->first();

                $coupon_order_used   = CouponCodes::where('code', $cart->delivery_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->delivery_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);

                $count_history = CouponHistory::model()->where('coupon_discount_code', $cart->delivery_discount_code)->count();
                if (($count_history + 1) >= $coupon_check->uses_total) {
                    DB::table('coupon_codes')->where('code', $cart->delivery_discount_code)->update(['is_active' => '1', 'order_used' => $coupon_order_used]);
                }

                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->coupon_delivery_name;
                $couponHistory->coupon_discount_code = $cart->delivery_discount_code;
                $couponHistory->coupon_code          = $cart->coupon_delivery_code;
                $couponHistory->total_discount       = $cart->coupon_delivery_price;
                $couponHistory->save();
            }

            if (!empty($cart->voucher_discount_code)) {
                $voucher = DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->first();
                if (($voucher->discount - $subTotalPrice) <= 0) {
                    DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->update(['is_active' => '1', 'order_used' => $order->id]);
                }
                DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->update(['discount' => $voucher->discount - $cart->voucher_value_use]);
                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->voucher_title;
                $couponHistory->coupon_discount_code = $cart->voucher_discount_code;
                $couponHistory->coupon_code          = $cart->voucher_code;
                $couponHistory->total_discount       = $cart->voucher_value_use;
                $couponHistory->save();

                $coupon_order_used   = CouponCodes::where('code', $cart->voucher_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->voucher_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);
            }


            $cart->details->each(function ($detail) {
                $detail->delete();
            });
            $cart->delete();


            //            // Send Email
            //            $company           = Company::model()->where('id', $user->company_id)->first();
            //            $order             = Order::with(['store', 'customer', 'distributor', 'details.product.unit'])->where('id', $order->id)->first();
            //            $customer_email    = $input['email'] ?? null;
            //            $email_notify      = $order->store->email_notify ?? null;
            //            $distributor_email = $distributor->email ?? null;
            //            if (!empty($customer_email)) {
            //                $this->dispatch(new SendCustomerMailNewOrderJob($customer_email, [
            //                    'logo'         => $company->avatar,
            //                    'support'      => $company->email,
            //                    'company_name' => $company->name,
            //                    'order'        => $order,
            //                ]));
            //            }
            //            if (!empty($email_notify)) {
            //                $this->dispatch(new SendStoreMailNewOrderJob($email_notify, [
            //                    'logo'         => $company->avatar,
            //                    'support'      => $company->email,
            //                    'company_name' => $company->name,
            //                    'order'        => $order,
            //                ]));
            //            }
            //            if (!empty($distributor_email)) {
            //                $this->dispatch(new SendHUBMailNewOrderJob($distributor_email, [
            //                    'logo'         => $company->avatar,
            //                    'support'      => $company->email,
            //                    'company_name' => $company->name,
            //                    'order'        => $order,
            //                    'link_to'      => TM::urlBase("/user/order/" . $order->id),
            //                ]));
            //            }

            //            $this->sendNotifyConfirmOrder($request, $order);
            #CREATE[ACCESSTRADE]
            try {
                $accesstrade_id = $cart->access_trade_id;
                $click_id       = $cart->access_trade_click_id;
                Accesstrade::create($order, $accesstrade_id, $click_id);
            } catch (\Exception $e) {
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return [
            'order_id'       => $order->id,
            'order_code'     => $order->code,
            'payment_method' => $order->payment_method,
            'lat'            => $order->lat,
            'long'           => $order->long,
            'total'          => $totalPrice
        ];
    }

    public function updateStatusItemInOrder(
        Request $request,
        UpdateStatusItemInOrderValidator $updateStatusItemInOrderValidator
    )
    {
        $input = $request->all();
        $updateStatusItemInOrderValidator->validate($input);
        $userId   = TM::getCurrentUserId();
        $userType = User::find($userId)->type;
        if ($userType !== USER_TYPE_PARTNER) {
            return ['status' => Message::get("no_permission")];
        }
        try {
            DB::beginTransaction();
            $id          = $input['id'];
            $status      = $input['status'];
            $orderDetall = OrderDetail::find($id);
            if (empty($orderDetall)) {
                return $this->response->errorBadRequest(Message::get('V003', 'ID #' . $id));
            }
            if ($orderDetall->status === ORDER_HISTORY_STATUS_COMPLETED) {
                return $this->response->errorBadRequest(Message::get('V046', $status, $orderDetall->status));
            }
            $orderDetall->status = $status;
            $orderDetall->save();
            $orderId = $orderDetall->order_id;
            $result  = OrderDetail::model()
                ->where('order_id', $orderId);
            $result  = $result->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', ORDER_HISTORY_STATUS_COMPLETED);
            });
            $result  = $result->get()->toArray();
            if (empty($result)) {
                $order = Order::find($orderId);
                if ($order->status === ORDER_STATUS_COMPLETED) {
                    return $this->response->errorBadRequest(Message::get('V046', $order->status));
                }
                $order->status = ORDER_STATUS_COMPLETED;
                $order->save();
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("user_status_orders.update-success")];
    }

    ####################### Enterprise ######################
    public function assignEnterprises(
        $orderId,
        Request $request,
        OrderAssignEnterprisesValidator $assignEnterprisesValidator
    )
    {
        $input = $request->all();
        $assignEnterprisesValidator->validate($input);

        try {
            $order = Order::find($orderId);
            if (empty($order)) {
                throw new \Exception(Message::get("V003", Message::get('orders') . " #$orderId"));
            }

            $enterpriseFound = $this->requestEnterprise($order, $input['product_id']);
            if (!empty($enterpriseFound)) {
                return response()->json(['data' => $enterpriseFound]);
            }
            //                DB::beginTransaction();
            //                $input['enterprise_id'] = $enterpriseFound->user_id;
            //                $enterpriseModel = new EnterpriseOrderModel();
            //                $order = $enterpriseModel->createEnterprises($orderId, $input);
            //                DB::commit();
            //            }
            //            return response()->json(['data' => $enterpriseFound]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("V034")];
    }

    public function enterpriseResponse(
        $orderId,
        Request $request,
        OrderAssignEnterprisesValidator $assignEnterprisesValidator
    )
    {
        $input = $request->all();
        $assignEnterprisesValidator->validate($input);

        try {
            DB::beginTransaction();
            $order = Order::find($orderId);
            if (empty($order)) {
                throw new \Exception(Message::get("V003", "Order #$orderId"));
            }

            // Check Order Received by Other Partner
            if (!empty($order->partner_id) || $order->status == ORDER_STATUS_RECEIVED) {
                throw new \Exception(Message::get("V039"));
            }

            if (empty($input['received']) || strtoupper($input['received']) == "NO") {
                // Deny
                $denieds                      = !empty($order->enterprise_denied_ids) ? explode(",", $order->enterprise_denied_ids) : [];
                $denieds[]                    = TM::getCurrentUserId();
                $denieds                      = array_unique($denieds);
                $order->enterprise_denied_ids = implode(",", $denieds);
                $order->save();

                // Continue Request
                $this->requestEnterprise($order, $input['product_id']);
                $userStatus = "CANCELED";
            } elseif (strtoupper($input['received']) == "YES") {
                return $this->responseError("Chưa xử lý trường hợp này đâu thím!!!");
                $userStatus = "RECEIVED";

                // Partner Revenue
                $objectType          = TM::getMyPartnerType();
                $totalPartnerFee     = 0;
                $totalPartnerRevenue = 0;
                if ($objectType && in_array($objectType, [USER_PARTNER_TYPE_ENTERPRISE, USER_PARTNER_TYPE_PERSONAL])) {
                    $objectType    = strtolower($objectType);
                    $details       = $order->details;
                    $allProductIds = array_column($details->toArray(), 'product_id');
                    $products      = Product::model()->select(['id', 'personal_object', 'enterprise_object'])
                        ->whereIn('id', $allProductIds)->get()->pluck(null, 'id')->toArray();

                    foreach ($details as $detail) {
                        $price          = !empty($detail->real_price) ? $detail->real_price : $detail->price;
                        $qty            = object_get($detail, 'qty', 0);
                        $partnerRate    = array_get($products, $detail->product_id . ".{$objectType}_object");
                        $partnerRevenue = $partnerRate > 0 ? ($qty * $price * (float)$partnerRate / 100) : null;
                        if ($totalPartnerFee == 0) {
                            $totalPartnerFee = $partnerRate == "fee" ? ((int)$order->district_fee) : 0;
                        }
                        $shineRate                     = $partnerRate === null || $partnerRate == "fee" ? 100 : (100 - $partnerRate);
                        $totalPartnerRevenue           += $partnerRevenue;
                        $detail->partner_revenue_rate  = $partnerRate;
                        $detail->partner_revenue_total = $partnerRevenue;
                        $detail->shine_revenue_rate    = $shineRate;
                        $detail->status                = ORDER_STATUS_PENDING;
                        $detail->updated_at            = date('Y-m-d H:i:s', time());
                        $detail->updated_by            = TM::getCurrentUserId();
                        $detail->save();
                    }
                }

                // Update Revenue
                $order->partner_revenue_total = $totalPartnerRevenue;
                $order->partner_ship_fee      = $totalPartnerFee;
                $order->shine_revenue_total   = $order->total_price - $totalPartnerRevenue;

                // Update Partner for Order
                $order->partner_id = TM::getCurrentUserId();
                $order->status     = ORDER_STATUS_RECEIVED;
                $order->save();

                // Send Notification
                $device = UserSession::model()->where('user_id', $order->customer_id)
                    ->where('deleted', '0')->first();
                $device = $device->device_token ?? null;
                if ($device) {
                    $receiver = Profile::model()->where('user_id', TM::getCurrentUserId())->first();
                    // Send Notification
                    $fields  = [
                        'data'         => [
                            'type'         => "RECEIVED-PARTNER",
                            'order_id'     => $order->id,
                            'user_id'      => $receiver->user_id,
                            'full_name'    => $receiver->full_name,
                            'phone'        => $receiver->phone,
                            'lat'          => $receiver->lat,
                            'long'         => $receiver->long,
                            "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                        ],
                        'notification' => [
                            'title' => "Đã có đối tác nhận đơn của bạn",
                            'sound' => 'shame',
                            'body'  => $receiver->full_name . " đã nhận đơn của bạn",
                        ],
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
                        throw new \Exception("FCM Error: " . curl_error($ch));
                    }
                    curl_close($ch);
                }
            }

            if (isset($userStatus)) {
                // Add User Status Order
                UserStatusOrder::insert([
                    'user_id'    => TM::getCurrentUserId(),
                    'order_id'   => $order->id,
                    'status'     => $userStatus,
                    'created_at' => date("Y-m-d H:i:s", time()),
                    'created_by' => TM::getCurrentUserId(),
                ]);
            }
            DB::commit();
            return response()->json(['status' => 'success', 'message' => "Successfully"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    //Print Order
    public function printOrder($id)
    {
        $order = Order::model()->with([
            'seller',
            'customer',
            'customer.profile',
            'details',
            'details.product',
            'user',
        ])->where('id', $id)->first();

        if (empty($order)) {
            return ['data' => []];
        }
        $dataCompany = Company::find(TM::getCurrentCompanyId());
        if (empty($dataCompany)) {
            return $this->response->errorBadRequest(Message::get('V003', Message::get("companies")));
        }
        $companyName    = $dataCompany->name;
        $companyEmail   = $dataCompany->email;
        $companyAddress = $dataCompany->address;
        $companyTax     = $dataCompany->tax_code;
        $companyPhone   = $dataCompany->phone;
        $pdf            = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 40, -1, true);
        $pdf->SetHeaderData($ln = '', $lw = 0, $ht = '<br/>', $hs = '
            <table style="display: block; font-size: 8px">
                <tr>
                    <td width="100px"><img height="300px" width="300px" src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . ($order->code) . '"></td>
                    <td width="200px">
                        <p><strong>' . ($companyName) . '</strong><br/>
                            Địa chỉ: ' . ($companyAddress) . '<br/>
                            Email: ' . ($companyEmail) . '<br/>
                            SĐT: ' . ($companyPhone) . '<br/>
                            Tax: ' . ($companyTax) . '<br/>
                        </p>
                    </td>
                    <td width="50px">&nbsp;</td>
                    <td width="200px">
                        <h3>Hóa đơn thanh toán</h3>
                        <p>Ngày: ' . (date("d/m/Y", strtotime($order->created_at))) . '<br/>
                            Mã hóa đơn #: ' . ($order->code) . '
                        </p>
                    </td>
                </tr>
            </table>
            <hr/>
            ');
        $pdf->SetPrintFooter(false);
        // add a page
        $pdf->AddPage();
        // --- Approve
        if (!empty($order->status)) {
            $pdf->StartTransform();
            $pdf->Rotate(45);
            $pdf->SetFont('dejavusans', 'B', 70);
            $pdf->SetTextColor(204, 204, 204);
            $pdf->TranslateX(-100);
            $pdf->TranslateY(120);
            $pdf->Write(0, "");
        }
        // --- End Approve
        $pdf->StopTransform();

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $tempPrice    = 0;
        $orderDetails = $order->details;
        $myTax        = ["null" => 0, 0 => 0, 5 => 0, 10 => 0];
        foreach ($orderDetails as $key => $item) {

            if (!empty($item->decrement)) {
                $tempPrice -= $item->price * $item->qty;
            }
            if (!empty($item->discount)) {
                $tempPrice -= (float)$item->discount * $item->qty;
            }
            if (!isset($item->total)) {
                continue;
            }
            $tax  = $item->product->tax === null ? "null" : $item->product->tax;
            $temp = $item->price * $item->qty;
            $temp = !empty($item->decrement) ? $temp = 0 : $temp;
            $temp = $temp + ($tax > 0 ? $temp * $tax / 100 : 0);;
            $tempPrice   += $temp;
            $myTax[$tax] += $temp;
        }
        $totalPrice = $tempPrice;

        $coupon = Coupon::model()->where('code', $order->coupon_code)->first();
        if (!empty($order->coupon_code) && !empty($coupon)) {
            $totalPrice -= $order->total_discount;
        }
        $totalConvert    = $this->convert_number_to_words($totalPrice - object_get($order, 'discount', 0)) . ' đồng.';
        $addressCustomer = object_get($order, 'getWard.type') . object_get($order, 'getWard.name') . ", "
                           . object_get($order, 'getDistrict.type') . " " . object_get($order, 'getDistrict.name') . ", "
                           . object_get($order, 'getCity.type') . object_get($order, 'getCity.name');
        $streetCustomer  = object_get($order, 'street_address', null);
        $data            = [
            'orderTax'        => $myTax["null"],
            'orderTax0'       => $myTax[0],
            'orderTax5'       => $myTax[5],
            'orderTax10'      => $myTax[10],
            'order'           => $order,
            'total'           => $totalPrice,
            'totalConvert'    => $totalConvert,
            'approver_name'   => object_get($order, 'user.profile.full_name'),
            'tax_code'        => $companyTax,
            'address'         => $order->shipping_address,
            'phone'           => $companyPhone,
            'payment_method'  => PAYMENT_METHOD_NAME_CONVERT[$order->payment_method] ?? null,
            'tempPrice'       => $tempPrice,
            'addressCustomer' => $addressCustomer,
            'streetCustomer'  => $streetCustomer,
        ];

        $html = view("order.order_print", compact('data'));
        $pdf->writeHTML($html);
        $name = "{$order->code}-{$order->customer_id}.pdf";
        if (!file_exists(storage_path() . "/order/print")) {
            mkdir(storage_path() . "/order/print", 0755, true);
        }
        $filePdf = storage_path() . "/order/print/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename='$name'");
        header('Access-Control-Allow-Origin: *');
        readfile($filePdf);
        return Message::get('orders.print-success', $order->code);
    }

    public function printDeliveryNote($id)
    {
        $order = Order::model()->with([
            'seller',
            'customer',
            'customer.profile',
            'details',
            'details.product',
            'user',
        ])->where('id', $id)->first();

        if (empty($order)) {
            return ['data' => []];
        }
        $dataCompany = Company::find(TM::getCurrentCompanyId());
        if (empty($dataCompany)) {
            return $this->response->errorBadRequest(Message::get('V003', Message::get("companies")));
        }
        $companyName    = $dataCompany->name;
        $companyEmail   = $dataCompany->email;
        $companyAddress = $dataCompany->address;
        $companyTax     = $dataCompany->tax_code;
        $companyPhone   = $dataCompany->phone;
        $pdf            = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 40, -1, true);
        $pdf->SetHeaderData($ln = '', $lw = 0, $ht = '<br/>', $hs = '
            <table style="display: block">
                <tr>
                    <td width="100px"><img height="300px" width="300px" src="https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=' . ($order->code) . '"></td>
                    <td width="200px">
                        <p><strong>' . ($companyName) . '</strong><br/>
                            Địa chỉ: ' . ($companyAddress) . '<br/>
                            Email: ' . ($companyEmail) . '<br/>
                            SĐT: ' . ($companyPhone) . '<br/>
                            Tax: ' . ($companyTax) . '<br/>
                        </p>
                    </td>
                    <td width="50px">&nbsp;</td>
                    <td width="200px">
                        <h3>Phiếu Xuất Hàng</h3>
                        <p>Ngày: ' . (date("d/m/Y", strtotime($order->created_at))) . '<br/>
                            Mã hóa đơn #: ' . ($order->code) . '
                        </p>
                    </td>
                </tr>
            </table>
            <hr/>
            ');
        $pdf->SetPrintFooter(false);
        // add a page
        $pdf->AddPage();
        // --- Approve
        if (!empty($order->status)) {
            $pdf->StartTransform();
            $pdf->Rotate(45);
            $pdf->SetFont('dejavusans', 'B', 70);
            $pdf->SetTextColor(204, 204, 204);
            $pdf->TranslateX(-100);
            $pdf->TranslateY(120);
            $pdf->Write(0, "");
        }
        // --- End Approve
        $pdf->StopTransform();

        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetTextColor(0, 0, 0);

        $tempPrice   = 0;
        $orderDeails = $order->details;
        foreach ($orderDeails as $key => $item) {
            $tempPrice += $item->total;
        }
        $totalPrice = $tempPrice;
        $coupon     = Coupon::model()->where('code', $order->coupon_code)->first();
        if (!empty($order->coupon_code) && !empty($coupon)) {
            $totalPrice -= $order->total_discount;
        }
        $totalConvert    = $this->convert_number_to_words($totalPrice) . ' đ';
        $addressCustomer = object_get($order, 'getWard.type') . object_get($order, 'getWard.name') . ", "
                           . object_get($order, 'getDistrict.type') . " " . object_get($order, 'getDistrict.name') . ", "
                           . object_get($order, 'getCity.type') . object_get($order, 'getCity.name');
        $streetCustomer  = object_get($order, 'street_address', null);
        $data            = [
            'order'           => $order,
            'total'           => $totalPrice,
            'totalConvert'    => $totalConvert,
            'approver_name'   => object_get($order, 'user.profile.full_name'),
            'tax_code'        => $companyTax,
            'address'         => $order->shipping_address,
            'phone'           => $order->$companyPhone,
            'note'            => $order->note,
            'payment_method'  => PAYMENT_METHOD_NAME_CONVERT[$order->payment_method] ?? null,
            'tempPrice'       => $tempPrice,
            'addressCustomer' => $addressCustomer,
            'streetCustomer'  => $streetCustomer,
        ];
        $html            = view("order.order_print_delivery_note", compact('data'));
        $pdf->writeHTML($html);
        $name = "{$order->code}-{$order->customer_id}.pdf";
        if (!file_exists(storage_path() . "/order/print/delevery_note")) {
            mkdir(storage_path() . "/order/print/delevery_note", 0755, true);
        }
        $filePdf = storage_path() . "/order/print/delevery_note/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename='$name'");
        header('Access-Control-Allow-Origin: *');
        readfile($filePdf);
        return Message::get('orders.print-success', $order->code);
    }

    public function getOrderDetail($id, OrderDetailTransformer $transformer)
    {
        $result = OrderDetail::find($id);
        if (empty($result)) {
            return ['data' => null];
        }
        return $this->response->item($result, $transformer);
    }

    ############################################### PRIVATE ###############################################
    private function requestEnterprise(Order $order, $product_id)
    {
        if (empty($product_id)) {
            return [];
        }

        $product = Product::model()->where('id', $product_id)->first();

        $deniedIds          = !empty($order->enterprise_denied_ids) ? explode(",", $order->enterprise_denied_ids) : [];
        $enterpriseForOrder = $this->model->getEnterpriseForOrder(
            $order->lat,
            $order->long,
            $product->area_id,
            $deniedIds,
            1
        );
        if (!empty($enterpriseForOrder)) {
            $userId = $enterpriseForOrder->user_id;
            $device = UserSession::model()->where('user_id', $userId)->where('deleted', '0')->first();
            $device = $device->device_token ?? null;
            if ($device) {
                // Send Notification
                $fields  = [
                    'data'         => [
                        'type'         => "ASSIGN-ENTERPRISE",
                        'order_id'     => $order->id,
                        'product_id'   => $product_id,
                        //                        'order'    => $order->toArray(),
                        "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                    ],
                    'notification' => [
                        'title' => "Sản phẩm mới",
                        'sound' => 'shame',
                        'body'  => "Bạn nhận được sản phẩm: [" . $product->code . "] " . $product->name . ". Đơn hàng: #" . $order->code,
                    ],
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
                    throw new \Exception("FCM Error: " . curl_error($ch));
                }
                curl_close($ch);
            }
        }

        return $enterpriseForOrder;
    }

    public function updateSeller(Request $request)
    {
        $input = $request->all();
        DB::beginTransaction();
        try {
            foreach ($input as $value) {
                $userId        = $value['seller_id'] ?? $value['leader_id'];
                $checkSellerId = User::model()->where('id', $userId)
                    ->whereHas('userStores', function ($q) {
                        $q->where('store_id', TM::getCurrentStoreId());
                    })->first();
                if (empty($checkSellerId)) {
                    throw new \Exception(Message::get("V003", $userId));
                }
                if (!empty($value['seller_id'])) {
                    $order = Order::model()->whereNull('seller_id')->where(['store_id' => TM::getCurrentStoreId(), 'status' => ORDER_STATUS_NEW, 'status_crm' => ORDER_STATUS_CRM_PENDING]);
                    if (TM::info()['role_code'] == USER_ROLE_LEADER) {
                        $order = $order->where('leader_id', TM::info()['id']);
                    }
                    if (TM::info()['role_code'] != USER_ROLE_LEADER) {
                        $order = $order->whereNull('leader_id');
                    }
                    $order->limit($value['qty_order'])
                        ->update([
                            'seller_id'   => $checkSellerId->id,
                            'seller_code' => $checkSellerId->code,
                            'seller_name' => $checkSellerId->name,
                            'leader_id'   => TM::info()['role_code'] == USER_ROLE_LEADER ? TM::info()['id'] : $checkSellerId->parentLeader->id
                        ]);
                }
                if (!empty($value['leader_id'])) {
                    Order::model()->whereNull('leader_id')->where(['store_id' => TM::getCurrentStoreId(), 'status' => ORDER_STATUS_NEW, 'status_crm' => ORDER_STATUS_CRM_PENDING])->limit($value['qty_order'])
                        ->update([
                            'leader_id' => $checkSellerId->id,
                        ]);
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return $exception->getMessage();
        }
        return ['status' => Message::get("orders.update-seller-success")];
    }

    public function orderCollectionCRM(Request $request)
    {
        $input = $request->all();
        foreach ($input as $value) {
            $userId      = $value['seller_id'] ?? $value['leader_id'];
            $checkUserId = User::model()->where('id', $userId)
                ->whereHas('userStores', function ($q) {
                    $q->where('store_id', TM::getCurrentStoreId());
                })
                ->whereHas('role', function ($q) use ($value) {
                    if (!empty($value['seller_id'])) {
                        $q->where('code', USER_ROLE_SELLER);
                    }
                    if (!empty($value['leader_id'])) {
                        $q->where('code', USER_ROLE_LEADER);
                    }
                })
                ->first();
            if (empty($checkUserId)) {
                return $this->responseError(Message::get("V003", $userId));
            }
            if (!empty($value['seller_id'])) {
                $order = Order::model()->where(['store_id' => TM::getCurrentStoreId(), 'seller_id' => $checkUserId->id, 'status' => ORDER_STATUS_NEW, 'status_crm' => ORDER_STATUS_CRM_PENDING]);
                if (TM::info()['role_code'] == USER_ROLE_LEADER) {
                    $order = $order->where('leader_id', TM::info()['id']);
                }
                $order->limit($value['qty_order'])
                    ->update([
                        'seller_id'   => null,
                        'seller_code' => null,
                        'seller_name' => null,
                    ]);
            }
            if (!empty($value['leader_id'])) {
                Order::model()->where(['store_id' => TM::getCurrentStoreId(), 'leader_id' => $checkUserId->id, 'status' => ORDER_STATUS_NEW, 'status_crm' => ORDER_STATUS_CRM_PENDING])->limit($value['qty_order'])
                    ->update([
                        'leader_id'   => null,
                        'seller_id'   => null,
                        'seller_code' => null,
                        'seller_name' => null,
                    ]);
            }
        }
        return ['status' => Message::get("orders.update-collection-success")];
    }

    public function clientDelete($id)
    {
        try {
            DB::beginTransaction();
            $order = Order::find($id);
            if (empty($order)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            // 1. Delete Order detail
            OrderDetail::model()->where('order_id', $id)->delete();
            // 2. Delete Order
            $order->delete();
            Log::delete($this->model->getTable(), "#ID:" . $order->id . "-" . $order->code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("orders.delete-success", $order->code)];
    }

    public function clientGetOrderByPhone($phone, Request $request, OrderTransformer $orderTransformer)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
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
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($company_id)) {
            return response()->json(['data' => []]);
        }

        $input          = $request->all();
        $input['phone'] = $phone;
        $limit          = array_get($input, 'limit', 20);
        $order          = $this->model->searchByPhone(
            $input,
            ['hub', 'partner', 'customer', 'details', 'promotionTotals'],
            $limit
        );
        Log::view($this->model->getTable());
        return $this->response->paginator($order, $orderTransformer);
    }

    public function getProductPurchased(
        Request $request,
        OrderProductPurchasedTransformer $orderProductPurchasedTransformer
    )
    {
        $input        = $request->all();
        $limit        = array_get($input, 'limit', 20);
        $productOrder = OrderDetail::model()
            ->select(['order_details.product_id', DB::raw("SUM(order_details.qty) as qty")])
            ->join('orders AS o', 'o.id', '=', 'order_details.order_id')
            ->join('products AS p', 'p.id', '=', 'order_details.product_id')
            ->where('o.customer_id', TM::getCurrentUserId())
            ->where('o.store_id', TM::getCurrentStoreId())
            ->where('o.status', ORDER_STATUS_COMPLETED)
            ->where('p.deleted', 0)
            ->groupBy('order_details.product_id')
            ->paginate($limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($productOrder, $orderProductPurchasedTransformer);
    }

    public function orderExportExcel(Request $request)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        ini_set('max_execution_time', 50000);
        ini_set('memory_limit', '-1');
        if (ob_get_contents()) ob_end_clean();
        $query          = "";
        $input          = $request->all();
        $date           = date('YmdHis', time());
        $from           = '';
        $to             = '';
        $dataOrder      = null;
        $distributor_id = null;
        $user_dis       = UserRP::where('id', TM::getCurrentUserId())->select('id', 'role_id', 'group_code')->first(); // quan.pham
        if ($user_dis->group_code == "DISTRIBUTOR" || $user_dis->group_code == "HUB") {
            $distributor_id = $user_dis->id;
            $query          .= "$store_id AND distributor_id = $distributor_id ";
        } else {
            if ($user_dis->group_code == "TTPP") {
                $id_hub    = [];
                $user_ttpp = UserRP::where('distributor_center_id', $user_dis->id)->get();
                foreach ($user_ttpp as $ut) {
                    array_push($id_hub, $ut['id']);
                }
                $query .= "$store_id AND distributor_id IN ($id_hub)";
            } else {
                $query .= "$store_id";
            }
        }

        // if(!empty($input['code']) || !empty($input['distributor_id'])|| !empty($input['distributor_code'])){
        //     if(!empty($input['code'])){
        //         $sql = "SELECT * FROM `order_export_excel` WHERE store_id = 58 and `order_code` = '{$input['code']}' ORDER BY order_created_at DESC;";
        //         $dataOrder = DB::select($sql);
        //     }
        //     if(!empty($input['distributor_id'])){
        //         $sql = "SELECT * FROM `order_export_excel` WHERE store_id = 58 and `order_code` = '{$input['distributor_id']}' ORDER BY order_created_at DESC;";
        //         $dataOrder = DB::select($sql);
        //     }
        //     if(!empty($input['code'])){
        //         $sql = "SELECT * FROM `order_export_excel` WHERE store_id = 58 and `order_code` = '{$input['code']}' ORDER BY order_created_at DESC;";
        //         $dataOrder = DB::select($sql);
        //     }
        // }else{
        if (empty($input['from']) && empty($input['to'])) {
            // $dataOrder = DB::connection('mysql2')->select("CALL get_order_export_excel_all('$query')");
            $sql       = "SELECT distinct * FROM `order_export_excel` WHERE `store_id`= " . $query . " AND deleted_at is null ORDER BY order_created_at DESC;";
            $dataOrder = DB::connection('mysql2')->select($sql);
            if (!empty($dataOrder)) {
                $to   = head($dataOrder)->order_created_at;
                $from = end($dataOrder)->order_created_at;
            }
        } else {
            $from      = date('Y-m-d', strtotime($input['from']));
            $to        = date('Y-m-d', strtotime($input['to']));
            $sql       = "SELECT distinct * FROM `order_export_excel` WHERE `order_created_at` BETWEEN '{$from}' AND '{$to}' AND store_id =" . $query . " AND deleted_at is null ORDER BY order_created_at DESC;";
            $dataOrder = DB::connection('mysql2')->select($sql);

            // $dataOrder = DB::connection('mysql2')->select("CALL 
            // get_order_export_excel(" . "'$from'" . ',' . "'$to'" . ',' . "'$query'" . ")");
        }

        $permision = PermissionRP::where('code', 'CUSTOMER-INFO')->select('id')->first();
        if (!empty($permision)) {
            $role = RolePermissionRP::where('role_id', $user_dis->role_id)->where('permission_id', $permision->id)->whereNull('deleted_at')->first();
        }
        $dataOrder['role_id'] = $user_dis->role_id;
        $dataOrder['is_ac']   = !empty($role) ? 0 : 1;

        ob_start();
        return Excel::download(new ExportOrders($dataOrder ?? [], $from, $to), 'list_orders_' . $date . '.xlsx');
    }

    public function orderExportExcel2(Request $request)
    {
        // return ['message' => Message::get("R011"), 'code' => Response::HTTP_BAD_REQUEST];
        $input    = $request->all();
        $date     = date('YmdHis', time());
        $time     = date('Y-m-d', time());
        $user_dis = User::where('id', TM::getCurrentUserId())->first();
        $orders   = Order::with(['details'])
            ->where('store_id', TM::getCurrentStoreId())
            ->where(function ($q) use ($input, $user_dis) {
                if (!empty($input['shipping_address_city_code'])) {
                    $q->where('shipping_address_city_code', $input['shipping_address_city_code']);
                }
                if (!empty($input['shipping_address_district_code'])) {
                    $q->where('shipping_address_district_code', $input['shipping_address_district_code']);
                }
                if (!empty($input['shipping_address_ward_code'])) {
                    $q->where('shipping_address_ward_code', $input['shipping_address_ward_code']);
                }
                if (!empty($input['customer_id'])) {
                    $q->where('customer_id', $input['customer_id']);
                }
                if (!empty($input['customer_code'])) {
                    $q->where('customer_code', $input['customer_code']);
                }
                if (!empty($input['customer_name'])) {
                    $q->where('customer_name', $input['customer_name']);
                }
                if (!empty($input['customer_phone'])) {
                    $q->where('customer_phone', $input['customer_phone']);
                }
                if (!empty($input['status'])) {
                    $q->where('status', $input['status']);
                }
                // if (!empty($input['distributor_id'])) {
                //     $q->where('distributor_id', $input['distributor_id']);
                // }
                if ($user_dis->group_code == "DISTRIBUTOR") {
                    $q->where('distributor_id', $user_dis->id);
                }
                if ($user_dis->group_code == "HUB") {
                    $q->where('distributor_id', $user_dis->id);
                }
                if ($user_dis->group_code == "TTPP") {
                    $id_hub    = [];
                    $user_ttpp = User::where('distributor_center_id', $user_dis->id)->get();
                    foreach ($user_ttpp as $ut) {
                        array_push($id_hub, $ut['id']);
                    }
                    $q->whereIn('distributor_id', $id_hub);
                }
                if (!empty($input['distributor_code'])) {
                    $q->where('distributor_code', $input['distributor_code']);
                }
                if (!empty($input['code'])) {
                    $q->where('code', $input['code']);
                }
                if (!empty($input['from']) && !empty($input['to'])) {
                    $q->whereDate('created_at', '>=', $input['from'])->whereDate('created_at', '<=', $input['to']);
                }
            })
            ->get();
        $i        = 0;
        foreach ($orders as $order) {
            $details = $order->details;
            foreach ($details as $detail) {
                if (!$order->shipping_method_name) continue;
                $orderStatus = $detail->orderStatus->pluck(null, 'order_status_code')->toArray();
                $statusNew      = $orderStatus[ORDER_STATUS_NEW]['created_at'] ?? null;//GIỜ LÊN ĐƠN //NEW
                $statusApproved = $orderStatus[ORDER_STATUS_APPROVED]['created_at'] ?? null;//GIỜ XÁC NHẬN ĐƠN //APPROVED
                //GIỜ XÁC NHẬN VC  //??
                //GIỜ XUẤT KHO  //??
                $statusShipping  = $orderStatus[ORDER_STATUS_SHIPPING]['created_at'] ?? null;//GIỜ XÁC NHẬN VC  //SHIPPING
                $statusShipped   = $orderStatus[ORDER_STATUS_SHIPPED]['created_at'] ?? null;//GIỜ NVC GIAO HÀNG XONG //SHIPPED
                $statusCompleted = $orderStatus[ORDER_STATUS_COMPLETED]['created_at'] ?? null;
                $statusCanceled  = $orderStatus[ORDER_STATUS_CANCELED]['created_at'] ?? null;

                $categoryArr = !empty($detail->product->category_ids) ? explode(',', $detail->product->category_ids) : null;
                if ($categoryArr) {
                    $categories    = Category::model()->whereIn('id', $categoryArr)->select('name')->get();
                    $categoryArray = [];
                    foreach ($categories as $category) {
                        array_push($categoryArray, $category['name']);
                    }
                    $nameCategories = implode(",", $categoryArray);
                } else $nameCategories = "";

                $dataOrder[] = [
                    'stt'                   => ++$i,
                    'distributor_code'      => array_get($order, "distributor.code"),
                    'distributor_name'      => array_get($order, "distributor.name"),
                    'distributor_address'   => array_get($order, "distributor.profile.address"),
                    'ward_code'             => array_get($order, "distributor.profile.ward.code"),
                    'ward_name'             => array_get($order, "distributor.profile.ward.name"),
                    'district_code'         => array_get($order, "distributor.profile.district.code"),
                    'district_name'         => array_get($order, "distributor.profile.district.name"),
                    'city_code'             => array_get($order, "distributor.profile.city.code"),
                    'city_name'             => array_get($order, "distributor.profile.city.name"),
                    'order_code'            => $order->code,
                    'order_type'            => $order->customer->group->name ?? null,
                    'created_at'            => !empty($statusNew) ? date('d-m-Y H:i', strtotime($statusNew)) : date('d-m-Y H:i', strtotime($order->created_at)), //GIỜ LÊN ĐƠN //NEW
                    'updated_date'          => !empty($statusApproved) ? (date('d-m-Y H:i', strtotime($statusApproved))) : null, //GIỜ XÁC NHẬN ĐƠN //APPROVED
                    'order_created_date'    => !empty($statusShipping) ? date('d-m-Y H:i', strtotime($statusShipping)) : null, //GIỜ XÁC NHẬN VC
                    'order_shipped_date'    => !empty($statusShipped) ? date('d-m-Y H:i', strtotime($statusShipped)) : null, //ngày gh thành công
                    'order_updated_date'    => !empty($statusCompleted) ? date('d-m-Y H:i', strtotime($statusCompleted)) : null, //ngày hoàn thành đơn hàng
                    'order_canceled_date'   => !empty($statusCanceled) ? (date('d-m-Y H:i', strtotime($statusCanceled))) : null, //ngày huỷ nhật đơn
                    'order_revice_date'     => !empty($order->receive_date) ? (date('d-m-Y H:i', strtotime($order->receive_date))) : null, //ngày nvc nhận hàng
                    'order_canceled_reason' => !empty($order->canceled_reason_admin) ? $order->canceled_reason_admin : (!empty(json_decode($order->canceled_reason)) ? json_decode($order->canceled_reason)->value : $order->canceled_reason), //lý do huỷ nhận đơn
                    'updated_at'            => date('d-m-Y H:i', strtotime($order->updated_at)), //ngày cập nhật
                    'shipping_method_code'  => $order->shipping_method_code == "DEFAULT" ? "NTF" : $order->shipping_method_code, //MÃ NVC
                    'shipping_method_name'  => $order->shipping_method_name, // TÊN NVC
                    'product_code'          => $detail->product_code, //MÃ SP
                    'product_name'          => $detail->product_name, //TÊN SP
                    'product_category_name' => $nameCategories, //TÊN NHÓM HÀNG
                    'unit'                  => $detail->product->unit->name ?? null, //ĐVT
                    'product_specification' => $detail->product->specification->value ?? null, //QUI CÁCH
                    'qty'                   => $detail->qty ?? null, //SỐ LƯỢNG
                    'total_weight'          => !empty($detail->product['weight']) && $detail->product['weight_class'] == "GRAM" ? (($detail->product['weight'] / 1000) * $detail->qty) : $detail->product['weight'] * $detail->qty, //SỐ KG
                    'total_price'           => $detail->total ?? 0, // DOANH SỐ (TRƯỚC VAT)
                    'ship_fee_total'        => $order->ship_fee ?? 0, // CƯỚC PHÍ VC (TRƯỚC VAT)
                ];
            }
        }
        if (empty($input['from'])) {
            $input['from'] = '';
        }
        if (empty($input['to'])) {
            $input['to'] = '';
        }
      
        return Excel::download(new ExportOrders2($dataOrder ?? [], $input['from'], $input['to']), 'list_orders_' . $date . '.xlsx');
    }

    public function orderListExport(Request $request)
    {
        //ob_end_clean(); // this
        $input = $request->all();
        $date  = date('YmdHis', time());
        $time  = date('Y-m-d', time());
        ini_set('max_execution_time', 50000);
        ini_set('memory_limit', '-1');
        $user_dis  = User::where('id', TM::getCurrentUserId())->first();
        $orders    = Order::with(['details', 'details.product.unit', 'store', 'customer', 'distributor', 'distributor.profile', 'distributor.profile.city', 'distributor.profile.district', 'distributor.profile.ward', 'promotionTotals'])
            ->where('store_id', TM::getCurrentStoreId())
            ->where(function ($q) use ($input, $user_dis) {
                if (!empty($input['shipping_address_city_code'])) {
                    $q->where('shipping_address_city_code', $input['shipping_address_city_code']);
                }
                if (!empty($input['shipping_address_district_code'])) {
                    $q->where('shipping_address_district_code', $input['shipping_address_district_code']);
                }
                if (!empty($input['shipping_address_ward_code'])) {
                    $q->where('shipping_address_ward_code', $input['shipping_address_ward_code']);
                }
                if (!empty($input['customer_id'])) {
                    $q->where('customer_id', $input['customer_id']);
                }
                if (!empty($input['customer_code'])) {
                    $q->where('customer_code', $input['customer_code']);
                }
                if (!empty($input['customer_name'])) {
                    $q->where('customer_name', $input['customer_name']);
                }
                if (!empty($input['customer_phone'])) {
                    $q->where('customer_phone', $input['customer_phone']);
                }
                if (!empty($input['status'])) {
                    $q->where('status', $input['status']);
                }
                if (!empty($input['distributor_id'])) {
                    $q->where('distributor_id', $input['distributor_id']);
                }
                //                if (!empty($input['distributor_code'])) {
                //                    $q->where('distributor_code', $input['distributor_code']);
                //                }
                if ($user_dis->group_code == "DISTRIBUTOR") {
                    $q->where('distributor_id', $user_dis->id);
                }
                if ($user_dis->group_code == "HUB") {
                    $q->where('distributor_id', $user_dis->id);
                }
                if ($user_dis->group_code == "TTPP") {
                    $id_hub    = [];
                    $user_ttpp = User::where('distributor_center_id', $user_dis->id)->get();
                    foreach ($user_ttpp as $ut) {
                        array_push($id_hub, $ut['id']);
                    }
                    $q->whereIn('distributor_id', $id_hub);
                }
                if (!empty($input['code'])) {
                    $code = explode(",", $input['code']);
                    $q->whereIn('code', $code);
                }
                if (!empty($input['from']) && !empty($input['to'])) {
                    $q->whereDate('created_at', '>=', $input['from'])->whereDate('created_at', '<=', $input['to']);
                }
            })
            ->get();
        $i         = 0;
        $promocode = [];

        foreach ($orders as $order) {
            $created_order              = date('Y-m-d', strtotime($order->updated_at));
            $shippingOrder              = $order->shippingOrders ?? [];
            $count_print                = $shippingOrder['count_print'] ?? 0;
            $count_print_shipping_order = $shippingOrder['count_print_shipping_order'] ?? 0;

            $status = ORDER_STATUS_NEW_NAME[$order->status];

            //            if ($order->status == ORDER_STATUS_SHIPPING) {
            //                if (empty($count_print) || empty($count_print_shipping_order)) {
            //                    $status = "Đã duyệt - TLGH";
            //                }
            //            }
            $shipping_status = [];
            $promoname       = [];
            foreach ($order->shippingStatusHistories as $ss) {
                array_push($shipping_status, $ss->text_status_code);
            }
            $value = [];
            if (!empty($order->promotionTotals)) {
                foreach ($order->promotionTotals as $promo) {
                    array_push($promocode, $promo->promotion_code);
                    array_push($promoname, $promo->promotion_code);
                    array_push($value, $promo->value);
                }
            }
            $dataOrder[] = [
                'stt'                   => ++$i,
                'distributor_code'      => $order->distributor_code,
                'distributor_name'      => $order->distributor_name,
                'customer_code'         => !empty($order->customer_code) ? $order->customer_code : $order->customer->code,
                'customer_name'         => !empty($order->customer_name) ? $order->customer_name : $order->customer->name,
                'customer_phone'        => !empty($order->customer_phone) ? $order->customer_phone : $order->customer->phone,
                'shipping_address'      => $order->shipping_address,
                'ward'                  => $order->getWard->full_name ?? null,
                'district'              => $order->getDistrict->full_name ?? null,
                'city'                  => $order->getCity->full_name ?? null,
                'order_code'            => $order->code,
                'order_type'            => !empty($order->customer->group) ? $order->customer->group->name : null,
                'order_channel'         => $order->order_channel ?? null,
                'lading_method'         => LADING_METHOD[$order->lading_method] ?? null,
                'created_at'            => !empty($statusNew) ? date('d-m-Y H:i', strtotime($statusNew)) : date('d-m-Y H:i', strtotime($order->created_at)), //ngày nhận
                'updated_date'          => !empty($statusApproved) ? (date('d-m-Y H:i', strtotime($statusApproved))) : null, //ngày duyệt
                'order_created_date'    => !empty($statusShipping) ? date('d-m-Y H:i', strtotime($statusShipping)) : null, //ngày tạo lệnh gh
                'order_shipped_date'    => !empty($statusShipped) ? date('d-m-Y H:i', strtotime($statusShipped)) : null, //ngày gh thành công
                'order_updated_date'    => !empty($statusCompleted) ? date('d-m-Y H:i', strtotime($statusCompleted)) : null, //ngày hoàn thành đơn hàng
                'order_canceled_date'   => !empty($statusCanceled) ? (date('d-m-Y H:i', strtotime($statusCanceled))) : null, //ngày huỷ nhật đơn
                'updated_at'            => date('d-m-Y H:i', strtotime($order->updated_at)),
                'payment_code'          => $order->payment_code,
                'payment_method'        => PAYMENT_METHOD_NAME[$order->payment_method] ?? null,
                'shipping_method_name'  => $order->shipping_method_name,
                'is_freeship'           => $order->is_freeship,
                'payment_status'        => $order->payment_status,
                'status'                => $status,
                'shipping_order_status' => end($shipping_status),
                'seller_phone'          => array_get($order, "customer.seller_phone"),
                'reference_phone'       => array_get($order, "customer.reference_phone"),
                'promotion_code'        => $promocode,
                'promotioncode'         => $promoname,
                'promotion_name'        => implode(", ", $promoname),
                'ship_fee_customer'     => $order->is_freeship == 0 ? $order->ship_fee : 0,
                'ship_fee_shop'         => $order->is_freeship == 1 ? $order->ship_fee : 0,
                'ship_fee_total'        => $order->ship_fee,
                'ward_code'             => array_get($order, "distributor.profile.ward.code", null),
                'ward_name'             => array_get($order, "distributor.profile.ward.full_name", null),
                'district_code'         => array_get($order, "distributor.profile.district.code", null),
                'district_name'         => array_get($order, "distributor.profile.district.full_name", null),
                'city_code'             => array_get($order, "distributor.profile.city.code", null),
                'city_name'             => array_get($order, "distributor.profile.city.full_name", null),
                'date_time'             => (strtotime($time) - strtotime($created_order)) / (60 * 60 * 24),
                'total_price'           => $order->total_price,
                'value'                 => $value,
                'sub_total_price'       => $order->sub_total_price
            ];
        };
        $dataOrder['promo']          = array_unique($promocode);
        $dataOrder['code_promotion'] = $promocode;
        //        $dataOrder['value']= $value;

        if (empty($input['from'])) {
            $input['from'] = '';
        }
        if (empty($input['to'])) {
            $input['to'] = '';
        }
        //ob_start(); // and this
        return Excel::download(new OrderListExport($dataOrder ?? [], $input['from'], $input['to']), 'list_orders_' . $date . '.xlsx');
    }

    public function exportOrder(Request $request)
    {
        //ob_end_clean();
        $input  = $request->all();
        $date   = date('YmdHis', time());
        $order  = Order::with(['details', 'details.product.unit', 'store', 'customer'])->where('store_id', TM::getCurrentStoreId())
            ->where(function ($q) use ($input) {
                if (!empty($input['shipping_address_city_code'])) {
                    $q->where('shipping_address_city_code', $input['shipping_address_city_code']);
                }
                if (!empty($input['shipping_address_district_code'])) {
                    $q->where('shipping_address_district_code', $input['shipping_address_district_code']);
                }
                if (!empty($input['shipping_address_ward_code'])) {
                    $q->where('shipping_address_ward_code', $input['shipping_address_ward_code']);
                }
                if (!empty($input['customer_id'])) {
                    $q->where('customer_id', $input['customer_id']);
                }
                if (!empty($input['customer_code'])) {
                    $q->where('customer_code', $input['customer_code']);
                }
                if (!empty($input['customer_name'])) {
                    $q->where('customer_name', $input['customer_name']);
                }
                if (!empty($input['customer_phone'])) {
                    $q->where('customer_phone', $input['customer_phone']);
                }
                if (!empty($input['status'])) {
                    $q->where('status', $input['status']);
                }
                if (!empty($input['distributor_id'])) {
                    $q->where('distributor_id', $input['distributor_id']);
                }
                if (!empty($input['distributor_code'])) {
                    $q->where('distributor_code', $input['distributor_code']);
                }
                if (!empty($input['from']) && !empty($input['to'])) {
                    $q->whereBetween('updated_at', [$input['from'], $input['to']]);
                }
            })
            ->get();
        $order2 = $order->sum('total_price');
        if (empty($input['from'])) {
            $input['from'] = '';
        }
        if (empty($input['to'])) {
            $input['to'] = '';
        }
        return Excel::download(new OrderExport($order, $input['from'], $input['to'], $order2), 'orders_export_' . $date . '.xlsx');
    }

    public function approvedOrderByCode($code)
    {
        $strCode     = [];
        $code        = explode(',', $code);
        $orderStatus = Order::whereIn('code', $code)->pluck('status', 'code')->toArray();
        foreach ($orderStatus as $key => $status) {
            if ($status !== ORDER_STATUS_NEW) {
                return $this->responseError(Message::get('V002', Message::get('status') . " [$key]"));
            }
        }
        try {
            DB::beginTransaction();
            $orders = Order::whereIn('code', $code)->where('status_crm', '!=', ORDER_STATUS_CRM_PENDING)->get();
            foreach ($orders as $order) {
                if ($order->shipping_method_code != SHIPPING_PARTNER_TYPE_DEFAULT) {
                    if ($order->payment_status != 0 || $order->payment_method == PAYMENT_METHOD_CASH) {
                        $type   = $order->shipping_method;
                        $result = [];
                        if ($type) {
                            switch ($type) {
                                case SHIPPING_PARTNER_TYPE_VNP:
                                    $result = VNP::sendOrder($order, VNP::getToken(), 1);
                                    break;
                                case SHIPPING_PARTNER_TYPE_VTP:
                                    $token = VTP::getApiToken();
                                    if ($token == [] || (isset($token['status']) && isset($token['success']) && $token['status'] == 'error' && $token['success'] == 'false')) {
                                        break;
                                    }
                                    $result = VTP::sendOrder($order, $token, 1);
                                    break;
                                case SHIPPING_PARTNER_TYPE_GRAB:
                                    $result = GRAB::sendOrder($order, 1);
                                    break;
                            }
                        }
                        if (!empty($result['status']) && $result['status'] == 'success' && $result['success'] == true) {
                            $order->status_crm = ORDER_STATUS_CRM_ADAPPROVED;
                            $order->save();
                            $dataOrderHistory[] = [
                                'order_id' => $order->id,
                                'status'   => 'APPROVED',
                            ];
                            $dataOrderHistory[] = [
                                'order_id' => $order->id,
                                'status'   => 'SHIPPING',
                            ];
                            foreach ($dataOrderHistory as $value) {
                                $check_status = OrderHistory::model()->where(['order_id' => $value['order_id'], 'status' => $value['status']])->first();
                                if (empty($check_status)) {
                                    OrderHistory::insert([
                                        'order_id'   => $value['order_id'],
                                        'status'     => $value['status'],
                                        'created_at' => date("Y-m-d H:i:s", time()),
                                        'created_by' => TM::getCurrentUserId(),
                                    ]);
                                }
                                $checkHistoryStatus = OrderStatusHistory::model()->where(['order_id' => $value['order_id'], 'order_status_code' => $value['status']])->first();
                                if (empty($checkHistoryStatus)) {
                                    $orderStatus = OrderStatus::model()->where([
                                        'code'       => $value['status'],
                                        'company_id' => TM::getCurrentCompanyId() ?? $order->company_id
                                    ])->first();
                                    $param       = [
                                        'order_id'          => $value['order_id'],
                                        'order_status_id'   => $orderStatus->id ?? null,
                                        'order_status_code' => $orderStatus->code ?? null,
                                        'order_status_name' => $orderStatus->name ?? null,
                                        'created_at'        => date('Y-m-d H:i:s'),
                                        'updated_at'        => date('Y-m-d H:i:s'),
                                        'created_by'        => TM::getCurrentUserId() ?? $order->customer_id
                                    ];
                                    OrderStatusHistory::insert($param);
                                }
                            }
                            ShippingHistoryStatus::insert([
                                'shipping_id'      => $order->code,
                                'status_code'      => "APPROVED",
                                'text_status_code' => ORDER_STATUS_NAME["APPROVED"],
                                'created_at'       => date("Y-m-d H:i:s", time()),
                                'created_by'       => TM::getCurrentUserId(),
                            ]);
                            $strCode[] = $order->code;
                            //Create Inventory && Update Quantity Warehouse
                            $this->shippingType->createInventory($order, $result['warehouse'], $type);
                        }
                    }
                }

                if ($order->shipping_method_code == SHIPPING_PARTNER_TYPE_DEFAULT) {
                    $order->status_crm  = ORDER_STATUS_CRM_ADAPPROVED;
                    $order->status      = "APPROVED";
                    $order->status_text = "Đã xác nhận";
                    OrderHistory::insert([
                        'order_id'   => $order->id,
                        'status'     => $order->status,
                        'created_at' => date("Y-m-d H:i:s", time()),
                        'created_by' => TM::getCurrentUserId(),
                    ]);
                    $this->model->updateOrderStatusHistory($order);
                    ShippingHistoryStatus::insert([
                        'shipping_id'      => $order->code,
                        'status_code'      => $order->status,
                        'text_status_code' => ORDER_STATUS_NAME[$order->status],
                        'created_at'       => date("Y-m-d H:i:s", time()),
                        'created_by'       => TM::getCurrentUserId(),
                    ]);
                    $strCode[] = $order->code;
                    $order->save();
                }
            }
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            $response = TM_Error::handle($exception);
            return $this->responseError($response['message']);
        }
        return ['status' => Message::get("R002", implode(',', $strCode))];
    }

    /**
     * Send SMS
     *
     * @param $message
     * @param $phone
     * @return \Psr\Http\Message\StreamInterface|null
     */
    private function sendSMSCode($message, $phone)
    {
        $param           = [
            'Phone'     => $phone,
            'Content'   => $message,
            'ApiKey'    => env('SMS_API_KEY'),
            'SecretKey' => env('SMS_SECRET_KEY'),
            'SmsType'   => 2,
            'Brandname' => env('SMS_BRAND_NAME'),
        ];
        $client          = new Client();
        $phonesException = Setting::model()->select('data')->where('code', 'PHONE-TEST')->first();
        $phonesException = explode(',', $phonesException->data ?? '');
        if (env('SMS_ENABLE_SEND', null) == 1 && !in_array($phone, $phonesException)) {
            $smsResponse = $client->get(env('SMS_URL'), ['query' => $param])->getBody();
        }
        return $smsResponse ?? null;
    }

    public function exportOrderByPromotion(Request $request)
    {
        //ob_end_clean();
        ini_set('max_execution_time', 50000);
        ini_set('memory_limit', '-1');
        $time   = Carbon::now();
        $input  = $request->all();
        $orders = Order::with('promotionTotals')
            ->where("orders.company_id", TM::getCurrentCompanyId())
            ->where(function ($q) use ($input) {
                if (!empty($input['from']) && !empty($input['to'])) {
                    $q->whereBetween('orders.created_at', [date('Y-m-d 00:00:00', strtotime($input['from'])), date('Y-m-d 23:59:59', strtotime($input['to']))]);
                }
            })
            ->get();
        //        print_r(json_encode($orders));die;
        try {
            $excel = new OrderByPromotionExport("BÁO CÁO CHƯƠNG TRÌNH KHUYẾN MÃI", [
                // ['label_html' => '<strong>STT</strong>', 'width' => '5',],
                ['label_html' => '<strong>Mã đơn hàng</strong>', 'width' => '15',],
                ['label_html' => '<strong>Loại đơn hàng</strong>', 'width' => '15',],
                ['label_html' => '<strong>Trạng thái</strong>', 'width' => '15',],
                ['label_html' => '<strong>Mã khách hàng</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tên khách hàng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Mã CTKM</strong>', 'width' => '20',],
                ['label_html' => '<strong>Tên CTKM</strong>', 'width' => '30',],
                ['label_html' => '<strong>Loại CTKM</strong>', 'width' => '15',],
                ['label_html' => '<strong>Quà tặng</strong>', 'width' => '20',],
                ['label_html' => '<strong>Phí ship shop trả</strong>', 'width' => '15',],
                ['label_html' => '<strong>Phí ship khách trả</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tạm tính</strong>', 'width' => '15',],
                ['label_html' => '<strong>Thành tiền</strong>', 'width' => '15',],
                ['label_html' => '<strong>Mã NPP</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tên NPP</strong>', 'width' => '15',],
                ['label_html' => '<strong>Mã quận/huyện</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tên quận/huyện</strong>', 'width' => '25',],
                ['label_html' => '<strong>Mã phường/xã</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tên phường/xã</strong>', 'width' => '25',],
                ['label_html' => '<strong>Mã tỉnh/tp</strong>', 'width' => '15',],
                ['label_html' => '<strong>Tên tỉnh/tp</strong>', 'width' => '25',],
            ], $input['from'] ?? null, $input['to'] ?? null, $time);
            $excel->setFormatNumber([
                // 'R' => NumberFormat::FORMAT_NUMBER,
                // 'S' => NumberFormat::FORMAT_NUMBER,
                // 'T' => NumberFormat::FORMAT_NUMBER,
            ]);


            $k = 0;
            foreach ($orders as $order) {
                $order_promotion = [];
                $freeItem        = $order->free_item;
                if (!empty(json_decode($freeItem))) {
                    $decode_freeItem = json_decode($freeItem);
                    foreach ($decode_freeItem as $i => $value) {
                        $code     = $value->code;
                        $title    = $value->title;
                        $act_type = $value->act_type;
                        if (!empty($value->text)) {
                            foreach ($value->text as $k => $item) {
                                $name_qty[] = $item->title_gift ?? $item->product_name . ' x ' . $item->qty_gift;
                            }
                        }
                        $order_promotion[] = [
                            'promotion_code'  => $code,
                            'promotion_name'  => $title,
                            'promotion_type'  => $act_type,
                            'promotion_value' => implode(",", $name_qty)
                        ];
                    };
                }
                $order_promotion_bf = $order->promotionTotals->map(function ($detail) {
                    return [
                        'promotion_code'  => $detail->promotion_code,
                        'promotion_name'  => $detail->promotion_name,
                        'promotion_type'  => $detail->promotion_type,
                        'promotion_value' => $detail->value
                    ];
                });

                foreach ($order_promotion_bf as $i => $promotion) {
                    $order_promotion[] = [
                        'promotion_code'  => $promotion['promotion_code'],
                        'promotion_name'  => $promotion['promotion_name'],
                        'promotion_type'  => $promotion['promotion_type'],
                        'promotion_value' => $promotion['promotion_value'],
                    ];
                }
                foreach ($order_promotion as $p => $order_promotions) {
                    $data[] = [
                        // 'stt'                       => $k++,
                        'code'                      => $order->code,
                        'type'                      => ORDER_TYPE_NAME[$order->order_type],
                        'status'                    => ORDER_STATUS_NEW_NAME[$order->status],
                        'customer_code'             => $order->customer_code,
                        'customer_name'             => $order->customer_name,
                        'promotion_code'            => $order_promotions['promotion_code'],
                        'promotion_name'            => $order_promotions['promotion_name'],
                        'promotion_type'            => PROMOTION_TYPE_NAME[$order_promotions['promotion_type']],
                        'promotion_value'           => $order_promotions['promotion_value'],
                        'ship_fee_store'            => $order->is_freeship == 1 ? $order->ship_fee : 0,
                        'ship_fee_customer'         => $order->is_freeship == 0 ? $order->ship_fee : 0,
                        'sub_total_price'           => $order->sub_total_price,
                        'total_price'               => $order->total_price,
                        'distributor_code'          => $order->distributor_code,
                        'distributor_name'          => array_get($order, 'addressDistributor.name', null),
                        'distributor_district_code' => array_get($order, 'addressDistributor.district_code', null),
                        'distributor_district_name' => array_get($order, 'addressDistributor.district_full_name', null),
                        'distributor_ward_code'     => array_get($order, 'addressDistributor.ward_code', null),
                        'distributor_ward_name'     => array_get($order, 'addressDistributor.ward_full_name', null),
                        'distributor_city_code'     => array_get($order, 'addressDistributor.city_code', null),
                        'distributor_city_name'     => array_get($order, 'addressDistributor.city_full_name', null),
                    ];
                }
            }
            $excel->setBodyArray($data);
            //ob_start();
            return $excel->download('reportPromotionProgram-' . date("Ymd") . '.xlsx');
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function pushOrder($code)
    {
        try {
            $orders       = explode(',', $code);
            $order        = Order::with(['details'])->whereIn('code', $orders)->get();
            $order_detail = [];
            // $token = $this->getTokenDMSOrder();
            foreach ($order as $od) {
                foreach ($od->details as $detail) {
                    $product      = Product::find($detail->product_id);
                    $detail_order = [
                        "itemCode"      => $detail->product_code,
                        "itemShortName" => $detail->product_name,
                        "qtyBook"       => $detail->qty * $product->specification->value,
                        "salesOff"      => $detail->special_percentage,
                        "costUnit"      => $detail->price / $product->specification->value,
                        "amount"        => $detail->total
                    ];
                    array_push($order_detail, $detail_order);
                }
                $param    = [
                    "orderNumber"     => $od->code,
                    "orderType"       => 'NTS',
                    "orderDate"       => date('d-m-Y H:i:s', strtotime($od->created_at)),
                    "status"          => 'W',
                    "outName"         => $od->customer_name,
                    "address"         => $od->shipping_address,
                    "province"        => $od->getCity->code,
                    "district"        => $od->getDistrict->code,
                    "ward"            => $od->getDistrict->code . '_' . $od->getWard->code,
                    "phone"           => $od->customer_phone,
                    "paymentMethod"   => $od->payment_method != "CASH" ? 'CK' : 'TM',
                    "payemtStatus"    => $od->payment_status != 1 ? "0" : "1",
                    "note"            => $od->note ?? null,
                    "createDate"      => date('d-m-Y H:i:s', time()),
                    "createdBy"       => "NutiFoodShop",
                    "modifyDate"      => null,
                    "modifiedBy"      => null,
                    "einvocie"        => !empty($od->invoice_company_name) ? "Y" : "N",
                    "shippingService" => $od->shipping_method_code != "DEFAULT" ? $od->shipping_method_code : "HUB",
                    "shippingOption"  => $od->shipping_note ?? null,
                    "distributorCode" => $od->distributor->group_code == "HUB" ? $od->distributor->distributor_center_code : $od->distributor_code,
                    "deliveryFee"     => $od->ship_fee ?? null,
                    "hubCode"         => $od->distributor->group_code != "DISTRIBUTOR" ? $od->distributor->code : null,
                    "saleOrderLines"  => $order_detail
                ];
                
                if(empty(env("DMS_ORDER"))){
                    return ['status' => 'Đồng bộ thành công!!'];
                }

                $client   = new Client();         
                $response = $client->post(env("DMS_ORDER") . "/SaleOrder", [
                    'headers' => ['Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . env("TOKEN_DMS_ORDER")],
                    'body'    => json_encode($param)
                ]);
            }
            $response = !empty($response) ? $response->getBody()->getContents() : null;
            $status   = !empty($response) ? $response : "Đồng bộ thành công!!";
            return ['status' => $status];
        } catch (\Exception $ex) {
            return $this->responseError('Đồng bộ không thành công!!!');
        }
    }

    public function updateManyHub($code, Request $request)
    {
        $input     = $request->all();
        $hub_code  = $input['hub_code'];
        $orderCode = explode(",", $code);
        $userHub   = User::model()->where([
            'code'       => $hub_code,
            'company_id' => TM::getCurrentCompanyId()
        ])->whereIn("group_code", [USER_GROUP_DISTRIBUTOR, USER_GROUP_HUB, USER_GROUP_DISTRIBUTOR_CENTER])->first();
        if (empty($userHub)) {
            return $this->response->errorBadRequest(Message::get("distributor.hub-not-exist", $input['hub_code']));
        }
        $orders = Order::model()->whereIn('code', $orderCode)->where([
            'company_id'           => TM::getCurrentCompanyId(),
            'shipping_method_code' => SHIPPING_PARTNER_TYPE_DEFAULT
        ])->get();
        if (count($orders) == 0) {
            return $this->response->errorBadRequest(Message::get("V003", Message::get("orders")));
        }
        $orderCodeReturn = [];
        foreach ($orders as $order) {
            $order->distributor_id    = $userHub->id;
            $order->distributor_code  = $userHub->code;
            $order->distributor_name  = $userHub->name;
            $order->distributor_email = $userHub->email;
            $order->distributor_phone = $userHub->phone;
            $order->save();
            $orderCodeReturn[] = $order->code;
        }
        return ['status' => Message::get("orders.update-success", implode(",", $orderCodeReturn))];
    }

    function getAutoSeller($company_id)
    {
        $seller = User::model()->select('id', 'code', 'name', 'parent_id')->withCount('countOrder as order_count')->where(['company_id' => $company_id, 'is_active' => 1])
            ->whereHas('role', function ($q) {
                $q->where('code', USER_ROLE_SELLER);
            })->where(function ($q) {
                $q->where('caller_start_time', '<', date('H:i:s'));
                $q->where('caller_end_time', '>', date('H:i:s'));
                $q->orWhereNull('caller_end_time');
            })
            ->orderBy('order_count', 'asc')
            ->first();
        return $seller;
    }

    public function getOrderDms($code)
    {
        $order = Order::model()->where('code', $code)->first();
        if (empty($order)) {
            return ['data' => []];
        }
        return ['data' => json_decode($order->log_order_dms)];
    }

    public function getOrderStatusDms($code)
    {
        $order = Order::model()->where('code', $code)->first();
        if (empty($order)) {
            return ['data' => []];
        }
        return ['data' => json_decode($order->log_status_order_dms)];
    }

    private function writeLogGrab($code, $quote, $response)
    {
        LogShippingOrder::insert([
            'order_code'           => $code,
            'type'                 => "QUOTE",
            'code_shipping_method' => null,
            'reponse_json'         => json_encode($response),
            'param_request'        => json_encode($quote),
        ]);
    }

    /**
     * @param $orderCode
     * @return void
     * @throws \Exception
     */
    public function repushOrderToDMS($code)
    {
        $order = Order::model()->where('code', $code)->first();
        if (!$order) {
            throw new \Exception(Message::get('V003', $code));
        }
        if ($order->status == ORDER_STATUS_COMPLETED){
            #CDP
            try {
                CDP::pushOrderCdp($order, 'repushOrderToDMS - OrderController - line:4848');
            } catch (\Exception $e) {
            }
        }
        try {
            $syncDMS = OrderSyncDMS::dataOrder(array($order->code), "C");
            if (!empty($syncDMS)) {
                $pushOrderDms = OrderSyncDMS::callApiDms($syncDMS, "CREATE-ORDER");
                if (!empty($pushOrderDms['errors'])) {
                    foreach ($pushOrderDms['errors'] as $item) {
                        Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $syncDMS ?? [], "CREATE-ORDER", 0, $item);
                    }
                } else {
//                    Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                    if (!empty($pushOrderDms)) {
                        Log::logSyncDMS($order->code, null, $syncDMS ?? [], "CREATE-ORDER", 1, $pushOrderDms);
                    }
                    if (empty($pushOrderDms)) {
                        Log::logSyncDMS($order->code, "Connection Error", $syncDMS ?? [], "CREATE-ORDER", 0, $pushOrderDms);
                    }
                }

            }
            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($syncDMS)]);
        } catch (\Exception $exception) {
            Log::logSyncDMS($order->code, $exception->getMessage(), $syncDMS ?? [], "CREATE-ORDER", 0, null);
        }

        return $this->responseData();
    }

    public function pushStatusOrderToDMS($code)
    {
        $order = Order::model()->where('code', $code)->first();
        if (empty($order)) {
            return ['data' => []];
        }
        if ($order->status == ORDER_STATUS_COMPLETED){
            #CDP
            try {
                CDP::pushOrderCdp($order, 'pushStatusOrderToDMS - OrderController - line:4881');
            } catch (\Exception $e) {
            }
        }
        try {
            $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
            if (!empty($dataUpdateDMS)) {
                $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                if (!empty($pushOrderStatusDms['errors'])) {
                    foreach ($pushOrderStatusDms['errors'] as $item) {
                        Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                    }
                } else {
                    if (!empty($pushOrderStatusDms)) {
                        Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                    }
                    if (empty($pushOrderStatusDms)) {
                        Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                    }
                }

            }
            Order::where('code', $order->code)->update(['push_cancel_to_dms' => 1]);
        } catch (\Exception $exception) {
            Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
        }
    }

    /**
     * @param $code
     * @return array|void
     * @throws \JsonException
     */
    public function jsonOrderDms($code)
    {
        $data = OrderSyncDMS::dataOrder(array($code), 'C');
        // $data[1]['json_encode'] = json_encode($data, JSON_THROW_ON_ERROR);
        return $data;
    }

    /**
     * @param $code
     * @return array
     */
    public function jsonStatusOrderDms($code)
    {
        $order = Order::model()->where('code', $code)->first();
        if (empty($order)) {
            return ['data' => []];
        }
        $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
        return $dataUpdateDMS;
    }

    public function adminConfirmOrder(Request $request) {
        $input = $request->all();
        (new AdminConfirmOrderValidator())->validate($input);
        try {
            // dd($input);
            DB::beginTransaction();
            $autoCode = $this->getAutoOrderCode();
            $cart = Cart::findOrFail($input['cart_id']);
            if(empty($cart)) {
                return $this->responseError("Không tồn tại giỏ hàng");
            }
            $status = OrderStatus::where('code', ORDER_STATUS_NEW)->first();
            $user = User::find($cart->user_id ?? null);
            $shipping_method_name = ConfigShipping::where('code', $cart->shipping_method_code)->first();
            // dd($cart->total_info);
            $total_info = $cart->total_info;

            $totalPrice = $originalPrice = $subTotalPrice = 0;
            //            $freeShip      = false;
            $customerPoint     = null;
            $seller_id         = null;
            $settingAutoSeller = Setting::model()->select('data')->where(['code' => 'CRMAUTO', 'company_id' => TM::getCurrentCompanyId()])->first();
            if (!empty($settingAutoSeller) && !empty(json_decode($settingAutoSeller['data'])[0]->value) && json_decode($settingAutoSeller['data'])[0]->value == 1) {
                $seller_id = $this->getAutoSeller(TM::getCurrentCompanyId());
            }
            // dd($cart->details);
            foreach ($cart->details as $detail) {
                $originalPrice += Arr::get(
                    $detail->product,
                    'priceDetail.price',
                    Arr::get($detail->product, 'price', 0)
                );
            }
            // $sub_total_price = 
            $session_id = $cart->session_id ?? null;
            // dd($cart->total_info );
            foreach ($cart->total_info as $item) {
                switch ($item['code']) {
                    case 'sub_total':
                        $subTotalPrice = $item['value'];
                        break;
                    case 'total':
                        $totalPrice = $item['value'];
                        break;
                    default:
                        if (!empty($item['act_type'])) {
                            //                            if ($item['act_type'] == 'free_shipping') {
                            //                                $freeShip = true;
                            //                            }

                            if ($item['act_type'] == 'accumulate_point') {
                                $customerPoint = $item['value'];
                            }
                        }
                        break;
                }
            }
            $param_order = [
                'cart_id' => $cart->id,
                'code' => $autoCode,
                'order_type' => ORDER_TYPE_GUEST,
                'status' =>$status->code,
                'status_text' => $status->name,
                'status_crm' => ORDER_STATUS_PENDING,
                'order_type' => 'GUEST',
                'customer_id' => $cart->user_id ?? null,
                'qr_scan' => 0,
                'customer_name' => $cart->full_name ?? null,
                'customer_code' => $user->code ?? null,
                'customer_phone' => $cart->phone ?? null,
                'phone' =>$cart->phone ?? null,
                'shipping_address' => $cart->address ?? null,
                'coupon_code' => $cart->coupon_code ?? null,
                'voucher_code' => $cart->voucher_code ?? null,

                'discount_admin_input_type' => $cart->discount_admin_input_type ?? null,
                'discount_admin_input'     => $cart->discount_admin_input ?? null,
                'coupon_admin'             => $cart->coupon_admin ?? "{}",
                // 'sub_total_price' => 
                'customer_lat' => Arr::get($input, 'customer_lat', $cart->customer_lat),
                'customer_long'                  => Arr::get($input, 'customer_long', $cart->customer_long),
                'customer_postcode'              => Arr::get($input, 'customer_postcode', $cart->customer_postcode),
                'session_id'                     => $session_id,
                'customer_point'                 => $customerPoint ?? null,
                'note'                           => $input['note'] ?? null,
                'street_address'                 => $cart->street_address ?? null,
                'shipping_address'               => $cart->address ?? null,
                'shipping_address_id'            => $cart->shipping_address_id,
                'shipping_address_ward_code'     => $cart->customer_ward_code ?? null,
                'shipping_address_ward_type'     => $cart->customer_ward_code->getWard->type ?? null,
                'shipping_address_ward_name'     => $cart->customer_ward_code->getWard->name ?? null,
                'shipping_address_district_code' => $cart->customer_district_code ?? null,
                'shipping_address_district_type' => $cart->getDistrict->type ?? null,
                'shipping_address_district_name' => $cart->getDistrict->name ?? null,
                'shipping_address_city_code'     => $cart->customer_city_code ?? null,
                'shipping_address_city_type'     => $cart->getCity->type ?? null,
                'shipping_address_city_name'     => $cart->getCity->name ?? null,
                'payment_method' => $cart->payment_method ?? null,
                'shipping_method' => $cart->shipping_method ?? null,
                'shipping_method_code' => $cart->shipping_method_code ?? null,
                'shipping_method_name' => $shipping_method_name->shipping_partner_name ?? null ,
                'shipping_service' =>  $shipping_method_name->shipping_partner_code == 'VIETTELPOST' ? "NCOD":  ($shipping_method_name->shipping_partner_code == 'GRAB' ? 'INSTANT' :  null),
                'shipping_note' => $cart->shipping_note ?? null,
                'extra_service' => $cart->extra_service ?? null,
                'saving'                         => $cart->saving ?? null,
                'ship_fee'                       => $cart->ship_fee ?? null,
                'ship_fee_start' => $cart->ship_fee_start ?? 0,
                'estimated_deliver_time' => $cart->estimated_deliver_time ?? null,
                'lading_method' => $cart->lading_method ?? null,
                'total_weight' => $cart->total_weight ?? 0,
                
                'intersection_distance'          => $cart->intersection_distance ?? 0,
                'invoice_city_code'              => $input['invoice_city_code'] ?? null,
                'invoice_city_name'              => $input['invoice_city_name'] ?? null,
                'invoice_district_code'          => $input['invoice_district_code'] ?? null,
                'invoice_district_name'          => $input['invoice_district_name'] ?? null,
                'invoice_ward_code'              => $input['invoice_ward_code'] ?? null,
                'invoice_ward_name'              => $input['invoice_ward_name'] ?? null,
                'invoice_street_address'         => $input['invoice_street_address'] ?? null,
                'invoice_company_name'           => $input['invoice_company_name'] ?? null,
                'invoice_company_email'          => $input['invoice_company_email'] ?? null,
                'invoice_tax'                    => $input['invoice_tax'] ?? null,
                'invoice_company_address'        => $input['invoice_company_address'] ?? null,
                'created_date'                   => $cart->created_at,
                'delivery_time'                  => $cart->receiving_time,
                'access_trade_id'                => $cart->access_trade_id ?? null,
                'access_trade_click_id'          => $cart->access_trade_click_id ?? null,
                'order_source'                   => $cart->order_source ?? null,
                'latlong'                        => $cart->ship_address_latlong,
                'lat'                            => $lat_long[0] ?? 0,
                'long'                           => $lat_long[1] ?? 0,
                'coupon_code'                    => $cart->coupon_code ?? null,
                'coupon_discount_code'           => $cart->coupon_discount_code ?? null,
                'coupon_delivery_code'           => $cart->coupon_delivery_code ?? null,
                'delivery_discount_code'         => $cart->delivery_discount_code ?? null,
                'voucher_code'                   => $cart->voucher_code ?? null,
                'voucher_discount_code'          => $cart->voucher_discount_code ?? null,
                'total_discount'                 => 0,
                'original_price'                 => $originalPrice,
                'total_price'                    => $totalPrice,
                'sub_total_price'                => $subTotalPrice,
                'is_freeship'                    => $cart->is_freeship ?? 0,
                'order_channel'                  => $input['order_channel'] ?? null,
                'distributor_id'                 => Arr::get($input, 'distributor_id', $cart->distributor_id),
                'distributor_code'               => Arr::get($input, 'distributor_code', $cart->distributor_code),
                'distributor_name'               => Arr::get($input, 'distributor_name', $cart->distributor_name),
                'distributor_email'              => Arr::get($input, 'distributor_email', $cart->distributor_email),
                'distributor_phone'              => Arr::get($input, 'distributor_phone', $cart->distributor_phone),
                'distributor_lat'                => Arr::get($input, 'distributor_lat', $cart->distributor_lat),
                'distributor_long'               => Arr::get($input, 'distributor_long', $cart->distributor_long),
                'distributor_postcode'           => Arr::get($input, 'distributor_postcode', $cart->distributor_postcode),
                'store_id'                       => $user->store_id,
                'is_active'                      => 1,
                //                'seller_id'                      => Arr::get($input, 'seller_id', null),
                //                'seller_code'                    => Arr::get($input, 'seller_code', null),
                //                'seller_name'                    => Arr::get($input, 'seller_name', null),
                'seller_id'                      => !empty($seller_id) ? $seller_id->id : null,
                'seller_code'                    => !empty($seller_id) ? $seller_id->code : null,
                'seller_name'                    => !empty($seller_id) ? $seller_id->name : null,
                'leader_id'                      => !empty($seller_id) ? $seller_id->parent_id : null,
                'total_info'                     => json_encode($cart->total_info),
                'free_item'                      => !empty($cart->free_item) ? ($cart->free_item) : null, 'outvat' => !empty($input['invoice_company_name']) ? 1 : 0,
                'qr_scan'                        => $cart->qr_scan ?? 0,
                'transfer_confirmation'          => !empty($input['payment_method']) && ($input['payment_method'] == 'bank_transfer') ? 0 : 1,
                'status_crm'                     => ORDER_STATUS_CRM_PENDING,
                'free_item_admin'                => !empty($input['free_item_admin']) ? json_encode($input['free_item_admin']) : null,
            ];
            $order = Order::create($param_order);
            // dd($order);
            $cart_details = $cart->details ?? null;
            // dd($cart_details);
            foreach ($cart_details as $detail) {
                OrderDetail::create([
                    'order_id'           => $order->id,
                    'product_id'         => $detail->product_id,
                    'product_code'       => $detail->product_code,
                    'product_name'       => $detail->product_name,
                    'product_category'   => $detail->product_category,
                    'qty'                => $detail->quantity,
                    'qty_sale'           => $detail->qty_sale_re ?? null,
                    'price'              => $detail->price,
                    'discount'           => !empty($detail->special_percentage) && $detail->special_percentage > 0 && $detail->promotion_price <= 0 ? $detail->price * ($detail->special_percentage / 100) : $detail->promotion_price,
                    'special_percentage' => empty($detail->special_percentage) && !empty($detail->promotion_price) ? round(($detail->promotion_price / $detail->price) * 100) : round($detail->special_percentage),
                    'real_price'         => $detail->price,
                    'price_down'         => 0,
                    'total'              => $detail->total,
                    'note'               => $detail->note,
                    'status'             => ORDER_HISTORY_STATUS_PENDING,
                    'is_active'          => 1,
                    'item_value'         => $detail->item_value ?? null,
                    'item_type'          => $detail->item_type ?? null,
                ]);
            }

            // Update users
            $updateUser = User::model()->where([
                'phone'      => $input['phone'],
                'store_id'   => $user->store_id,
                'company_id' => $user->company_id
            ])->first();

            if ($updateUser) {
                $last_name  = explode(" ", $input['full_name']);
                $last_name  = array_pop($last_name);
                $first_name = explode(" ", $input['full_name']);
                $first_name = array_shift($first_name);
                $short_name = "{$first_name} {$last_name}";
                if (!empty($input['email'])) {
                    if ($updateUser->email != $input['email']) {
                        $chekEmail = User::model()->where([
                            'email'    => $input['email'],
                            'store_id' => $user->store_id
                        ])->first();
                        if (!empty($chekEmail)) {
                            throw new \Exception(Message::get("V007", "Enail: #{$input['email']}"));
                        }
                    }
                }
                $updateUser->name = $input['full_name'] ?? $updateUser->name;
                // $updateUser->email = $input['email'] ?? $updateUser->email;

                //Send Sms code
                // $codeSMS               = mt_rand(100000, 999999);
                // $updateUser->password  = password_hash($codeSMS, PASSWORD_BCRYPT);
                // $updateUser->is_active = 1;
                // $this->sendSMSCode(Message::get('SMS-REGISTER-ORDER', $codeSMS), $user->phone);
                $updateUser->save();

                $profile = Profile::model()->where('user_id', $updateUser->id)->first();
                if (!empty($profile)) {
                    $profile->email         = $input['email'] ?? $profile->email;
                    $profile->first_name    = $first_name ?? $profile->first_name;
                    $profile->last_name     = $last_name ?? $profile->last_name;
                    $profile->short_name    = $short_name ?? $profile->short_name;
                    $profile->full_name     = $input['full_name'] ?? $profile->full_name;
                    $profile->city_code     = $input['city_code'] ?? $profile->city_code;
                    $profile->ward_code     = $input['ward_code'] ?? $profile->ward_code;
                    $profile->district_code = $input['district_code'] ?? $profile->district_code;
                    $profile->address       = $input['address'] ?? $updateUser->profile->address;
                    $profile->save();
                }
                $now = date("Y-m-d H:i:s", time());
                //Create Shipping Address
                ShippingAddress::insert(
                    [
                        'user_id'        => $updateUser->id,
                        'full_name'      => $updateUser->name,
                        'phone'          => $updateUser->phone,
                        'city_code'      => $input['customer_city_code'] ?? null,
                        'district_code'  => $input['customer_district_code'] ?? null,
                        'ward_code'      => $input['customer_ward_code'] ?? null,
                        'street_address' => $input['street_address'] ?? null,
                        'company_id'     => $updateUser->company_id,
                        'store_id'       => $updateUser->store_id ?? null,
                        'is_default'     => 1,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ]
                );
            }

            $couponHistory = CouponHistory::model()->where('order_id', $order->id)->first();
            if (!empty($cart->coupon_discount_code)) {

                $coupon_check = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                    ->where('coupon_codes.code', $cart->coupon_discount_code)->first();

                $coupon_order_used   = CouponCodes::where('code', $cart->coupon_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->coupon_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);

                $count_history = CouponHistory::model()->where('coupon_discount_code', $cart->coupon_discount_code)->count();
                if (($count_history + 1) >= $coupon_check->uses_total) {
                    DB::table('coupon_codes')->where('code', $cart->coupon_discount_code)->update(['is_active' => '1', 'order_used' => $coupon_order_used]);
                }

                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->coupon_name;
                $couponHistory->coupon_discount_code = $cart->coupon_discount_code;
                $couponHistory->coupon_code          = $cart->coupon_code;
                $couponHistory->total_discount       = $cart->coupon_price > $subTotalPrice ? $subTotalPrice : $cart->coupon_price;
                $couponHistory->save();
            }
            if (!empty($cart->delivery_discount_code)) {

                $coupon_check = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
                    ->where('coupon_codes.code', $cart->delivery_discount_code)->first();

                $coupon_order_used   = CouponCodes::where('code', $cart->delivery_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->delivery_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);

                $count_history = CouponHistory::model()->where('coupon_discount_code', $cart->delivery_discount_code)->count();
                if (($count_history + 1) >= $coupon_check->uses_total) {
                    DB::table('coupon_codes')->where('code', $cart->delivery_discount_code)->update(['is_active' => '1', 'order_used' => $coupon_order_used]);
                }

                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->coupon_delivery_name;
                $couponHistory->coupon_discount_code = $cart->delivery_discount_code;
                $couponHistory->coupon_code          = $cart->coupon_delivery_code;
                $couponHistory->total_discount       = $cart->coupon_delivery_price;
                $couponHistory->save();
            }

            if (!empty($cart->voucher_discount_code)) {
                $voucher = DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->first();
                if (($voucher->discount - $subTotalPrice) <= 0) {
                    DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->update(['is_active' => '1', 'order_used' => $order->id]);
                }
                DB::table('coupon_codes')->where('code', $cart->voucher_discount_code)->update(['discount' => $voucher->discount - $cart->voucher_value_use]);
                $couponHistory                       = new CouponHistory();
                $couponHistory->order_id             = $order->id;
                $couponHistory->user_id              = $order->customer_id;
                $couponHistory->coupon_name          = $cart->voucher_title;
                $couponHistory->coupon_discount_code = $cart->voucher_discount_code;
                $couponHistory->coupon_code          = $cart->voucher_code;
                $couponHistory->total_discount       = $cart->voucher_value_use;
                $couponHistory->save();

                $coupon_order_used   = CouponCodes::where('code', $cart->voucher_discount_code)->value('order_used');
                $coupon_order_used   = !empty($coupon_order_used) ? explode(",", $coupon_order_used) : [];
                $coupon_order_used[] = $order->code ?? null;
                CouponCodes::where('code', $cart->voucher_discount_code)->update([
                    'order_used' => implode(",", $coupon_order_used)
                ]);
            }


            $cart->details->each(function ($detail) {
                $detail->delete();
            });
            $cart->delete();

            DB::commit();

            return response()->json([
                'message' => 'Thêm đơn hàng thành công'
            ], 200);
        } catch (\Exception $ex) {
            // dd($ex);
            //throw $th;
            DB::rollBack();
            return $this->response->error($ex->getMessage(), $ex->getCode());
        }


    }
}
