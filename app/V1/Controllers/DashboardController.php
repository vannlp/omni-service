<?php

/**
 * User: dai.ho
 * Date: 10/30/2019
 * Time: 02:04 PM
 */

namespace App\V1\Controllers;


use App\Order;
use App\Supports\Message;
use App\TM;
use App\UserStatusOrder;
use App\V1\Models\OrderModel;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\TopCustomerHighestSaleExport;
use App\Exports\TopProductByCustomerExport;
use App\Product;
use App\Store;

class DashboardController extends BaseController
{
    /**
     * @var OrderModel
     */
    protected $orderModel;

    /**
     * BannerController constructor.
     */
    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    public function orderStatus()
    {
        $user     = TM::info();
        $typeUser = $user['type'] == "USER" ? null : ($user['type'] == USER_TYPE_PARTNER ? 'partner' : 'customer');
        if (empty($typeUser)) {
            return $this->response->errorBadRequest(Message::get("V002", "User"));
        }

        $completedOrder = Order::model()->where('status', ORDER_STATUS_COMPLETED)
            ->where($typeUser . '_id', TM::getCurrentUserId())
            ->get()->toArray();
        $receivedOrder  = Order::model()->where('status', ORDER_STATUS_RECEIVED)
            ->where($typeUser . '_id', TM::getCurrentUserId())
            ->get()->toArray();
        $totalPrice     = Order::model()->select(DB::raw('sum(total_price) as total_price'))
            ->where('status', ORDER_STATUS_COMPLETED)
            ->where($typeUser . '_id', TM::getCurrentUserId())
            ->first()->toArray();

        $userStatusOrder = UserStatusOrder::model()->select([DB::raw("count(status) as qty"), "status"])
            ->where('user_id', TM::getCurrentUserId())->groupBy('status')->get()->pluck("qty", "status")->toArray();

        $receiveQty = $userStatusOrder["RECEIVED"] ?? 0;
        $cancelQty  = $userStatusOrder["CANCELLED"] ?? 0;

        $rejectOrder = UserStatusOrder::model()->select(DB::raw('count(order_id) as reject'))
            ->where('user_id', TM::getCurrentUserId())
            ->groupBy('order_id')->first();

        return [
            "completed"          => count($completedOrder),
            "received"           => count($receivedOrder),
            "total_revenue"      => $totalPrice['total_price'] ?? 0,
            "receive_per_cancel" => $receiveQty . "/" . $cancelQty,
            "reject_order"       => $rejectOrder->reject ?? 0,
        ];
    }

