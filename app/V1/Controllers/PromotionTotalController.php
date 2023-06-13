<?php
/**
 * User: dai.ho
 * Date: 9/07/2020
 * Time: 11:03 AM
 */

namespace App\V1\Controllers;


use App\Order;
use App\Price;
use App\PromotionProgram;
use App\PromotionTotal;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\UserGroup;
use App\V1\Models\PromotionTotalModel;
use App\V1\Traits\ReportTrait;
use App\V1\Transformers\PromotionProgram\PromotionTotalTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

/**
 * Class PromotionTotalController
 * @package App\V1\Controllers
 */
class PromotionTotalController extends BaseController {

    use ReportTrait;

    /**
     * @var PromotionTotalModel
     */
    protected $model;

    /**
     * PromotionTotalController constructor.
     */
    public function __construct() {
        $this->model = new PromotionTotalModel();
    }

    /**
     * @param Request $request
     * @param PromotionTotalTransformer $promotionTotalTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, PromotionTotalTransformer $promotionTotalTransformer) {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $items = $this->model->search($input, [], $limit);
        return $this->response->paginator($items, $promotionTotalTransformer);
    }

    /**
     * @param $id
     * @param PromotionTotalTransformer $promotionTotalTransformer
     * @return array|\Dingo\Api\Http\Response
     */
    public function view($id, PromotionTotalTransformer $promotionTotalTransformer) {
        $item = PromotionTotal::find($id);
        if (empty($promotion)) {
            return ['data' => []];
        }
        return $this->response->item($item, $promotionTotalTransformer);
    }

    public function approve($id, Request $request) {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $item = PromotionTotal::model()->where('id', $id)->first();
            if (empty($item)) {
                return $this->responseError(Message::get("V002", "ID #$id"));
            }
            $item->approval_status = 'APPROVED';
            $item->approved_at     = date("Y-m-d H:i:s");
            $item->approved_by     = TM::getCurrentUserId();
            $item->save();
            Log::update($this->model->getTable(), "#ID:" . $id, null, $item->name);
            DB::commit();

            return ['status' => Message::get("R018", $item->name)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function reject($id, Request $request) {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $item = PromotionTotal::model()->where('id', $id)->first();
            if (empty($item)) {
                return $this->responseError(Message::get("V002", "ID #$id"));
            }
            $item->approval_status = 'REJECTED';
            $item->approved_at     = date("Y-m-d H:i:s");
            $item->approved_by     = TM::getCurrentUserId();
            $item->save();
            Log::update($this->model->getTable(), "#ID:" . $id, null, $item->name);
            DB::commit();

            return ['status' => Message::get("R019", $item->name)];
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

//    public function searchReport(Request $request)
//    {
//        $input = $request->all();
//        $input['promotion_type'] = "DISCOUNT";
//        if (!empty($input['promotion_code'])) {
//            $code = explode(",", $input['promotion_code']);
//            $input['promotion_code'] = ['in' => $code];
//        }
//        $items = $this->model->search($input)->toArray();
//
//        $customerIds = array_column($items, 'order_customer_id');
//        $total_customer_price = [];
//        if ($customerIds) {
//            $total_customer_price = Order::model()->select(['customer_id', DB::raw("sum(total_price) as total_price")])
//                ->whereIn('customer_id', $customerIds)
//                ->where('status', '!=', ORDER_STATUS_NEW)
//                ->groupBy('customer_id')
//                ->get()->pluck('total_price', 'customer_id');
//        }
//
//        $result = [];
//        $pro_code_used = [];
//        foreach ($items as $item) {
//            if (empty($result[$item['order_customer_code']])) {
//                $result[$item['order_customer_code']]['code'] = $item['order_customer_code'];
//                $result[$item['order_customer_code']]['name'] = $item['order_customer_name'];
//                $result[$item['order_customer_code']]['total_order_price'] = $total_customer_price[$item['order_customer_id']] ?? 0;
//                $result[$item['order_customer_code']]['promotions'] = [];
//                $result[$item['order_customer_code']]['total_value'] = 0;
//            }
//
//            $result[$item['order_customer_code']]['promotions'][$item['promotion_code']] =
//                $result[$item['order_customer_code']]['promotions'][$item['promotion_code']] ?? [
//                    'code'  => $item['promotion_code'],
//                    'name'  => $item['value'],
//                    'value' => 0
//                ];
//            $result[$item['order_customer_code']]['promotions'][$item['promotion_code']]['value'] += $item['value'];
//            $result[$item['order_customer_code']]['total_value'] += $item['value'];
//            $pro_code_used[$item['promotion_code']] = $item['promotion_name'];
//        }
//
//        $output = [];
//        foreach ($result as $pro_code => $row) {
//            $newRow = $row;
//            $promo = [];
//            foreach ($pro_code_used as $usedCode => $usedName) {
//                $promo[] = [
//                    'code'  => $usedCode,
//                    'name'  => $usedName,
//                    'value' => $row['promotions'][$usedCode]['value'] ?? 0,
//                ];
//            }
//            $newRow['promotions'] = $promo;
//            $output[$pro_code] = $newRow;
//        }
//
//        if (!empty($input['export']) && $input['export'] == 'xlsx') {
//            $input['promotionList'] = $pro_code_used;
//            $input['dataTable'] = array_values($output);
//            $this->writeExcelBusinessResult("Report-Business_Result", storage_path('Report'), $input);
//            die;
//        }
//
//        return ['data' => array_values($output)];
//    }
    public function searchReport(Request $request) {
        $input = $request->all();
        if (empty($input['from'])) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get('from')));
        }
        if (empty($input['from'])) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get('to')));
        }
        $saleBonus               = $input['sale_bonus'] ?? 0;
        $input['promotion_type'] = "DISCOUNT";
