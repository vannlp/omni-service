<?php


namespace App\V1\Controllers;


use App\File;
use App\Order;
use App\OrderDetail;
use App\PaymentHistory;
use App\Product;
use App\SearchHistory;
use App\UserSession;
use App\TM;
use App\User;
use App\V1\Models\OrderModel;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StatisticController extends BaseController
{
    protected $orderModel;

    public function __construct()
    {
        $this->orderModel = new OrderModel();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    //biểu đồ doanh thu theo ngày
    public function statisticRevenueDay(Request $request)
    {
        $input = $request->all();
        $to = strtotime ( '+1 day' , strtotime ($input['to']));
        $dateto = date ( 'Y-m-d' , $to );
        $query = Order::model()
            ->select([
                DB::raw("DATE_FORMAT(created_at, '%d-%m-%Y') as date"),
                DB::raw("(sum(total_price) - sum(total_discount)) as total_price"),
            ])
            ->where('status', 'COMPLETED')
            ->groupBy('date');
        if (!empty($input['from']) && !empty($input['to'])) {
            $query = $query->whereBetween('created_at', [$input['from'], $dateto]);
        }
        if (!empty($input['store_id'])) {
            $query = $query->where('store_id', $input['store_id']);
        }
        $data = $query->get();
        $data = array_pluck($data, null, 'date');
        if (!empty($input['from']) && !empty($input['to'])) {
            $day       = $this->getDatesBetween($input['from'], $input['to']);
            $dataJonTo = [];
            foreach ($day as $value) {
                $detailData = array_get($data, $value);
                if (empty($detailData)) {
                    $dataJonTo[$value]['date']        = $value;
                    $dataJonTo[$value]['total_price'] = 0;
                    continue;
                }
                $dataJonTo[] = $detailData;
            }
            $dataJonTo = array_values($dataJonTo);
            return response()->json(['data' => $dataJonTo]);
        } else {
            $data        = $query->take(6)->get();
            $datas       = array_pluck($data, null, 'date');
            $dataJon     = [];
            $getWeekDays = $this->orderModel->getWeekDays($data[0]->date, $output_format = 'd-m-Y');

            foreach ($getWeekDays as $dayValue) {
                $detailData = array_get($datas, $dayValue);
                if (empty($detailData)) {
                    $dataJon[$dayValue]['date']        = $dayValue;
                    $dataJon[$dayValue]['total_price'] = 0;
                    continue;
                }
                $dataJon[] = $detailData;
            }
            $dataJon = array_values($dataJon);
        }
        return response()->json(['data' => $dataJon]);
    }

    //biểu đồ doanh thu theo tháng
    public function statisticRevenueMonth(Request $request)
    {
        $input = $request->all();
        if (!empty($input['from']) && !empty($input['to'])) {
            $monthFrom = date("m", strtotime($input['from']));
            $yearFrom  = date("Y", strtotime($input['from']));
            $monthTo   = date("m", strtotime($input['to']));
            $yearTo   = date("Y", strtotime($input['to']));
            $yearF = $yearFrom;

            if($yearFrom ==  $yearTo){
                for ($i = $monthFrom; $i <= $monthTo; $i++) {
                    $orderMonth = Order::model()
                        ->where([
                            'deleted'=>0,
                            'status' => ORDER_STATUS_COMPLETED,
                            'store_id'=> TM::getCurrentStoreId(),
                        ])
                        ->whereMonth('orders.created_at', $i)
                        ->whereYear('orders.created_at', $yearTo);
                    $total_price = $orderMonth->sum('total_price') - $orderMonth->sum('total_discount');
                    $result[]   = [
                        'month'       => (int)$i,
                        'total_price' => (int)$total_price
                    ];
                }
            } else {
                do {
                $yearTmp = $yearFrom;
                $monthTmp = 12;
                if($yearFrom==$yearTo){
                    $monthTmp=(int)$monthTo;
                    for ($i = 1; $i <= $monthTmp; $i++) {
                        $orderMonth = Order::model()
                            ->where([
                                'deleted'=>0,
                                'status' => ORDER_STATUS_COMPLETED,
                                'store_id'=> TM::getCurrentStoreId(),
                            ])
                            ->whereMonth('orders.created_at', $i)
                            ->whereYear('orders.created_at',$yearTo);
                        $total_price = $orderMonth->sum('total_price') - $orderMonth->sum('total_discount');
                        $result[]   = [
                            'month'       => (int)$i,
                            'total_price' => (int)$total_price
                        ];
                    }
                }
                elseif($monthFrom == 12 and $yearF != $yearFrom-1){
                    for ($i = 12; $i <= $monthTmp; $i++) {
                        $orderMonth = Order::model()
                            ->where([
                                'deleted'=>0,
                                'status' => ORDER_STATUS_COMPLETED,
                                'store_id'=> TM::getCurrentStoreId(),
                            ])
                            ->whereMonth('orders.created_at', $i)
                            ->whereYear('orders.created_at',$yearTmp);
                        $total_price = $orderMonth->sum('total_price') - $orderMonth->sum('total_discount');
                        $result[]   = [
                            'month'       => (int)$i,
                            'total_price' => (int)$total_price
                        ];
                    }
                }
                elseif($yearF < $yearFrom and $yearFrom < $yearTo){
                    for ($i = 1; $i <= $monthTmp; $i++) {
                        $orderMonth = Order::model()
                            ->where([
                                'deleted'=>0,
                                'status' => ORDER_STATUS_COMPLETED,
                                'store_id'=> TM::getCurrentStoreId(),
                            ])
                            ->whereMonth('orders.created_at', $i)
                            ->whereYear('orders.created_at',$yearTmp);
                        $total_price = $orderMonth->sum('total_price') - $orderMonth->sum('total_discount');
                        $result[]   = [
                            'month'       => (int)$i,
                            'total_price' => (int)$total_price
                        ];
                    }
                }
                else{
                for ($i = $monthFrom; $i <= $monthTmp; $i++) {
                    $orderMonth = Order::model()
                        ->where([
                            'deleted'=>0,
                            'status' => ORDER_STATUS_COMPLETED,
                            'store_id'=> TM::getCurrentStoreId(),
                        ])
                        ->whereMonth('orders.created_at', $i)
                        ->whereYear('orders.created_at',$yearTmp);
                    $total_price = $orderMonth->sum('total_price') - $orderMonth->sum('total_discount');
                    $result[]   = [
                        'month'       => (int)$i,
                        'total_price' => (int)$total_price
                    ];
                }
                }
                $yearFrom+=1;
            }
                while($yearFrom <=  $yearTo);
            }
        } else {
            $monthFrom = 1;
            $monthTo   = 12;
            for ($i = $monthFrom; $i <= $monthTo; $i++) {
                $orderMonth = Order::model()
                    ->where([
                        'deleted'=>0,
                        'status' => ORDER_STATUS_COMPLETED,
                        'store_id'=> TM::getCurrentStoreId(),
                    ])
                    ->whereMonth('orders.created_at', $i);
                $total_price = $orderMonth->sum('total_price') - $orderMonth->sum('total_discount');
                $result[]   = [
                    'month'       => $i,
                    'total_price' => (int)$total_price
                ];
            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

    //biểu đồ doanh thu theo năm
    public function statisticRevenueYear(Request $request)
    {
        $input  = $request->all();
        $result = [];
        if (!empty($input['from']) && !empty($input['to'])) {
            $yearFrom = (int)$input['from'];
            $yearTo   = (int)$input['to'];
            for ($i = $yearFrom; $i <= $yearTo; $i++) {

                $orderYear = Order::model()
                    ->where([
                        'deleted'=>0,
                        'status' => ORDER_STATUS_COMPLETED,
                        'store_id'=> TM::getCurrentStoreId(),
                    ])
                    ->whereYear('orders.created_at', $i);
                    $total_price = $orderYear->sum('total_price') - $orderYear->sum('total_discount');
                    $result[]  = [
                        'year'        => (int)$i,
                        'total_price' => (int)$total_price
                    ];
                   
            }
        } else {
            $yearFrom = 2019;
            $yearTo   = 2026;
            for ($i = $yearFrom; $i <= $yearTo; $i++) {
                $orderYear = Order::model()
                    ->where([
                        'deleted'=>0,
                        'status' => ORDER_STATUS_COMPLETED,
                        'store_id'=> TM::getCurrentStoreId(),
                    ])
                    ->whereYear('orders.created_at', $i);
                $total_price = $orderYear->sum('total_price') - $orderYear->sum('total_discount');
                $result[]  = [
                    'year'        => $i,
                    'total_price' => (int)$total_price
                ];

            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

    public function fastStatistics(Request $request)
    {
        $input                = $request->all();
        $yesterday            = Carbon::yesterday();
        $today                = Carbon::now();
        $limit                = array_get($input, 'limit', 10);
        $date                 = date('Y-m-d', time());
        $data['user_partner'] = User::model()->where([
            'type' => USER_TYPE_PARTNER,
        ])->whereHas('userStores', function ($q) {
            $q->where('store_id', TM::getCurrentStoreId());
        })->get()->count('id');

        $data['user_partner_enterprise'] = User::model()->where([
            'type'         => USER_TYPE_PARTNER,
            'partner_type' => USER_PARTNER_TYPE_ENTERPRISE
        ])->whereHas('userStores', function ($q) {
            $q->where('store_id', TM::getCurrentStoreId());
        })->get()->count('id');

        $pdo                   = DB::getPdo();
        $sql                   = 'select count(id) from users where `type`=' . $pdo->quote(USER_TYPE_CUSTOMER) . ' and `store_id` = ' . TM::getCurrentStoreId();
        $data['user_customer'] = DB::statement($sql);

        $data['order_success'] = Order::model()->where('store_id', TM::getCurrentStoreId())
            ->where(function ($q) {
                $q->where('status', ORDER_STATUS_COMPLETED)->orWhere('status', ORDER_STATUS_PAID);
            })->get()->count('id');
        //tong doanh thu
        $data['order_total_price']    = Order::model()->where([
            'status'   => ORDER_STATUS_COMPLETED,
            'store_id' => TM::getCurrentStoreId()
        ])->sum('total_price');
        $data['order_total_discount'] = Order::model()->where([
            'status'   => ORDER_STATUS_COMPLETED,
            'store_id' => TM::getCurrentStoreId()
        ])->sum('total_discount');
        $data['order_total']          = $data['order_total_price'] - $data['order_total_discount'];
        //total_accumulation
        $data['total_price_accumulation']    = Order::model()->where([
            'store_id' => TM::getCurrentStoreId()
        ])->where('status', '!=', 'COMPLETED')
            ->where('status', '!=', 'CANCELED')
            ->sum('total_price');
        $data['total_discount_accumulation'] = Order::model()->where([
            'store_id' => TM::getCurrentStoreId()
        ])->where('status', '!=', 'COMPLETED')
            ->where('status', '!=', 'CANCELED')
            ->sum('total_discount');
        $data['total_accumulation']          = $data['total_price_accumulation'] - $data['total_discount_accumulation'];
        $data['total_recharge']              = PaymentHistory::model()
            ->whereHas('user', function ($q) {
                $q->whereHas('userStores', function ($q) {
                    $q->where('store_id', TM::getCurrentStoreId());
                });
            })
            ->where([
                'type' => PAYMENT_TYPE_RECHARGE
            ])->sum('total_pay');

        $data['user_agent_approved'] = User::model()->where([
            'group_code'     => USER_GROUP_OUTLET,
            'account_status' => ACCOUNT_STATUS_APPROVED
        ])->whereHas('userStores', function ($q) {
            $q->where('store_id', TM::getCurrentStoreId());
        })->count('id');

        $data['user_agent_pending'] = User::model()->where([
            'group_code'     => USER_GROUP_OUTLET,
            'account_status' => ACCOUNT_STATUS_PENDING
        ])->whereHas('userStores', function ($q) {
            $q->where('store_id', TM::getCurrentStoreId());
        })->count('id');

        // Order Statistic
//        $orderStatistic = Order::model()->select([
//            DB::raw('COUNT(id) as total_order'),
//            'status',
//            DB::raw('MIN(status_text) as status_text'),
//        ])->where('store_id', TM::getCurrentStoreId())
//            ->where('status', '!=', ORDER_STATUS_IN_PROGRESS)
//            ->groupBy('status')->get()->toArray();

        // Order Statistic
        $orderStatistic = Order::model()->select([
            DB::raw('COUNT(orders.id) as total_order'),
            'orders.status',
            'os.name as status_text',
        ])->join('order_status as os', 'os.code', '=', 'orders.status')
            ->where('orders.status', '!=', ORDER_STATUS_IN_PROGRESS)
            ->where('orders.store_id', TM::getCurrentStoreId())
            ->where('os.company_id', TM::getCurrentCompanyId());
        $roleCurrentGroup = TM::getCurrentRoleGroup();
        if($roleCurrentGroup != USER_ROLE_GROUP_ADMIN){
            $orderStatistic = $orderStatistic->where('distributor_code',TM::info()['code'])->where('status_crm','!=',ORDER_STATUS_CRM_PENDING);
        }
        $orderStatistic= $orderStatistic->groupBy('orders.status','os.name')->get()->toArray();
        //Order Static LadingMethod
        $orderStatisticLadingMethod = Order::model()->select([
                DB::raw('COUNT(orders.id) as total_order'),
                'orders.lading_method',
            ])->join('order_status as os', 'os.code', '=', 'orders.status')
                ->where('orders.status', '=', ORDER_STATUS_NEW)
                ->whereNotNull('orders.lading_method')
                ->where('orders.store_id', TM::getCurrentStoreId())
                ->where('os.company_id', TM::getCurrentCompanyId());
        if($roleCurrentGroup != USER_ROLE_GROUP_ADMIN){
            $orderStatisticLadingMethod = $orderStatisticLadingMethod->where('distributor_code',TM::info()['code'])->where('status_crm','!=',ORDER_STATUS_CRM_PENDING);
        }
        $orderStatisticLadingMethod = $orderStatisticLadingMethod->groupBy('orders.lading_method')->get()->toArray();
        if(!empty($orderStatisticLadingMethod)){
        foreach($orderStatisticLadingMethod as $value){
            $lading_method[] = [
                   "total_order" => $value['total_order'],
                   "status" => $value['lading_method'],
                   "status_text"  => LADING_METHOD[$value['lading_method']]
            ]; 
        }
    }
        // Oder Statistic New
        $newOrderToday = Order::model()->select([
            DB::raw('COUNT(*) as new_order_today'),
            'status'
        ])->where('status', ORDER_STATUS_NEW)
            ->where('store_id', TM::getCurrentStoreId())
            ->whereDay('updated_at', $today->day)
            ->whereMonth('updated_at', $today->month)
            ->whereYear('updated_at', $today->year)
            ->groupBy('status')->get()->toArray();
        //total_sales_today
        $data['total_sales_today']    = Order::model()->where([
            'status'   => ORDER_STATUS_COMPLETED,
            'store_id' => TM::getCurrentStoreId()
        ])->where('updated_at', '>=', $date . ' 00:00:00')
            ->where('updated_at', '<=', $date . ' 23:59:00')
            ->sum('total_price');
        $data['total_discount_today'] = Order::model()->where([
            'status'   => ORDER_STATUS_COMPLETED,
            'store_id' => TM::getCurrentStoreId()
        ])->where('updated_at', '>=', $date . ' 00:00:00')
            ->where('updated_at', '<=', $date . ' 23:59:00')
            ->sum('total_discount');
        $data['total_today']          = $data['total_sales_today'] - $data['total_discount_today'];
        ////total_sales_yesterday
        $data['total_sales_yesterday']    = Order::model()->where([
            'status'   => ORDER_STATUS_COMPLETED,
            'store_id' => TM::getCurrentStoreId()
        ])->whereDay('updated_at', $yesterday->day)
            ->whereMonth('updated_at', $yesterday->month)
            ->whereYear('updated_at', $yesterday->year)
            ->sum('total_price');
        $data['total_discount_yesterday'] = Order::model()->where([
            'status'   => ORDER_STATUS_COMPLETED,
            'store_id' => TM::getCurrentStoreId()
        ])->whereDay('updated_at', $yesterday->day)
            ->whereMonth('updated_at', $yesterday->month)
            ->whereYear('updated_at', $yesterday->year)
            ->sum('total_discount');
        $data['total_yesterday']          = $data['total_sales_yesterday'] - $data['total_discount_yesterday'];
        //tổng số user
        $data['total_user'] = User::model()->where([
            'store_id'  => TM::getCurrentStoreId(),
            'deleted'   => 0,
            'is_active' => 1
        ])->count('*');

        $data['new_order_today'] = array_sum(array_column($newOrderToday, 'new_order_today'));
        $data ['total']          = array_sum(array_column($orderStatistic, 'total_order'));
        $data['status_total']   = array_merge($orderStatistic,$lading_method ?? []);
        return response()->json(['data' => $data]);
    }

// biểu đồ theo ngày
    public function statisticOrderDay(Request $request)
    {
        $input = $request->all();
        $to = strtotime ( '+1 day' , strtotime ($input['to']));
        $dateto = date ( 'Y-m-d' , $to );  
        $query = Order::model()
            ->select([
                DB::raw("DATE_FORMAT(orders.updated_at, '%d-%m-%Y') as date"),
                DB::raw("count(DISTINCT orders.id) as total_order")
            ])
            ->groupBy('date');
            // ->orderBy('orders.id', 'ASC');
        if (!empty($input['from']) && !empty($input['to'])) {
            $query = $query->where('updated_at', '>=', $input['from'])
                            ->where('updated_at', '<=', $dateto);
        }
        if (!empty($input['store_id'])) {
            $query = $query->where('store_id', $input['store_id']);
        }
        
        $data = $query->get();
        $data = array_pluck($data, null, 'date');
        if (!empty($input['from']) && !empty($input['to'])) {
            $day       = $this->getDatesBetween($input['from'], $input['to']);
            $dataJonTo = [];
            foreach ($day as $value) {
                $detailData = array_get($data, $value);
                if (empty($detailData)) {
                    $dataJonTo[$value]['date']        = $value;
                    $dataJonTo[$value]['total_order'] = 0;
                    continue;
                }
                $dataJonTo[] = $detailData;
            }
            $dataJonTo = array_values($dataJonTo);
            return response()->json(['data' => $dataJonTo]);
        } else {
            $data        = $query->take(7)->get();
            $datas       = array_pluck($data, null, 'date');
            $dataJon     = [];
            $getWeekDays = $this->orderModel->getWeekDays($data[0]->date, $output_format = 'd-m-Y');

            foreach ($getWeekDays as $dayValue) {
                $detailData = array_get($datas, $dayValue);
                if (empty($detailData)) {
                    $dataJon[$dayValue]['date']        = $dayValue;
                    $dataJon[$dayValue]['total_order'] = 0;
                    continue;
                }
                $dataJon[] = $detailData;
            }
            $dataJon = array_values($dataJon);
        }
        return response()->json(['data' => $dataJon]);
    }

// biểu đồ theo tháng
    public function statisticOrderMonth(Request $request)
    {
        $input = $request->all();
        if (!empty($input['from']) && !empty($input['to'])) {
            $monthFrom = date("m", strtotime($input['from']));
            $yearFrom  = date("Y", strtotime($input['from']));
            $monthTo   = date("m", strtotime($input['to']));
            $yearTo    = date("Y", strtotime($input['to']));

            if ($yearFrom == $yearTo) {
                for ($i = 1; $i <= $monthTo; $i++) {
                    $orderMonth = Order::model()
                        ->where([
                        'deleted'=>0,
                        'store_id'=> TM::getCurrentStoreId(),
                        ])
                        ->whereMonth('orders.created_at', $i)
                        ->whereYear('orders.created_at', $yearTo);
                    $result[]   = [
                        'month'       => (int)$i,
                        'total_order' => $orderMonth->count('id')
                    ];
                }
            } else {
                do {
                    $yearTmp  = $yearFrom;
                    $monthTmp = 12;
                    if ($yearTmp == $yearTo) {
                        $monthTmp = (int)$monthTo;
                        for ($i = 1; $i <= $monthTmp; $i++) {
                            $orderMonth = Order::model()
                                ->where([
                                    'deleted'=>0,
                                    'store_id'=> TM::getCurrentStoreId(),
                                ])
                                ->whereMonth('orders.created_at', $i)
                                ->whereYear('orders.created_at', $yearTo);
                            $result[]   = [
                                'month'       => (int)$i,
                                'total_order' => $orderMonth->count('id')
                            ];
                        }
                    }
                    if ($monthFrom == 12) {
                        for ($i = 12; $i <= $monthTmp; $i++) {
                            $orderMonth = Order::model()
                                ->where([
                                    'deleted'=>0,
                                    'store_id'=> TM::getCurrentStoreId(),
                                ])
                                ->whereMonth('orders.created_at', $i)
                                ->whereYear('orders.created_at', $yearTo);
                            $result[]   = [
                                'month'       => (int)$i,
                                'total_order' => $orderMonth->count('id')
                            ];
                        }
                    } else {
                        for ($i = $monthFrom; $i <= $monthTmp; $i++) {
                            $orderMonth = Order::model()
                                ->where([
                                    'deleted'=>0,
                                    'store_id'=> TM::getCurrentStoreId(),
                                ])
                                ->whereMonth('orders.created_at', $i)
                                ->whereYear('orders.created_at', $yearTo);
                            $result[]   = [
                                'month'       => (int)$i,
                                'total_order' => $orderMonth->count('id')
                            ];
                        }
                    }
                    $yearFrom += 1;
                } while ($yearFrom <= $yearTo);
            }
        } else {
            $monthFrom = 1;
            $monthTo   = 12;
            for ($i = $monthFrom; $i <= $monthTo; $i++) {
                $orderMonth = Order::model()
                    ->where([
                        'deleted'=>0,
                        'store_id'=> TM::getCurrentStoreId(),
                    ])
                    ->whereMonth('orders.created_at', $i);
                $result[]   = [
                    'month'       => $i,
                    'total_order' => $orderMonth->count('id')
                ];
            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

// biểu đồ theo năm
    public function statisticOrderYear(Request $request)
    {
        $input = $request->all();
        if (!empty($input['from']) && !empty($input['to'])) {
            $yearFrom = (int)$input['from'];
            $yearTo   = (int)$input['to'];
            for ($i = (int)$yearFrom; $i <= (int)$yearTo; $i++) {
                $orderYear = Order::model()
                    ->where([
                    'deleted'=>0,
                    'store_id'=> TM::getCurrentStoreId(),
                    ])
                    ->whereYear('orders.created_at', $i)->get();
                $result[]  = [
                    'year'        => $i,
                    'total_order' => $orderYear->count('id')
                ];
            }
        } else {
            $yearFrom = 2019;
            $yearTo   = 2026;
            for ($i = $yearFrom; $i <= $yearTo; $i++) {
                $orderYear = Order::model()
                    ->where([
                    'deleted'=>0,
                    'store_id'=> TM::getCurrentStoreId(),
                    ])
                    ->whereYear('orders.created_at', $i)->get();
                $result[]  = [
                    'year'        => $i,
                    'total_order' => $orderYear->count('id')
                ];
            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

    public function statisticProductMost(Request $request)
    {
        $input        = $request->all();
        $orderDetails = OrderDetail::model()
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->where('products.type', 'PRODUCT')
            ->select(['product_id', DB::raw('COUNT(product_id) as total_product_id')])
            ->groupBy('product_id')
            ->orderBy('total_product_id', 'DESC');
        if (!empty($input['store_id'])) {
            $orderDetails = $orderDetails->where('products.store_id', $input['store_id']);
        }
        $orderDetails = $orderDetails->limit(10)->get()->pluck('total_product_id', 'product_id')->toArray();
        $result       = [];
        foreach (array_values(array_flip($orderDetails)) as $key => $product_id) {
            $product  = Product::find($product_id);
            if (!empty($product->thumbnail)) {
                $fileCode = Arr::get($product, 'file.code', null);
                $data     = [
                    'total'        => $orderDetails[$product->id],
                    'product_id'   => $product->id,
                    'product_name' => $product->name,
                    'thumbnail'    => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode: null,
                ];
                $result[] = $data;
            }
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

    public function statisticServiceMost(Request $request)
    {
        $input        = $request->all();
        $orderDetails = OrderDetail::model()
            ->join('products', 'order_details.product_id', '=', 'products.id')
            ->where('products.type', 'SERVICE')
            ->select(['product_id', DB::raw('COUNT(product_id) as total_product_id')])
            ->groupBy('product_id')
            ->orderBy('total_product_id', 'DESC');
        if (!empty($input['store_id'])) {
            $orderDetails = $orderDetails->where('products.store_id', $input['store_id']);
        }
        $orderDetails = $orderDetails->limit(10)->get()->pluck('total_product_id', 'product_id')->toArray();
        $result       = [];
        foreach (array_values(array_flip($orderDetails)) as $key => $product_id) {
            $product  = Product::find($product_id);
            $fileCode = Arr::get($product, 'file.code', null);
            $data     = [
                'total'        => $orderDetails[$product->id],
                'service_id'   => $product->id,
                'service_name' => $product->name,
                'thumbnail'    => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
            ];
            $result[] = $data;
        }
        if (empty($result)) {
            return ['data' => []];
        }
        return response()->json(['data' => $result]);
    }

    public function getDatesBetween($inputFrom, $inputTo, $frequency = "DAILY")
    {
        $start    = new \DateTime($inputFrom);
        $interval = new \DateInterval('P1D');
        $end      = new \DateTime(date('d-m-Y', strtotime("+1 day", strtotime($inputTo))));

        $period = new \DatePeriod($start, $interval, $end);

        $dates = array_map(function ($d) {
            return $d->format("d-m-Y");
        }, iterator_to_array($period));

        switch ($frequency) {
            case "WEEKLY":
                $temp = [];
                foreach ($dates as $date) {
                    $week_num = date("W", strtotime($date));
                    $date_tmp = explode('-', $date);

                    if ($week_num >= 52 && (int)$date_tmp[1] == 1) {
                        $curWeek = ($date_tmp[0] - 1) . "-W$week_num";
                    } else {
                        if ($week_num == 1 && (int)$date_tmp[1] == 12) {
                            $curWeek = ($date_tmp[0] + 1) . "-W$week_num";
                        } else {
                            $curWeek = date('Y', strtotime($date)) . "-W$week_num";
                        }
                    }

                    if (empty($temp[$curWeek])) {
                        $temp[$curWeek] = $curWeek;
                    }
                }
                $dates = array_values($temp);
                break;
            case "MONTHLY":
                $dates      = null;
                $month_from = date('Y-m', strtotime($inputFrom));
                $month_to   = date('Y-m', strtotime($inputTo));
                $dates[]    = $month_from;
                if (strtotime($month_to . "-01") > strtotime($month_from . "-01")) {
                    $temp = strtotime('+1 month', strtotime($inputFrom));
                    while (date('Y-m', $temp) != $month_to) {
                        $dates[]   = date('Y-m', $temp);
                        $inputFrom = $temp;
                        $temp      = strtotime('+1 month', $inputFrom);
                    }
                    $dates[] = date('Y-m', $temp);
                }
                break;
            case "QUARTERLY":
                $froms        = $this->kpiUser->getQuarterMonths($inputFrom);
                $fromTemp     = explode("-", $froms[3]);
                $quarter_from = $fromTemp[1] / 3;

                $tos        = $this->kpiUser->getQuarterMonths($inputTo);
                $toTemp     = explode("-", $tos[3]);
                $quarter_to = $toTemp[1] / 3;

                $dates                                   = null;
                $dates[$fromTemp[0] . "-Q$quarter_from"] = $fromTemp[0] . "-Q$quarter_from";

                if (strtotime($tos[3] . "-01") > strtotime($froms[3] . "-01")) {
                    $temp         = strtotime('+1 month', strtotime($inputFrom));
                    $tempQuarters = $this->kpiUser->getQuarterMonths(date('Y-m-d', $temp));
                    $tempQuarters = explode("-", $tempQuarters[3]);
                    while ($tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3) != $toTemp[0] . "-Q$quarter_to") {
                        $dates[$tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3)] = $tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3);
                        $inputFrom                                               = implode("-", $tempQuarters) . "-01";
                        $temp                                                    = strtotime('+1 month', strtotime($inputFrom));
                        $tempQuarters                                            = $this->kpiUser->getQuarterMonths(date('Y-m-d', $temp));
                        $tempQuarters                                            = explode("-", $tempQuarters[3]);
                    }
                    $dates[$tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3)] = $tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3);
                }
                break;
            case "YEARLY":
                $year_from = date('Y', strtotime($inputFrom));
                $year_to   = date('Y', strtotime($inputTo));

                $dates = null;

                while ($year_to > $year_from) {
                    $dates[] = $year_from;
                    $year_from++;
                }
                $dates[] = $year_from;
                break;
        }
        return $dates;
    }

    //top keyword
    public function topkeyword(Request $request)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 10);
        $data  = SearchHistory::model()
            ->select('keyword', DB::raw('COUNT(keyword) as keyword_count'))
            ->where('store_ids', TM::getCurrentStoreId())
            ->groupBy('keyword')
            ->orderBy('keyword_count', 'desc')
            ->limit($limit)->get();
        return response()->json(['data' => $data]);
    }

    //product_top_sale
    public function topsaleproduct(Request $request)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 10);
        $data  = Order::model()
            ->join('order_details', 'order_details.order_id', 'orders.id')
            ->join('products', 'products.id', 'order_details.product_id')
            ->select('order_details.product_id', 'products.name', 'products.code', DB::raw('COUNT(order_details.product_id) as number_product'), DB::raw('(COUNT(order_details.product_id) * products.price) as price_product'))
            ->where('orders.store_id', TM::getCurrentStoreId())
            ->groupBy('order_details.product_id')
            ->orderBy('number_product', 'desc')
            ->limit($limit)
            ->get();
        return response()->json(['data' => $data]);
    }

    //product_top_sale
    public function topsearchproduct(Request $request)
    {
        $input           = $request->all();
        $limit           = array_get($input, 'limit', 10);
        $data            = [];
        $searchHistories = SearchHistory::model()->where('store_ids', TM::getCurrentStoreId());
        $searchHistories = $searchHistories->get()->toArray();
        foreach ($searchHistories as $searchHistory) {
            $data[] = $searchHistory['data'];
        }
        $arrayMerge = [];
        foreach (array_filter($data) as $key => $datum) {
            $arrayMerge = array_merge($arrayMerge, explode(",", $datum));
        }
        $countValues = array_flip(array_count_values($arrayMerge));
        krsort($countValues);
        $products = Product::model()->whereIn('id', array_values($countValues))->paginate($limit);
        return response()->json(['data' => $products]);
    }

    //user
    public function userAnalytic()
    {
        $data = User::model()
            ->where([
                'store_id' => TM::getCurrentStoreId(),
                'role_id'  => 4
            ])
            ->join('user_sessions as us', 'us.user_id', 'users.id')
            ->where('us.deleted', 0)
            ->groupBy('us.user_id')
            ->select(DB::raw('COUNT(us.user_id) as sumus'))
            ->get();
        $v    = 0;
        foreach ($data as $b) {
            $c = $b->sumus;
            $v += $c;
        }
        return response()->json(['data' => $v]);
    }
}
       