    public function dashboardRevenueMonth(Request $request)
    {
        $input = $request->all();
        if (!empty($input['from']) && !empty($input['to'])) {
            $monthFrom    = date("Y-m-d", strtotime($input['from']));
            $monthTo      = date("Y-m-d", strtotime($input['to']));
            $dt           = Carbon::create($monthFrom);
            $now          = Carbon::create($monthTo);
            $diffInYears  = $now->diffInYears($dt);
            $diffInMonths = $now->diffInMonths($dt);
            $monthF       = date("m", strtotime($input['from']));
            $monthT       = date("m", strtotime($input['to']));
            $yearFrom     = date("Y", strtotime($input['from']));
            $yearTo       = date("Y", strtotime($input['to']));
            $year         = $yearFrom;

            if ($diffInYears != 0) {
                $monthTemp = $monthF;
                for ($i = (int)$monthF; $i <= (int)$diffInMonths + $monthF; $i++) {
                    $revenueMonth = Order::model()
                        ->whereMonth('orders.created_at', $monthTemp)
                        ->whereYear('orders.created_at', $year)
                        ->where('customer_id', TM::getCurrentUserId())
                        ->where('status', ORDER_STATUS_COMPLETED);
                    $result[]     = [
                        'month'   => $monthTemp,
                        'year'    => $year,
                        'revenue' => $revenueMonth->sum('total_price')
                    ];
                    if ($monthTemp == 12) {
                        $monthTemp = 0;
                        $year      += 1;
                    }

                    $monthTemp++;
                }
            } else {
                for ($i = (int)$monthF; $i <= (int)$monthT; $i++) {
                    $revenueMonth = Order::model()
                        ->whereMonth('orders.created_at', $i)
                        ->where('customer_id', TM::getCurrentUserId())
                        ->where('status', ORDER_STATUS_COMPLETED);
                    $result[]     = [
                        'month'   => $i,
                        'revenue' => $revenueMonth->sum('total_price')
                    ];
                }
            }
        } else {
            $monthFrom = 1;
            $monthTo   = 12;
            for ($i = $monthFrom; $i <= $monthTo; $i++) {
                $revenueMonth = Order::model()
                    ->whereMonth('orders.created_at', $i)
                    ->where('customer_id', TM::getCurrentUserId())
                    ->where('status', ORDER_STATUS_COMPLETED);
                $result[]     = [
                    'month'   => $i,
                    'revenue' => $revenueMonth->sum('total_price')
                ];
            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

    public function topCustomerHighestSale(Request $request)
    {
        //ob_end_clean();
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $type = TM::getCurrentUserType();
        if ($type != USER_TYPE_CUSTOMER) {
            return $this->responseData([]);
        }
        $storeId = TM::getCurrentStoreId();
        $order   = Order::model()
            ->where('store_id', $storeId)
            ->where('status', ORDER_STATUS_COMPLETED)
            ->select([
                'customer_id',
                'customer_code',
                'customer_name',
                DB::raw("SUM(total_price) as total_price"),
                DB::raw("COUNT(id) as total_order")
            ])
            ->groupBy('customer_id', 'customer_name')
            ->limit($limit)
            ->get()->toArray();

        if (!empty($input['export']) && $input['export'] == 1) {
            //ob_start();
            return Excel::download(new TopCustomerHighestSaleExport($order), 'top-customer-highest-sale.xlsx');
        } else {
            return $this->responseData($order);
        }
    }

    public function topProductByCustomer(Request $request)
    {
        //ob_end_clean();
        $store_id = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
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
                    $store_id = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }


        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['company_id'] = $company_id;
        $type = TM::getCurrentUserType();
        // if ($type != USER_TYPE_CUSTOMER) {
        //     return $this->responseData([]);
        // }
        $storeId = $store_id;
        $order   = Order::model()
            ->where('orders.store_id', $storeId)
            ->where('orders.status', ORDER_STATUS_COMPLETED)
            ->select([
                'p.id',
                'odetail.product_name as name',
                'p.code',
                'p.slug',
                'p.type',
                'p.tax',
                'p.tags',
                'p.short_description',
                'p.description',
                'p.category_ids',
                'p.price',
                'p.sku',
                // DB::raw("FORMAT(p.price,0) as price_formated"),
                DB::raw("CONCAT(FORMAT(p.price,0),' đ')as price_formated"),
                DB::raw("concat('" . (env('GET_FILE_URL')) . "', f.code) as thumbnail"),
                DB::raw("COUNT('odetail.qty') as qty")
            ])
            ->join('order_details as odetail', 'odetail.order_id', '=', 'orders.id')
            ->join('products as p', 'odetail.product_id', '=', 'p.id')
            ->join('files as f', 'p.thumbnail', '=', 'f.id')
            ->groupBy('name')
            ->orderBy('qty', 'desc')
            ->limit($limit)
            ->get()->toArray();
        // print_r($order);die;
        if (!empty($input['export']) && $input['export'] == 1) {
            //ob_start();
            return Excel::download(new TopProductByCustomerExport($order), 'top-product-by-customers.xlsx');
        } else {
            return $this->responseData($order);
        }
    }
    #################################### NO AUTITHENCATION ####################################
    // public function getClientTopProductByCustomer(Request $request)
    // { 
    //     $store_id = null;
    //     $company_id = null;
    //     if (TM::getCurrentUserId()) {
    //         $store_id = TM::getCurrentStoreId();
    //         $company_id = TM::getCurrentCompanyId();
    //     } else {
    //         $headers = $request->headers->all();
    //         if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
    //             $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
    //             if ($store_token_input && strlen($store_token_input) == 64) {
    //                 $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
    //                 if (!$store) {
    //                     return ['data' => []];
    //                 }
    //                 $store_id = $store->id;
    //                 $company_id = $store->company_id;
    //             }
    //         }
    //     }


    //     $input = $request->all();
    //     $limit = array_get($input, 'limit', 20);
    //     $input['company_id'] = $company_id;
    //     $type = TM::getCurrentUserType();
    //     // if ($type != USER_TYPE_CUSTOMER) {
    //     //     return $this->responseData([]);
    //     // }
    //     $storeId = $store_id;
    //     $order   = Order::model()
    //         ->where('orders.store_id', $storeId)
    //         ->where('orders.status', ORDER_STATUS_COMPLETED)
    //         ->select([
    //             // 'odetail.id as id',
    //             // 'orders.customer_id as Cid',
    //             // 'orders.code as code',
    //             // 'orders.customer_code as Ccode',
    //             'orders.customer_name as name', 
    //             // 'odetail.product_code as Pcode', 
    //             'odetail.product_name as product_name', 
    //             // 'odetail.total as total', 
    //             // 'odetail.qty as qty', 
    //             // 'products.id as id', 
    //             DB::raw("SUM(odetail.qty) as total_buy"),
    //             DB::raw("COUNT('odetail.product_name') as count_product_name")
    //         ])
    //         ->join('order_details as odetail', 'odetail.order_id','=', 'orders.id')
    //         // ->join('order_details as odetail', 'odetail.product_id','=', 'products.id')

    //         // ->join('files as f', 'f.id','=','')
    //         // ->join('producs as p', 'p.thumbnail','=','files.id')
    //         ->groupBy('name')
    //         ->orderBy('total_buy', 'desc')
    //         ->limit($limit)
    //         ->get()->toArray();


    //         return $this->responseData($order);

    // }


    public function getClientRevenueRecent(Request $request)
    {
        // $store_id = null;
        // $company_id = null;
        // if (TM::getCurrentUserId()) {
        //     $store_id = TM::getCurrentStoreId();
        //     $company_id = TM::getCurrentCompanyId();
        // } else {
        //     $headers = $request->headers->all();
        //     if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
        //         $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
        //         if ($store_token_input && strlen($store_token_input) == 64) {
        //             $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
        //             if (!$store) {
        //                 return ['data' => []];
        //             }
        //             $store_id = $store->id;
        //             $company_id = $store->company_id;
        //         }
        //     }
        // }

        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        // $input['company_id'] = $company_id;
        $storeId = TM::getCurrentStoreId();
        // $customer_id = TM::getCurrentUserId();
        // print_r($customer_id);die;
        if (!empty($input['date'])) {
            $monthT = date("m", strtotime($input['date']));
            $year   = date("Y", strtotime($input['date']));
            switch ($monthT) {
                case "1":
                    $month = [11, 12, 1];
                    break;
                case "2":
                    $month = [12, 1, 2];
                    break;
                case "3":
                    $month = [1, 2, 3];
                    break;
                case "4":
                    $month = [2, 3, 4];
                    break;
                case "5":
                    $month = [3, 4, 5];
                    break;
                case "6":
                    $month = [4, 5, 6];
                    break;
                case "7":
                    $month = [5, 6, 7];
                    break;
                case "8":
                    $month = [6, 7, 8];
                    break;
                case "9":
                    $month = [7, 8, 9];
                    break;
                case "10":
                    $month = [8, 9, 10];
                    break;
                case "11":
                    $month = [9, 10, 11];
                    break;
                case "12":
                    $month = [10, 11, 12];
                    break;
            }
            // print_r($yearTemp);die;
            foreach ($month as $value) {
                $yearTemp = $year;
                if ($monthT == 1 || $monthT == 2) {
                    if ($value == 11 || $value == 12) {
                        $yearTemp = $year - 1;
                    }
                }
                $revenueMonth = Order::model()
                    ->where('orders.store_id', $storeId)
                    ->whereMonth('orders.created_at', $value)
                    ->whereYear('orders.created_at', $yearTemp)
                    ->where('customer_id', TM::getCurrentUserId())
                    ->where('orders.status', ORDER_STATUS_COMPLETED)
                    ->join('order_details as odetail', 'odetail.order_id', '=', 'orders.id')
                    ->sum('total_price');
                $result[]     = [
                    'month'   => $value,
                    'year' => $yearTemp,
                    'total_price' => $revenueMonth,
                    'total_price_formatted' => number_format($revenueMonth) . ' đ',
                ];
            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }
}