//        if (!empty($input['promotion_code'])) {
//            $code                    = explode(",", $input['promotion_code']);
//            $input['promotion_code'] = ['in' => $code];
//        }

        $items = $this->model->search($input)->toArray();

        $customerIds          = array_column($items, 'order_customer_id');
        $total_customer_price = [];
        if ($customerIds) {
            $total_customer_price = Order::model()->select(['customer_id', DB::raw("sum(total_price) as total_price"), DB::raw("sum(original_price) as original_price")])
                    ->whereIn('customer_id', $customerIds)
                    ->where('status', '!=', ORDER_STATUS_NEW)
                    ->groupBy('customer_id')
                    ->get()->keyBy('customer_id');
        }

        $allUserGroup       = UserGroup::model()->pluck('name', 'id')->toArray();
        $allUser            = User::model()->pluck('group_id', 'id')->toArray();
        $allUserEstRevenues = User::model()->pluck('est_revenues', 'id')->toArray();
        $allPrice           = Price::model()->pluck('value', 'id')->toArray();
        $result             = [];
        $pro_code_used      = [];
        foreach ($items as $item) {
            if (empty($result[$item['order_customer_code']])) {
                $est_revenues                                                      = $allUserEstRevenues[$item['order_customer_id']] ?? 0;
                $total_order_price                                                 = $total_customer_price[$item['order_customer_id']]->total_price ?? 0;
                $price_value                                                       = !empty($input['price_id']) ? $allPrice[$input['price_id']] ?? 0 : 0;
                $kpi                                                               = $est_revenues != 0 ? $total_order_price * 100 / $est_revenues : 0;
                $original_price                                                    = $total_customer_price[$item['order_customer_id']]->original_price ?? 0;
                $discount_directly_on_bill                                         = $item['value'];
                $result[$item['order_customer_code']]['type']                      = !empty($allUser[$item['order_customer_id']]) ? $allUserGroup[$allUser[$item['order_customer_id']]] ?? null : null;
                $result[$item['order_customer_code']]['code']                      = $item['order_customer_code'];
                $result[$item['order_customer_code']]['name']                      = $item['order_customer_name'];
                $result[$item['order_customer_code']]['est_revenues']              = $est_revenues;
                $result[$item['order_customer_code']]['total_order_price']         = $original_price;
                $result[$item['order_customer_code']]['kpi']                       = round($kpi, 2);
                $result[$item['order_customer_code']]['discount_directly_on_bill'] = round($discount_directly_on_bill, 2);
                $result[$item['order_customer_code']]['revenue_difference']        = 0;
                $result[$item['order_customer_code']]['sale_bonus']                = $total_order_price * $saleBonus;
                $result[$item['order_customer_code']]['promotions']                = [];
                $result[$item['order_customer_code']]['total_value']               = 0;
            }

            $result[$item['order_customer_code']]['promotions'][$item['promotion_code']]          =
                    $result[$item['order_customer_code']]['promotions'][$item['promotion_code']] ?? [
                            'code'  => $item['promotion_code'],
                            'name'  => $item['value'],
                            'value' => 0
                    ];
            $result[$item['order_customer_code']]['promotions'][$item['promotion_code']]['value'] += $item['value'];
            $result[$item['order_customer_code']]['total_value']                                  += $total_order_price * $saleBonus;
            $pro_code_used[$item['promotion_code']]                                               = $item['promotion_name'];
        }

        $output = [];
        foreach ($result as $pro_code => $row) {
            $newRow = $row;
            $promo  = [];
            foreach ($pro_code_used as $usedCode => $usedName) {
                $promo[] = [
                        'code'  => $usedCode,
                        'name'  => $usedName,
                        'value' => $row['promotions'][$usedCode]['value'] ?? 0,
                ];
            }
            $newRow['promotions'] = $promo;
            $output[$pro_code]    = $newRow;
        }

        if (!empty($input['export']) && $input['export'] == 'xlsx') {
            $input['promotionList'] = $pro_code_used;
            $input['dataTable']     = array_values($output);
            $this->writeExcelBusinessResult("Report-Business_Result", storage_path('Report'), $input);
            die;
        }

        return ['data' => array_values($output)];
    }
}