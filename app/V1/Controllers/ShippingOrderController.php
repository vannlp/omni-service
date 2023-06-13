<?php

/**
 * User: dai.ho
 * Date: 29/06/2020
 * Time: 1:23 PM
 */

namespace App\V1\Controllers;

use App\Cart;
use App\LogShippingOrder;
use App\Setting;
use App\Company;
use App\ConfigShipping;
use App\Order;
use App\OrderDetail;
use App\OrderStatus;
use App\PromotionTotal;
use App\ShippingHistoryStatus;
use App\ShippingOrder;
use App\ShippingOrderDetail;
use App\Specification;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Supports\TM_PDF;
use App\Supports\Viettel;
use App\TM;
use App\User;
use App\Rotation;
use App\V1\Library\AHA;
use App\V1\Library\CDP;
use App\V1\Library\GHN;
use App\V1\Library\GHTK;
use App\V1\Library\GRAB;
use App\V1\Library\NJV;
use App\V1\Library\OrderSyncDMS;
use App\V1\Library\VNP;
use App\V1\Library\VNPOST;
use App\V1\Library\VTP;
use App\V1\Models\InventoryModel;
use App\V1\Models\ShippingOrderModel;
use App\V1\Traits\ControllerTrait;
use App\V1\Transformers\ShippingOrder\ShippingOrderDetailTransformer;
use App\V1\Transformers\ShippingOrder\ShippingOrderTransformer;
use App\V1\Validators\ShippingOrder\ShippingFeeValidator;
use App\V1\Validators\ShippingOrder\ShippingOrderCreateValidator;
use App\V1\Validators\ShippingOrder\ShippingOrderUpdateValidator;
use Google\Service\ShoppingContent\Weight;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;
use PHPUnit\Exception;
use App\V1\Library\Accesstrade;
use App\V1\Models\OrderModel;

class ShippingOrderController extends BaseController
{
    use ControllerTrait;

    protected $type;
    protected $model;
    protected $shippingType;
    protected $shippingTypeService;
    protected $_api_token;

    /**
     * ShippingOrderController constructor.
     * @param Request $request
     * @param ShippingOrderModel $model
     */
    const UNIT_FORMAT
        = [
            PROMOTION_TYPE_AUTO       => 'đ',
            PROMOTION_TYPE_COMMISSION => 'đ',
            PROMOTION_TYPE_DISCOUNT   => 'đ',
            PROMOTION_TYPE_POINT      => 'điểm',
            PROMOTION_TYPE_FLASH_SALE => 'đ',
            PROMOTION_TYPE_CODE       => 'đ',
        ];

    public function __construct(Request $request, ShippingOrderModel $model)
    {
        $this->model               = $model;
        $this->shippingType        = [
//            [
//                'code'        => SHIPPING_PARTNER_TYPE_GHTK,
//                'logo'        => env('GHTK_LOGO', ''),
//                'name'        => 'Giao hàng tiết kiệm',
//                'description' => '',
//            ],
//            [
//                'code'        => SHIPPING_PARTNER_TYPE_GHN,
//                'logo'        => env('GHN_LOGO', ''),
//                'name'        => 'Giao hàng nhanh',
//                'description' => '',
//            ],
SHIPPING_PARTNER_TYPE_DEFAULT => [
    'code'        => SHIPPING_PARTNER_TYPE_DEFAULT,
    'logo'        => env('GHN_LOGO', ''),
    'name'        => 'Nuti Express',
    'description' => '',
],
//            [
//                'code'        => SHIPPING_PARTNER_TYPE_AHA,
//                'logo'        => env('AHA_LOGO', ''),
//                'name'        => 'Ahamove',
//                'description' => '',
//            ],
//            [
//                'code'        => SHIPPING_PARTNER_TYPE_VNP,
//                'logo'        => env('VNP_LOGO', ''),
//                'name'        => 'VN Post',
//                'description' => '',
//            ],
//            [
//                'code'        => SHIPPING_PARTNER_TYPE_NJV,
//                'logo'        => env('NJV_LOGO', ''),
//                'name'        => 'Ninja Van',
//                'description' => '',
//            ],
[
    'code'        => SHIPPING_PARTNER_TYPE_VTP,
    'logo'        => env('VTP_LOGO', ''),
    'name'        => 'Viettel Post',
    'description' => '',
],
[
    'code'        => SHIPPING_PARTNER_TYPE_GRAB,
    'logo'        => env('GRAB_LOGO', ''),
    'name'        => 'Grab express',
    'description' => '',
],
//            [
//                'code'        => SHIPPING_PARTNER_TYPE_VNP,
//                'logo'        => env('GRAB_LOGO', ''),
//                'name'        => 'VNP Post',
//                'description' => '',
//            ],
        ];
        $this->shippingTypeService = [
//            SHIPPING_PARTNER_TYPE_GHTK=>[
//                'code'        => SHIPPING_PARTNER_TYPE_GHTK,
//                'logo'        => env('GHTK_LOGO',''),
//                'name'        => 'Giao hàng tiết kiệm',
//                'description' => '',
//            ],
//            SHIPPING_PARTNER_TYPE_GHN => [
//                'code'        => SHIPPING_PARTNER_TYPE_GHN,
//                'logo'        => env('GHN_LOGO', ''),
//                'name'        => 'Giao hàng nhanh',
//                'description' => '',
//            ],
SHIPPING_PARTNER_TYPE_DEFAULT => [
    'code'        => SHIPPING_PARTNER_TYPE_DEFAULT,
    'logo'        => env('GHN_LOGO', ''),
    'name'        => 'Nutifood giao hàng',
    'description' => '',
],
//            SHIPPING_PARTNER_TYPE_AHA=>[
//                'code'        => SHIPPING_PARTNER_TYPE_AHA,
//                'logo'        => env('AHA_LOGO',''),
//                'name'        => 'Ahamove',
//                'description' => '',
//            ],
//            SHIPPING_PARTNER_TYPE_VNP=>[
//                'code'        => SHIPPING_PARTNER_TYPE_VNP,
//                'logo'        => env('VNP_LOGO',''),
//                'name'        => 'VN Post',
//                'description' => '',
//            ],
//            SHIPPING_PARTNER_TYPE_NJV => [
//                'code'        => SHIPPING_PARTNER_TYPE_NJV,
//                'logo'        => env('NJV_LOGO', ''),
//                'name'        => 'Ninja Van',
//                'description' => '',
//            ],
//            SHIPPING_PARTNER_TYPE_VNP => [
//                'code'        => SHIPPING_PARTNER_TYPE_VNP,
//                'logo'        => env('VNP_LOGO', ''),
//                'name'        => 'VN Post',
//                'description' => '',
//            ],
SHIPPING_PARTNER_TYPE_VTP     => [
    'code'        => SHIPPING_PARTNER_TYPE_VTP,
    'logo'        => env('VTP_LOGO', ''),
    'name'        => 'Viettel Post',
    'description' => '',
],
SHIPPING_PARTNER_TYPE_GRAB    => [
    'code'        => SHIPPING_PARTNER_TYPE_GRAB,
    'logo'        => env('GRAB_LOGO', ''),
    'name'        => 'Grab Express',
    'description' => '',
],
        ];
        $this->method              = [
            array(
                'economy' => [

                ],
                'id'      => 'economy' . mt_rand(),
                'code'    => 'economy',
                'name'    => 'Chuyển phát tiết kiệm'
            ),
            array(
                'standard' => [

                ],

                'id'   => 'standard' . mt_rand(),
                'code' => 'standard',
                'name' => 'Chuyển phát nhanh'
            ),
            array(
                'express' => [

                ],
                'id'      => 'express' . mt_rand(),
                'code'    => 'express',
                'name'    => 'Chuyển phát hoả tốc'
            ),
            array(
                'save' => [

                ],
                'id'   => 'save' . mt_rand(),
                'code' => 'save',
                'name' => 'Chuyển phát tiêu chuẩn'
            )
        ];
        $type                      = $request->input('type');
        if ($type == SHIPPING_PARTNER_TYPE_AHA) {
            // Get Token
            $this->_api_token = AHA::getApiToken($request);
        }
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function getListShippingType()
    {
        return response()->json(['data' => array_values($this->shippingType)]);
    }

    public function search(Request $request, ShippingOrderTransformer $shippingOrderTransformer)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $shippingOrder       = $this->model->search($input, ['details'], $limit);

        return $this->response->paginator($shippingOrder, $shippingOrderTransformer);
    }

    public function viewDetail($shippingCode, ShippingOrderDetailTransformer $shippingOrderDetailTransformer)
    {
        try {
            $shippingOrder = ShippingOrder::model()->with([
                'details',
                'order.customer',
            ])->where([
                'code'       => $shippingCode,
                'company_id' => TM::getCurrentCompanyId(),
            ])->first();
            if (empty($shippingOrder)) {
                return ['data' => []];
            }

            return $this->response->item($shippingOrder, $shippingOrderDetailTransformer);
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function getShipFee(Request $request, ShippingFeeValidator $feeValidator)
    {
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $input = $request->all();
        $feeValidator->validate($input);
        $fee = $this->shippingType;
        foreach ($this->shippingType as $type => $partner) {
            switch ($type) {
                case SHIPPING_PARTNER_TYPE_GHTK:
                    $partnerData                          = GHTK::getShipFee($request);
                    $fee[$type]['fee']                    = $partnerData['data'] ?? null;
                    $fee[$type]['estimated_deliver_time'] = null;
                    break;
                case SHIPPING_PARTNER_TYPE_GHN:
                    $partnerData                          = null;
                    $fee[$type]['fee']                    = $partnerData['data'] ?? null;
                    $fee[$type]['estimated_deliver_time'] = null;
                    break;
                case SHIPPING_PARTNER_TYPE_AHA:
                    $fee[$type]['fee']                    = null;
                    $fee[$type]['estimated_deliver_time'] = null;
                    break;

                case SHIPPING_PARTNER_TYPE_VNP:
                    $fee[$type]['fee']                    = null;
                    $fee[$type]['estimated_deliver_time'] = null;
                    break;
            }
        }

        return response()->json(array_values($fee));
    }

    public function getAllShipFee(Request $request)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
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

        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = $company_id;
        $cart                = Cart::model()->where('id', $input['cart_id'])->first();
        if (empty($cart)) {
            return $this->responseError(Message::get("V003", Message::get('carts')));
        }
        if (empty($cart->distributor_code)) {
            return ['data' => [
                'notification_distributor' => empty($cart->distributor_id) ? $cart->notification_distributor . " Cảm ơn bạn đồng hành cùng Nutifood san sẻ suất mua với cộng đồng." : null,
            ]];
        }

        if (empty($input['type'])) {
            $fee = $this->shippingTypeService;
            $dt  = Carbon::now('Asia/Ho_Chi_Minh')->format('d-m-Y H:i:s');
            foreach ($this->shippingTypeService as $check => $partner) {
                switch ($check) {
                    //                    case SHIPPING_PARTNER_TYPE_GHTK:
                    ////                        $partnerData                           = GHTK::getShipFee($request);
                    //                        $fee[$check]['fee']                    = null;
                    //                        $fee[$check]['estimated_deliver_time'] = null;
                    //                        break;
//                    case SHIPPING_PARTNER_TYPE_GHN:
//                        $service = GHN::getService($request, $store_id);
//                        $dv1     = array_search("Tiết kiệm", array_column($service, 'short_name')) ?? null;
//                        $dv2     = array_search("Đi bộ", array_column($service, 'short_name')) ?? null;
//                        $dv3     = array_search("Bay", array_column($service, 'short_name')) ?? null;
//                        if (!empty($dv1)) {
//                            $request['ORDER_SERVICE']              = $service[$dv1]['service_type_id'];
//                            $partnerData                           = GHN::getShipFeeGHN($request, $store_id);
//                            $fee[$check]['fee_format']             = number_format($partnerData) . " đ";
//                            $fee[$check]['fee']                    = $partnerData ?? null;
//                            $getTimeShip                           = GHN::getTimeShip($request, $store_id);
//                            $date_form                             = date('d-m-Y', $getTimeShip['data']['leadtime']);
//                            $date                                  = (strtotime($date_form) - strtotime($dt)) / (60 * 60 * 24);
//                            $fee[$check]['number_date_ship']       = $date;
//                            $fee[$check]['estimated_deliver_time'] = $date_form;
//                            $fee[$check]['service']                = $dv1;
//                            $fee[$check]['diff']                   = $check . "-economy";
//                            $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
//                            if (empty($fee[$check]['fee'])) {
//                                $fee[$check]['is_active'] = 0;
//                                break;
//                            }
//                            $fee[$check]['is_active'] = 1;
//                            array_push($this->method[0]['economy'], $fee[$check]);
//                        }
//                        if (!empty($dv2)) {
//                            $request['ORDER_SERVICE']              = $service[$dv2]['service_type_id'];
//                            $partnerData                           = GHN::getShipFeeGHN($request, $store_id);
//                            $fee[$check]['fee_format']             = number_format($partnerData) . " đ";
//                            $fee[$check]['fee']                    = $partnerData ?? null;
//                            $getTimeShip                           = GHN::getTimeShip($request, $store_id);
//                            $date_form                             = date('d-m-Y', $getTimeShip['data']['leadtime']);
//                            $date                                  = (strtotime($date_form) - strtotime($dt)) / (60 * 60 * 24);
//                            $fee[$check]['number_date_ship']       = $date;
//                            $fee[$check]['estimated_deliver_time'] = $date_form;
//                            $fee[$check]['service']                = $dv2;
//                            $fee[$check]['diff']                   = $check . "-standard";
//                            $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
//                            if (empty($fee[$check]['fee'])) {
//                                $fee[$check]['is_active'] = 0;
//                                break;
//                            }
//                            $fee[$check]['is_active'] = 1;
//                            array_push($this->method[1]['standard'], $fee[$check]);
//                        }
//                        if (!empty($dv3)) {
//                            $request['ORDER_SERVICE']              = $service[$dv3]['service_type_id'];
//                            $partnerData                           = GHN::getShipFeeGHN($request, $store_id);
//                            $fee[$check]['fee_format']             = number_format($partnerData) . " đ";
//                            $fee[$check]['fee']                    = $partnerData ?? null;
//                            $getTimeShip                           = GHN::getTimeShip($request, $store_id);
//                            $date_form                             = date('d-m-Y', $getTimeShip['data']['leadtime']);
//                            $date                                  = (strtotime($date_form) - strtotime($dt)) / (60 * 60 * 24);
//                            $fee[$check]['number_date_ship']       = $date;
//                            $fee[$check]['estimated_deliver_time'] = $date_form;
//                            $fee[$check]['service']                = $dv3;
//                            $fee[$check]['diff']                   = $check . "-express";
//                            if (empty($fee[$check]['fee'])) {
//                                $fee[$check]['is_active'] = 0;
//                                break;
//                            }
//                            $fee[$check]['is_active'] = 1;
//                            array_push($this->method[2]['express'], $fee[$check]);
//                        }
//                        break;
                    //                    case SHIPPING_PARTNER_TYPE_AHA:
                    //                        $fee[$check]['fee']                    = null;
                    //                        $fee[$check]['estimated_deliver_time'] = null;
                    //                        break;

                    //                    case SHIPPING_PARTNER_TYPE_VNP:
                    //                        $fee[$check]['fee']                    = null;
                    //                        $fee[$check]['estimated_deliver_time'] = null;
                    //                        break;
//                    case SHIPPING_PARTNER_TYPE_NJV:
//                        $partnerData        = NJV::getShipFee($request, $store_id);
//                        $fee[$check]['fee'] = $partnerData['fee'];
//                        if (empty($partnerData)) {
//                            $fee[$check]['is_active'] = 0;
//                            break;
//                        }
//                        $fee[$check]['is_active']  = 1;
//                        $fee[$check]['fee_format'] = number_format($fee[$check]['fee']) . " đ";
//                        // $fee[$check]['number_date_ship']       = $partnerData['time'];
//                        $fee[$check]['estimated_deliver_time'] = $partnerData['time'];
//                        $fee[$check]['diff']                   = $check . "-economy";
//                        $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
//                        array_push($this->method[0]['economy'], $fee[$check]);
//                        break;
                    case SHIPPING_PARTNER_TYPE_VTP:
//                        if($cart->getUserDistributor->is_vtp !=1){
//                            break;
//                        }
                        $token = VTP::getApiToken();
                        if ($token == [] || (isset($token['status']) && isset($token['success']) && $token['status'] == 'error' && $token['success'] == 'false')) {
                            break;
                        }
//                        $dataService  = VTP::getSerVice($request, $token, $store_id, null);
//                        if($dataService != []){
//                            $key1 = 0;
//                            $key2 = null;
//                            if(!empty($dataService) && count($dataService) > 1){
//                                $chek_service = array_column($dataService, 'fee_service');
//                                $key1         = array_search(min($chek_service), $chek_service);
//                                $key2         = array_search(max($chek_service), $chek_service);
//                            }
//                            $dv1 = $dataService[$key1]['service_type_id'] ?? null;
//                            $dv2 = $dataService[$key2]['service_type_id'] ?? null;

                        $dataFeeShip = VTP::getShipFee($request, $token, $store_id);
                        if (count($dataFeeShip) == 1) {
                            $fee[$check]['is_active']  = 1;
                            $fee[$check]['fee_format'] = number_format($dataFeeShip[0]['MONEY_TOTAL']) . " đ";
                            $fee[$check]['fee']        = $dataFeeShip[0]['MONEY_TOTAL'];
                            // $fee[$check]['number_date_ship']       = $dataService[$key1]['time'];
//                            $str                                   = substr($dataService[$key1]['time'], 0, strlen($dataService[$key1]['time']) - 6);
                            $fee[$check]['estimated_deliver_time'] = $dataFeeShip[0]['time'];
                            $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                            $fee[$check]['service']                = $dataFeeShip[0]['service_type_id'];
                            $fee[$check]['diff']                   = $check . "-economy";
                            array_push($this->method[0]['economy'], $fee[$check]);
                        }
                        if (count($dataFeeShip) > 1) {
                            $ncod = array_search('NCOD', array_column($dataFeeShip, 'service_type_id'));
                            $lcod = array_search('LCOD', array_column($dataFeeShip, 'service_type_id'));
                            if (isset($lcod) && is_integer($lcod) == true) {
                                $fee[$check]['is_active']  = 1;
                                $fee[$check]['fee_format'] = number_format($dataFeeShip[$lcod]['MONEY_TOTAL']) . " đ";
                                $fee[$check]['fee']        = $dataFeeShip[$lcod]['MONEY_TOTAL'];
                                // $fee[$check]['number_date_ship']       = $dataService[$key1]['time'];
//                                $str                                   = substr($dataService[$key1]['time'], 0, strlen($dataService[$key1]['time']) - 6);
                                $fee[$check]['estimated_deliver_time'] = $dataFeeShip[$lcod]['time'];
                                $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                                $fee[$check]['service']                = $dataFeeShip[$lcod]['service_type_id'];
                                $fee[$check]['diff']                   = $check . "-economy";
                                array_push($this->method[0]['economy'], $fee[$check]);
                            }
                            if (isset($ncod) && is_integer($ncod) == true) {

                                $fee[$check]['is_active']  = 1;
                                $fee[$check]['fee_format'] = number_format($dataFeeShip[$ncod]['MONEY_TOTAL']) . " đ";
                                $fee[$check]['fee']        = $dataFeeShip[$ncod]['MONEY_TOTAL'];
//                                $str                                   = substr($dataService[$key2]['time'], 0, strlen($dataService[$key2]['time']) - 6);
                                $fee[$check]['estimated_deliver_time'] = $dataFeeShip[$ncod]['time'];
                                $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                                $fee[$check]['service']                = $dataFeeShip[$ncod]['service_type_id'];
                                $fee[$check]['diff']                   = $check . "-standard";
                                array_push($this->method[1]['standard'], $fee[$check]);
                            }
                        }
//                        }else{
//                            break;
//                        }
                        break;
                    // if($dataFeeShip == []){
                    // break;
                    // }
                    case SHIPPING_PARTNER_TYPE_GRAB:
//                        if($cart->getUserDistributor->is_grab !=1){
//                            break;
//                        }
                        $service_type = "INSTANT";
                        $dataFeeShip  = GRAB::getShipFee($request, $store_id, $service_type);

                        if (!empty($dataFeeShip['errors'])) {
                            $fee[$check]['is_active'] = 0;
                            $fee[$check]['errors']    = $dataFeeShip['errors'];
                            array_push($this->method[1]['standard'], $fee[$check]);
                            break;
                        }
                        if (empty($dataFeeShip)) {
                            break;
                        }
                        $fee[$check]['is_active']  = 1;
                        $fee[$check]['fee_format'] = number_format($dataFeeShip['price']) . " đ";
                        $fee[$check]['fee']        = $dataFeeShip['price'];
//                        $fee[$check]['number_date_ship']       = $dataFeeShip['time'];
                        $date                            = (strtotime($dataFeeShip['time']) - strtotime($dt)) / (60 * 60);
                        $fee[$check]['number_date_ship'] = date("Y-m-d H:i:s", strtotime($dataFeeShip['time']));

//                        $fee[$check]['estimated_deliver_time']  = ceil($date). " giờ";
                        $fee[$check]['estimated_deliver_time'] = "48 giờ";
                        $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                        $fee[$check]['service']                = $service_type;
                        $fee[$check]['diff']                   = $check . "-standard";
                        array_push($this->method[1]['standard'], $fee[$check]);
                        //standard
//                        $service_type = "SAME_DAY";
//                        $dataFeeShip = GRAB::getShipFee($request,$store_id,GRAB::getToken(),$service_type);
//                        if(empty($dataFeeShip)){
//                            $fee[$check]['is_active'] = 0;
//                            break;
//                        }
//                        $fee[$check]['is_active']              = 1;
//                        $fee[$check]['fee_format']             = number_format($dataFeeShip['price']) . " đ";
//                        $fee[$check]['fee']                    = $dataFeeShip['price'];
//                        $fee[$check]['number_date_ship']       = date("Y-m-d H:i:s",strtotime($dataFeeShip['time']));
//                        $date      = (strtotime($dataFeeShip['time']) - strtotime($dt)) / (60*60);
//                        $fee[$check]['estimated_deliver_time'] = ceil($date) +1 . " giờ";
//                        $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
//                        $fee[$check]['service']                = $service_type;
//                        $fee[$check]['diff']                   = $check . "-standard";
//                        array_push($this->method[1]['standard'], $fee[$check]);
                        break;
                    case SHIPPING_PARTNER_TYPE_VNP:
//                        if($cart->getUserDistributor->is_vnp !=1){
//                            break;
//                        }
                        $token_vnp = VNP::getToken();
                        if (empty($token_vnp) || !empty($token_vnp['status']) && $token_vnp['status'] == "error") {
                            break;
                        }
                        $feeService = VNP::getShipFeeAllService($request, $store_id);
                        if (!empty($feeService)) {
                            $ems   = array_search('TMDT_EMS', array_column($feeService, 'MaDichVu'));
                            $ecode = array_search('TMDT_BK', array_column($feeService, 'MaDichVu'));
                            if (isset($ecode) && is_integer($ecode) == true) {
                                $fee[$check]['is_active']              = 1;
                                $fee[$check]['fee_format']             = number_format($feeService[$ecode]['GiaCuoc']) . " đ";
                                $fee[$check]['fee']                    = $feeService[$ecode]['GiaCuoc'];
                                $fee[$check]['estimated_deliver_time'] = $feeService[$ecode]['time'];
                                $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                                $fee[$check]['service']                = $feeService[$ecode]['MaDichVu'];
                                $fee[$check]['diff']                   = $check . "-economy";
                                array_push($this->method[0]['economy'], $fee[$check]);
                            }
                            if (isset($ems) && is_integer($ems) == true) {
                                $fee[$check]['is_active']              = 1;
                                $fee[$check]['fee_format']             = number_format($feeService[$ems]['GiaCuoc']) . " đ";
                                $fee[$check]['fee']                    = $feeService[$ems]['GiaCuoc'];
                                $fee[$check]['estimated_deliver_time'] = $feeService[$ems]['time'];
                                $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                                $fee[$check]['service']                = $feeService[$ems]['MaDichVu'];
                                $fee[$check]['diff']                   = $check . "-standard";
                                array_push($this->method[1]['standard'], $fee[$check]);
                            }
                        }
                        break;

                    case SHIPPING_PARTNER_TYPE_DEFAULT:
//                        if($cart->getUserDistributor->is_self_delivery !=1){
//                            break;
//                        }
                        if (empty($cart->distributor_code) || !empty($cart->getUserDistributor->type_delivery_hub)) {
//                        if(empty($cart->distributor_code)){
                            break;
                        }
                        $fee[$check]['is_active']              = 1;
                        $fee[$check]['fee_format']             = "";
                        $fee[$check]['fee']                    = "";
                        $fee[$check]['estimated_deliver_time'] = "5 - 7 ngày";
                        $fee[$check]['note']                   = ["Tất cả các ngày trong tuần", "Chỉ giao giờ hành chính"];
                        $fee[$check]['service']                = SHIPPING_PARTNER_TYPE_DEFAULT;
                        $fee[$check]['diff']                   = $check . "-save";
                        array_push($this->method[3]['save'], $fee[$check]);
                        break;
                }
            }
            if (empty($this->method[0]['economy'])) {
                unset($this->method[0]);
            }
            if (empty($this->method[1]['standard'])) {
                unset($this->method[1]);
            }
            if (empty($this->method[2]['express'])) {
                unset($this->method[2]);
            }
            if (empty($this->method[3]['save'])) {
                unset($this->method[3]);
            }
        }

        if (!empty($input['type'])) {
            $type = $input['type'];
            $fee  = [];
            switch ($type) {
//                case SHIPPING_PARTNER_TYPE_GHTK:
//                    $fee['data'] = GHTK::getShipFee($request);
//                    $fee['fee']  = $fee['data'] ?? null;
//                    break;
//                case SHIPPING_PARTNER_TYPE_AHA:
//                    $data = [];
//                    break;

//                case SHIPPING_PARTNER_TYPE_VNP:
//                    $data = [];
//                    break;

                case SHIPPING_PARTNER_TYPE_VTP:
                    $fee_ship                 = VTP::getShipFee($request, VTP::getApiToken(), $store_id);
                    $fee[$type]['fee']        = $fee_ship['MONEY_TOTAL'] ?? null;
                    $fee[$type]['fee_format'] = number_format($fee_ship['MONEY_TOTAL']) . " đ";
                    break;
//                case SHIPPING_PARTNER_TYPE_GHN:
//                    $fee[$type]['fee']        = GHN::getShipFeeGHN($request, $store_id) ?? null;
//                    $fee[$type]['fee_format'] = number_format($fee[$type]['fee']) . " đ";
//                    break;
//                case SHIPPING_PARTNER_TYPE_NJV:
//                    $fee[$type]['fee']        = NJV::getShipFee($request, $store_id);
//                    $fee[$type]['fee_format'] = number_format($fee[$type]['fee']) . " đ";
//                    break;
                case SHIPPING_PARTNER_TYPE_GRAB:
                    $fee_ship                 = GRAB::getShipFee($request, $store_id);
                    $fee[$type]['fee']        = $fee_ship;
                    $fee[$type]['fee_format'] = number_format($fee_ship) . " đ";
                    break;
//                case SHIPPING_PARTNER_TYPE_VNP:
//                    $feeService = VNP::getShipFeeAllService($request,VNP::getToken(),$store_id);
//                    $fee[$type]['fee']        =$feeService['GiaCuoc'];
//                    $fee[$type]['fee_format'] = number_format($feeService['GiaCuoc']) . " đ";
//                    // $fee[$type]['fee_code']    =$feeService['CuocCOD'];
//                    dd($feeService);

            }
            return response()->json($fee);
        }
        return ['data' => array_values($this->method)];
    }

    public function getAllService(Request $request)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
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

        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['company_id'] = $company_id;
        if (empty($input['type'])) {
            throw new \Exception(Message::get("V001", 'type'));
        }
        $type    = $input['type'];
        $service = [];
        switch ($type) {
            case SHIPPING_PARTNER_TYPE_GHTK:
                $service[$type]['service'] = [];
                break;
            case SHIPPING_PARTNER_TYPE_AHA:
                $data = null;
                break;

            case SHIPPING_PARTNER_TYPE_VNP:
                $service[$type]['service'] = [];
                break;
            case SHIPPING_PARTNER_TYPE_VTP:
                $data                      = VTP::getShipFee($request, VTP::getApiToken(), $store_id);
                $service[$type]['service'] = $data;
                break;
//            case SHIPPING_PARTNER_TYPE_GHN:
//                $service['data'] = GHN::getService($request, $store_id);
//                break;
        }
        return response()->json(array_values($service));
    }

    public function postOrder($orderId, Request $request, ShippingOrderCreateValidator $shippingOrderCreateValidator)
    {
        $strOrder    = $orderId;
        $store_id = TM::getCurrentStoreId();
        $orderId     = explode(',', $orderId);
        $orderStatus = Order::whereIn('code', $orderId)->pluck('status', 'code')->toArray();
        foreach ($orderStatus as $key => $status) {
            if ($status !== ORDER_STATUS_APPROVED) {
                return $this->responseError(Message::get('V002', Message::get('status') . " [$key]"));
            }
        }
        $date     = date('Y-m-d H:i:s', time());
        $rotation = Rotation::model()
            ->with('condition')
            ->where('is_active', 1)->where('start_date', '<=', $date)
            ->where('end_date', '>=', $date)
            ->first();
        // $input = $request->all();
        // $shippingOrderCreateValidator->validate($input);
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
                        };
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
                // $type   = !empty($order->shipping_method_code) ? $order->shipping_method_code : "DEFAULT";
                $partner_code = ConfigShipping::model()->where('code',$order->shipping_method_code) ->first();
               

                $type   = !empty( $partner_code->shipping_partner_code) ? $partner_code->shipping_partner_code : "DEFAULT";

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
                            $result = $this->model->createShippingOrder($order, $request);
                            break;
                    }
                }
                if (!empty($result['status']) && $result['status'] == 'success' && $result['success'] == true) {
                    //Create Inventory && Update Quantity Warehouse
//                    print_r(json_encode($result['warehouse']));die;
//                    $a = $this->model->createInventory($order, $result['warehouse'], $type);
                }
//                try {
//                    $syncDMS = OrderSyncDMS::updateStatusDMS(array($order->code),"C",$order->status);
//                    $is_active = 1;
//                    Order::where('code',$order->code)->update(['log_status_order_dms'=>json_encode($syncDMS)]);
//                    \App\Supports\Log::logSyncDMS($order->order_code,null,$syncDMS ?? [],"CREATE-ORDER",$is_active);
//                }catch (\Exception $exception){
//                }
            }
            $result = !empty($result) ? $result : ["message" => "Tạo vận đơn thành công"];
            // if ($input['is_sync'] == 1 && TM::getCurrentStoreId() == 46) {
            //     // Send Order to Viettel
            //     $orderType = $order->order_type;
            //     switch ($orderType) {
            //         case 'VANGLAI':
            //             $customer = User::model()->where(['type' => 'CUSTOMER', 'channel_type' => '048'])->first();
            //             if (empty($customer)) {
            //                 $this->responseError(Message::get('V003', 'Khách hàng vãng lai'));
            //             }
            //             $shortCode = $customer->code;
            //             break;
            //         case 'GUEST':
            //             $customer = User::model()->where(['type' => 'CUSTOMER', 'channel_type' => '048'])->first();
            //             if (empty($customer)) {
            //                 $this->responseError(Message::get('V003', 'Khách hàng vãng lai'));
            //             }
            //             $shortCode = $customer->code;
            //             break;
            //         case 'AGENCY':
            //             $customer = User::model()->where(['type' => 'CUSTOMER', 'channel_type' => '064'])->first();
            //             if (empty($customer)) {
            //                 $this->responseError(Message::get('V003', 'Khách hàng đại lý'));
            //             }
            //             $shortCode = $customer->code;
            //             break;
            //         case 'GROCERY':
            //             $shortCode = Arr::get($order, 'customer.code', null);
            //             break;
            //         default:
            //             return $this->responseError(Message::get('V002', 'Loại đơn hàng'));
            //     }

            //     $params = [
            //         'orderNumber'       => $order->code,
            //         'createDate'        => date('d/m/Y', time()),
            //         'updateDate'        => '',
            //         'shortCode'         => $shortCode,
            //         'customerName'      => $order->customer_name,
            //         'phone'             => $order->customer_phone,
            //         'address'           => $order->shipping_address,
            //         'lat'               => $order->lat,
            //         'lng'               => $order->long,
            //         'shopCode'          => $order->distributor_code,
            //         'status'            => 1,
            //         'deliveryDate'      => date('d/m/Y'),
            //         'description'       => '',
            //         'amount'            => $order->total_price,
            //         'discountAmountSo'  => 0,
            //         'discountPercentSo' => 0,
            //         'discount'          => '',
            //         'total'             => $order->total_price,
            //         'totalDetail'       => count($order->details),
            //         'soOAMDetails'      => [],
            //     ];

            //     foreach ($order->details as $index => $detail) {
            //         $params['soOAMDetails'][] = json_decode(json_encode([
            //             'productCode'          => Arr::get($detail, 'product.code'),
            //             'productName'          => Arr::get($detail, 'product.name'),
            //             'quantity'             => $detail->qty,
            //             'isFreeItem'           => 0,
            //             'price'                => $detail->price,
            //             'amount'               => $detail->total,
            //             'discountAmount'       => $detail->price_down,
            //             'discountPercent'      => '',
            //             'promotionProgramCode' => '',
            //             'vat'                  => 0,
            //             'lineNumber'           => $index + 1,
            //         ]));
            //     }
            //     $sync = Viettel::syncOrder(json_decode(json_encode($params)));
            //     if ($sync['status'] == 'error') {
            //         return $this->responseError($sync['message']);
            //     }

            //     $order->search_sync_status = 5;
            //     $order->save();
            // }
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return response()->json($result);
    }

    public function getShippingOrderStatus($shippingCode)
    {
        try {
            DB::beginTransaction();
            $shippingOrder = ShippingOrder::model()->where('code', $shippingCode)->first();
            if (empty($shippingOrder)) {
                return $this->responseError(Message::get("V003", Message::get("code") . " #$shippingCode"));
            }

            $result = GHTK::getOrderStatus($shippingCode);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return response()->json($result);
    }

    public function cancelShippingOrder($shippingCode, Request $request)
    {
        $input = $request->all();
        try {
            DB::beginTransaction();
            $shippingOrder = ShippingOrder::model()->where('code', $shippingCode)->first();
            if (empty($shippingOrder)) {
                return $this->responseError(Message::get("V003", Message::get("code") . " #$shippingCode"));
            }
            $type = !empty($input['type']) ? $input['type'] : $shippingOrder->type;
            if ($type) {
                switch ($type) {
                    case SHIPPING_PARTNER_TYPE_GHTK:
                        $result = GHTK::cancelOrder($shippingCode);
                        break;
                    case SHIPPING_PARTNER_TYPE_GHN:
                        $result = GHN::cancelOrder($shippingCode, $shippingOrder);
                        break;
                    case SHIPPING_PARTNER_TYPE_AHA:
                        break;
                    case SHIPPING_PARTNER_TYPE_NJV:
                        $result = NJV::cancelOrder($shippingCode, $this->_api_token);
                        break;
                    case SHIPPING_PARTNER_TYPE_GRAB:
                        $result = GRAB::cancelOrder($shippingOrder->code_type_ghn, $shippingOrder->shipping_order_cool);
                        break;
                    case SHIPPING_PARTNER_TYPE_VNP:
                        $result = VNP::cancelOrder($shippingOrder->code_type_ghn, VNP::getToken());
                        break;
                    case SHIPPING_PARTNER_TYPE_VTP:
                        $result = VTP::updateOrder($shippingCode, $request, VTP::getApiToken());
                        break;
                    case SHIPPING_PARTNER_TYPE_VNP:
                        break;
                    case SHIPPING_PARTNER_TYPE_DEFAULT:
                        break;
                }
            }
            // $result = GHTK::cancelOrder($shippingCode);
            DB::commit();
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return response()->json($result);
    }

    private function getToken()
    {
    }

    public function webhooksUpdateStatus(Request $request)
    {
        $input = $request->all();
        Log::info(json_encode($input));

        $hash = Arr::get($input, 'hash');
        if (empty($hash)) {
            return $this->response->noContent();
        }

        $shippingCode = null;
        $status_id    = null;
        $status_text  = null;
        $ship_fee     = null;
        $pick_money   = null;

        switch ($hash) {
            case md5(SHIPPING_PARTNER_TYPE_GHTK);
                $shippingCode = Arr::get($input, 'label_id');
                $status_id    = Arr::get($input, 'status_id');
                $ship_fee     = Arr::get($input, 'fee');
                $pick_money   = Arr::get($input, 'pick_money');
                $status_text  = GHTK::STATUS[$status_id] ?? null;
                break;
            default;
                break;
        }

        if (empty($shippingCode)) {
            return $this->response->noContent();
        }

        $shippingOrder = ShippingOrder::where('code', $shippingCode)->first();

        if (empty($shippingOrder)) {
            return $this->response->noContent();
        }

        try {
            DB::table('shipping_orders')
                ->where('id', $shippingOrder->id)
                ->update([
                    'status'      => $status_id ?? $shippingOrder->status,
                    'status_text' => $status_text ?? $shippingOrder->status_text,
                    'ship_fee'    => $ship_fee ?? $shippingOrder->ship_fee,
                    'pick_money'  => $pick_money ?? $shippingOrder->pick_money,
                ]);

            if ($status_id == 5) {
                Order::where('id', $shippingOrder->order_id)->update([
                    'status' => OrderStatus::SHIPPER,
                ]);
            }
        }
        catch (\Exception $exception) {
        }

        return $this->response->noContent();
    }

    public function getOrderDetailGrab($shippingCode)
    {
        if (empty($shippingCode)) {
            return $this->response->noContent();
        }
        $result = GRAB::getDetailGrab($shippingCode);
        return response()->json([
            'data' => $result ?? null,
        ]);
    }

    public function getClientOrderDetailGrab($shippingCode)
    {
        if (empty($shippingCode)) {
            return $this->response->noContent();
        }
        $result = GRAB::getDetailGrab($shippingCode, 1);
        return response()->json([
            'data' => !empty($result['trackingURL']) ? $result['trackingURL'] : null
        ]);
    }

    ##################GHN##################
    public function getOrderDetailGHN($shippingCode)
    {
        if (empty($shippingCode)) {
            return $this->response->noContent();
        }
        $result = GHN::viewDetail($shippingCode);
        return $result;
    }

    public function returnOrderGHN($shippingCode)
    {
        if (empty($shippingCode)) {
            return $this->response->noContent();
        }
        $result = GHN::return($shippingCode);
        return $result;
    }

    public function webhooksVNPost(Request $request)
    {
        $input = $request->all();
        Log::info(json_encode($input));
        if (empty($input['Data'])) {
            return $input;
        }
        $data          = json_decode($input['Data']);
        $shippingOrder = ShippingOrder::where('code_type_ghn', $data->ItemCode)->first();
        if (empty($shippingOrder)) {
            return $input;
        }
        $shippingOrder->delivery_status = VNP::STATUS[$data->OrderStatusId];

        $check_status = ShippingHistoryStatus::where('shipping_id', $shippingOrder->code)
            ->where('status_code', $data->OrderStatusId)->first();
        if (empty($check_status)) {
            ShippingHistoryStatus::insert([
                'shipping_id'      => $shippingOrder->code,
                'status_code'      => $data->OrderStatusId,
                'text_status_code' => VNP::STATUS[$data->OrderStatusId] ?? null,
                'log_shipping'     => json_encode($data) ?? null,
                'created_at'       => date("Y-m-d H:i:s", time()),
            ]);
        }
        if ($data->OrderStatusId == 100) {
            $order                 = Order::find($shippingOrder->order_id);
            $order->status         = ORDER_STATUS_SHIPPED;
            $shippingOrder->status = ORDER_STATUS_SHIPPED;
            VNP::updateOrderStatusHistory($order);
            $order->save();
            if ($order->payment_method != PAYMENT_METHOD_CASH && $order->payment_status == 1) {
                $order->status = ORDER_STATUS_COMPLETED;
                $order->save();
                VNP::updateOrderStatusHistory($order);
            }
        }
        if ($data->OrderStatusId == 70) {
            $order               = Order::find($shippingOrder->order_id);
            $order->receive_date = date("Y-m-d H:i:s", time());
            $order->save();
        }
        $shippingOrder->save();
        return $input;
    }

    public function webhooksViettelPost(Request $request)
    {
        $input = $request->all();
        Log::info(json_encode($input));

        if (empty($input['DATA']['ORDER_NUMBER'])) {
            return $this->response->noContent();
        }
        $shippingOrder = ShippingOrder::where('code_type_ghn', $input['DATA']['ORDER_NUMBER'])->first();
        if (empty($shippingOrder)) {
            return $this->response->noContent();
        }
        $shippingOrder->delivery_status = VTP::STATUS[$input['DATA']['ORDER_STATUS']];
        $shippingOrder->save();

        $check_status = ShippingHistoryStatus::where('shipping_id', $shippingOrder->code)
            ->where('status_code', $input['DATA']['ORDER_STATUS'])->first();

        if (empty($check_status)) {
            ShippingHistoryStatus::insert([
                'shipping_id'      => $shippingOrder->code,
                'status_code'      => $input['DATA']['ORDER_STATUS'],
                'text_status_code' => VTP::STATUS[$input['DATA']['ORDER_STATUS']],
                'log_shipping'     => json_encode($input) ?? null,
                'created_at'       => date("Y-m-d H:i:s", time()),
            ]);
        }
        if ($input['DATA']['ORDER_STATUS'] == 501) {
            $order                 = Order::find($shippingOrder->order_id);
            $order->status         = ORDER_STATUS_SHIPPED;
            $shippingOrder->status = ORDER_STATUS_SHIPPED;
            $order->save();
            VNP::updateOrderStatusHistory($order);
            if ($order->payment_method != PAYMENT_METHOD_CASH && $order->payment_status == 1) {
                $order->status = ORDER_STATUS_COMPLETED;
                $order->save();
                try {
                    $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
                    $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
                    if (!empty($dataUpdateDMS)) {
                        $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                        if (!empty($pushOrderStatusDms['errors'])) {
                            foreach ($pushOrderStatusDms['errors'] as $item) {
                                \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                            }
                        } else {
                            if (!empty($pushOrderStatusDms)) {
                                \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                            }
                            if (empty($pushOrderStatusDms)) {
                                \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                            }
//                            \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                        }

                    }
                    Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
                }
                catch (\Exception $exception) {
                    \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
                }

                #CDP
                try {
                    CDP::pushOrderCdp($order, 'webhooksViettelPost - ShippingOrderController - line:1135');
                } catch (\Exception $e) {
                    TM_Error::handle($e);
                }

                VNP::updateOrderStatusHistory($order);
            }
        }
        if ($input['DATA']['ORDER_STATUS'] == 105) {
            // Nhận hàng
            $order               = Order::find($shippingOrder->order_id);
            $order->status_text = 'Đang giao hàng';
            $order->receive_date = date("Y-m-d H:i:s", time());
            $order->save();
        }
        if ($input['DATA']['ORDER_STATUS'] == 504) {
            $order                 = Order::find($shippingOrder->order_id);
            $order->status         = ORDER_STATUS_RETURNED;
            $shippingOrder->status = ORDER_STATUS_RETURNED;
            $order->save();
            VNP::updateOrderStatusHistory($order);
        }
        if ($input['DATA']['ORDER_STATUS'] == 170) {
            $order                 = Order::find($shippingOrder->order_id);
            $order->status         = ORDER_STATUS_RETURNED;
            $shippingOrder->status = ORDER_STATUS_RETURNED;
            $order->save();
            VNP::updateOrderStatusHistory($order);
        }
        if ($input['DATA']['ORDER_STATUS'] == 107 || $input['DATA']['ORDER_STATUS'] == 503) {
            $order                 = Order::find($shippingOrder->order_id);
            $order->status         = ORDER_STATUS_CANCELED;
            $shippingOrder->status = ORDER_STATUS_CANCELED;
            $order->status_text    = "Hủy đơn";
            $order->save();
            try {
                $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
                $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
                if (!empty($dataUpdateDMS)) {
                    $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                    if (!empty($pushOrderStatusDms['errors'])) {
                        foreach ($pushOrderStatusDms['errors'] as $item) {
                            \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                        }
                    } else {
                        if (!empty($pushOrderStatusDms)) {
                            \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                        }
                        if (empty($pushOrderStatusDms)) {
                            \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                        }
//                        \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                    }

                }
                Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
            }
            catch (\Exception $exception) {
                \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
            }
            VNP::updateOrderStatusHistory($order);
        }
        $shippingOrder->save();
        return $shippingOrder;
    }

    public function pushShippingOrderGrab($shippingCode)
    {
        $shipping = ShippingOrder::where('code', $shippingCode)->whereIn('status_shipping_method', ['FAILED', 'RETURNED'])->first();
        if (empty($shipping)) {
            return $this->responseError(Message::get("V003", Message::get("code") . " #$shippingCode"));
        }
        DB::beginTransaction();
        try {
            LogShippingOrder::insert([
                'order_code'           => $shipping->code,
                'type'                 => $shipping->type,
                'code_shipping_method' => $shipping->code_type_ghn,
                'reponse_json'         => $shipping->result_json,
            ]);
            $token    = GRAB::getToken($shipping->shipping_order_cool);
            $client   = new Client();
            $response = $client->post(env("GRAB_END_POINT") . "/deliveries", [
                'headers' => ['Content-Type' => 'application/json', 'Authorization' => "Bearer " . $token],
                'body'    => $shipping->param_push_shipping,
            ]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];
            try {
                TM::sendMessage('pushShippingOrderGrab: ',json_encode($response));
            }catch (\Exception $exception){
            }
            $order    = Order::model()->where('code', $shipping->code)->first();
            if ($response['status'] == 'ALLOCATING') {
                $order->shipping_info_code = $response['deliveryID'] ?? null;
                $order->shipping_info_json = !empty($response) ? json_encode($response) : null;
                $order->status             = OrderStatus::SHIPPING;
                $order->save();
                $shipping->code_type_ghn       = $response['deliveryID'] ?? null;
                $shipping->status              = 'SHIPPING';
                $shipping->status_text         = 'Đang giao hàng';
                $shipping->count_push_shipping = $shipping->count_push_shipping + 1;
                $shipping->company_id          = TM::getCurrentCompanyId();
                $shipping->result_json         = json_encode($response) ?? null;
                $shipping->created_at          = date("Y-m-d H:i:s");
                $shipping->created_by          = TM::getCurrentUserId();
                $shipping->save();
            }
            DB::commit();
        }
        catch (\Exception $exception) {
            DB::rollBack();
            TM_Error::handle($exception);
            return $exception->getMessage();
        }
        return ['status' => Message::get("shipping_adress.update-success", $shipping->code)];
    }

    public function webhooksGrabPost(Request $request)
    {
        $input = $request->all();
//        Log::info(json_encode($input));
        try {
            LogShippingOrder::insert([
                'order_code'           => null,
                'type'                 => "JSON-GRAB",
                'code_shipping_method' => $input['deliveryID'] ?? null,
                'reponse_json'         => json_encode($input),
            ]);
            if (empty($input)) {
                return $this->response->noContent();
            }
            $shippingOrder = ShippingOrder::where('code_type_ghn', $input['deliveryID'])->first();
            if (empty($shippingOrder)) {
                return $this->response->noContent();
            }
            $shippingOrder->delivery_status        = GRAB::STATUS[$input['status']];
            $shippingOrder->status_shipping_method = $input['status'];
            if (empty($shippingOrder->tracking_url)) {
                $shippingOrder->tracking_url = !empty($input['trackURL']) ? $input['trackURL'] : null;
            }
            $checkShippingHistoryStatus = ShippingHistoryStatus::model()->where(['shipping_id' => $shippingOrder->code, 'status_code' => $input['status']])->first();
            if (!empty($checkShippingHistoryStatus)) {
                $checkShippingHistoryStatus->phone_driver  = $input['driver']['phone'] ?? null;
                $checkShippingHistoryStatus->name_driver   = $input['driver']['name'] ?? null;
                $checkShippingHistoryStatus->license_plate = $input['driver']['license_plate'] ?? null;
                $checkShippingHistoryStatus->log_shipping  = json_encode($input) ?? null;
                $checkShippingHistoryStatus->created_at    = date("Y-m-d H:i:s");
                $checkShippingHistoryStatus->save();
            } else {
                ShippingHistoryStatus::insert([
                    'shipping_id'      => $shippingOrder->code,
                    'status_code'      => $input['status'],
                    'text_status_code' => GRAB::STATUS[$input['status']],
                    'phone_driver'     => $input['driver']['phone'] ?? null,
                    'name_driver'      => $input['driver']['name'] ?? null,
                    'log_shipping'     => json_encode($input) ?? null,
                    'license_plate'    => $input['driver']['licensePlate'] ?? null,
                    'created_at'       => !empty($input['timestamp']) ? date("Y-m-d H:i:s", $input['timestamp']) : date("Y-m-d H:i:s"),
                ]);
            }
            if ($input['status'] == 'COMPLETED') {
                $order = Order::find($shippingOrder->order_id);

                // $order->status                         = ORDER_STATUS_SHIPPED;
                // $shippingOrder->status                 = ORDER_STATUS_SHIPPED;
                // $shippingOrder->status_shipping_method = $input['status'];
                // $order->status_text    = "Đã hoàn thành";
                // $order->save();

                VNP::updateOrderStatusHistory($order);
                // if ($order->payment_method != PAYMENT_METHOD_CASH && $order->payment_status == 1) {
                $order->status         = ORDER_STATUS_COMPLETED;
                $shippingOrder->status = ORDER_STATUS_COMPLETED;
                $order->status_text    = "Đã hoàn thành";
                $order->save();
                try {
                    $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
                    $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
                    if (!empty($dataUpdateDMS)) {
                        $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                        if (!empty($pushOrderStatusDms['errors'])) {
                            foreach ($pushOrderStatusDms['errors'] as $item) {
                                \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                            }
                        } else {
                            if (!empty($pushOrderStatusDms)) {
                                \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                            }
                            if (empty($pushOrderStatusDms)) {
                                \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                            }
                            //                                \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                        }
                    }
                    Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
                } catch (\Exception $exception) {
                    \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
                }

                #CDP
                try {
                    CDP::pushOrderCdp($order, 'webhooksGrabPost - ShippingOrderController - line:1315');
                } catch (\Exception $e) {
                    TM_Error::handle($e);
                }


                VNP::updateOrderStatusHistory($order);
                    #UPDATE[ACCESSTRADE] THANH CONG
                    // try {
                    //     if (!empty($order->access_trade_id)) {
                    //         $status         = ORDER_STATUS_APPROVED;
                    //         $reason         = ORDER_STATUS_NEW_NAME['APPROVED'];
                    //         Accesstrade::update($order, $status, $reason);
                    //     }
                    // } catch (\Exception $e) { }
                // }
            }
            if ($input['status'] == 'CANCELED' || $input['status'] == "FAILED") {

                $order                  = Order::find($shippingOrder->order_id);
                $order->status          = ORDER_STATUS_CANCELED;
                $shippingOrder->status  = ORDER_STATUS_CANCELED;
                $order->status_shipping = "Đơn huỷ/lỗi từ Grab/".$input['status'];
                $order->failed_reason   = !empty($input['failedReason']) ? $input['failedReason'] : null;
                $order->status_text     = "Hủy đơn";
                $order->save();

//                 try {
//                     $statusDms     = array_flip(SYNC_STATUS_NAME_VIETTEL);
//                     $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
//                     if (!empty($dataUpdateDMS)) {
//                         $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
//                         if (!empty($pushOrderStatusDms['errors'])) {
//                             foreach ($pushOrderStatusDms['errors'] as $item) {
//                                 \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
//                             }
//                         } else {
//                             if (!empty($pushOrderStatusDms)) {
//                                 \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
//                             }
//                             if (empty($pushOrderStatusDms)) {
//                                 \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
//                             }
// //                            \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
//                         }

//                     }
//                     Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
//                 }
//                 catch (\Exception $exception) {
//                     \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
//                 }

                VNP::updateOrderStatusHistory($order);
                $shippingOrder->status_shipping_method = $input['status'];
                #UPDATE[ACCESSTRADE] 
                try {
                    if (!empty($order->access_trade_id)) {
                        $status = ORDER_STATUS_CANCELED;
                        $reason = ORDER_STATUS_NEW_NAME['CANCELED'];
                        Accesstrade::update($order, $status, $reason);
                    }
                }
                catch (\Exception $e) {
                }
            }
            if ($input['status'] == "RETURNED" || $input['status'] == "IN_RETURN") {
                $order                 = Order::find($shippingOrder->order_id);
                $order->status         = ORDER_STATUS_RETURNED;
                $shippingOrder->status = ORDER_STATUS_RETURNED;
                $order->save();
                VNP::updateOrderStatusHistory($order);
                $shippingOrder->status_shipping_method = "FAILED";
                try {
                    if (!empty($order->access_trade_id)) {
                        $status = ORDER_STATUS_CANCELED;
                        $reason = ORDER_STATUS_NEW_NAME['CANCELED'];
                        Accesstrade::update($order, $status, $reason);
                    }
                }
                catch (\Exception $e) {
                }
            }
            if ($input['status'] == "IN_DELIVERY") {
                $order               = Order::find($shippingOrder->order_id);

                $order->status_text = 'Đang giao hàng';
                $order->receive_date = date("Y-m-d H:i:s", time());
                $order->save();
            }
        }
        catch (Exception $exception) {
//            LogShippingOrder::insert([
//                'order_code' => $input['deliveryID'] ?? null,
//                'type' => "JSON-GRAB",
//                'code_shipping_method' => $input['deliveryID'] ?? null,
//                'reponse_json' => json_encode($input),
//                'message'    => json_encode($exception->getMessage())
//            ]);
        }
        $shippingOrder->save();
        return $request;
    }

    public function webhooksGHNPost(Request $request)
    {
        $input = $request->all();
        Log::info(json_encode($input));
        if (empty($input)) {
            return $this->response->noContent();
        }
        $shippingOrder = ShippingOrder::where('code_type_ghn', $input['OrderCode'])->first();
        if (empty($shippingOrder)) {
            return $this->response->noContent();
        }
        $shippingOrder->delivery_status = GHN::STATUS[$input['Status']];
        $shippingOrder->save();

        ShippingHistoryStatus::insert([
            'shipping_id'      => $shippingOrder->code,
            'status_code'      => $input['Status'],
            'text_status_code' => GHN::STATUS[$input['Status']],
            'created_at'       => date("Y-m-d H:i:s", time()),
        ]);
        if ($input['Status'] == 'delivered') {
            $order         = Order::find($shippingOrder->order_id);
            $order->status = ORDER_STATUS_SHIPPED;
            $order->save();
        }
    }

    public function webhooksNinjaVan(Request $request)
    {
        $input = $request->all();

        // Log::info(json_encode($input));

        if (empty($input['status'])) {
            return $this->response->noContent();
        }

        if (empty($input['tracking_id'])) {
            return $this->response->noContent();
        }

        $shippingOrder = ShippingOrder::where('code', $input['tracking_id'])->first();
        if (empty($shippingOrder)) {
            return $this->response->noContent();
        }

        try {
            $shippingOrder->delivery_status = $input['status'];
            $shippingOrder->save();

            ShippingHistoryStatus::insert([
                'shipping_id'      => $shippingOrder->code,
                'status_code'      => null,
                'text_status_code' => $input['status'],
                'created_at'       => date("Y-m-d H:i:s", time()),
            ]);

            if ($input['status'] == 'Completed') {
                $order         = Order::find($shippingOrder->order_id);
                $order->status = 'COMPLETED';
                $order->save();
            }
        }
        catch (\Exception $exception) {
        }
        return $this->response->noContent();
    }

    public function printShippingOrder($id)
    {
        $shippingOrder = ShippingOrder::model()->with([
            'order',
            'details',
        ])->where('id', $id)->first();
        if (empty($shippingOrder)) {
            return ['data' => []];
        }
        $dataCompany = Company::find(TM::getCurrentCompanyId());
        //        print_r($dataCompany->toArray());die();
        if (empty($dataCompany)) {
            return $this->response->errorBadRequest(Message::get('V003', Message::get("companies")));
        }
        //        $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        //        $pdf->SetMargins(PDF_MARGIN_LEFT, 40, -1, true);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        // set font
        $pdf->SetFont('dejavusans', '', 10);
        // add a page
        $pdf->AddPage();
        $data = [
            'shipping_order' => $shippingOrder->toArray(),
            'company'        => $dataCompany,
        ];

        $html = view("order.shipping_order_print", compact('data'));
        $pdf->writeHTML($html);
        $name = "{$shippingOrder->code}-{$shippingOrder->order->customer_id}.pdf";
        if (!file_exists(storage_path() . "/shippingOrder/print")) {
            mkdir(storage_path() . "/shippingOrder/print", 0755, true);
        }
        $filePdf = storage_path() . "/shippingOrder/print/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename='$name'");
        header('Access-Control-Allow-Origin: *');
        readfile($filePdf);

        return Message::get('orders.print-success', $shippingOrder->code);
    }

    public function updateShippingOrder(
        $id,
        Request $request,
        ShippingOrderUpdateValidator $shippingOrderUpdateValidator
    )
    {
        $input       = $request->all();
        $input['id'] = $id;
        $shippingOrderUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model->updateShippingOrder($input);
            DB::commit();

            $checkStatus = DB::table('order_status_histories')->where('order_status_code', 'SHIPPED')->where('order_id',$result->order->id)
            ->first();
         
            if($checkStatus){
                TM::sendMessage("Cập nhật trạng thái đơn hàng thành công #:". $result->order->code);
            }else{
                TM::sendMessage("Cập nhật trạng thái đơn hàng không thành công #:". $result->order->code);
                (new OrderModel())->updateOrderStatusHistory($result->order);
            }
        }
        catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("R002", $result->code)];
    }

    private function getOrderDetailsById($orderId)
    {
        $orderDetai = OrderDetail::model()
            ->select([
                'p.weight as weight',
                'p.weight_class as weight_class',
                'p.short_description as short_description',
                'p.code as  product_code',
                'p.name as  product_name',
                'p.gift_item as  gift_item',
                'p.specification_id as  specification_id',
                'order_details.price as price',
                'order_details.total as total',
                'order_details.qty as qty',
                'order_details.discount as discount'
            ])
            ->join('orders as o', 'o.id', '=', 'order_details.order_id')
            ->join('products as p', 'p.id', '=', 'order_details.product_id')
            ->where('o.id', $orderId)
            ->get()->toArray();
        return $orderDetai;
    }

    public function printShippingReceiveAndPayment($code)
    {
        $code          = explode(',', $code);
        $shippingOrder = ShippingOrder::model()
            ->select([
                'shipping_orders.code as SPGH',
                'shipping_orders.code_type_ghn as code_shipping',
                'o.shipping_address as shipping_address',
                'o.shipping_address_phone as shipping_address_phone',
                'o.distributor_name as NPP',
                'o.distributor_code as distributor_code',
                'u.phone as phone',
                'o.shipping_address_full_name as customer_name',
                'p.phone as customer_phone',
                'p.id as customer_id',
                'o.street_address as street_address',
                'o.shipping_address_full_name as shipping_address_full_name',
                'sd.unit_name as unit',
                'o.total_price as total_price',
                'o.total_discount as total_discount',
                'o.id as orderId',
                'o.created_at as create',
                'o.note as description',
                'shipping_orders.order_id as orderId'
            ])
            ->join('shipping_order_details as sd', 'sd.shipping_order_id', '=', 'shipping_orders.id')
            ->join('orders as o', 'o.id', '=', 'shipping_orders.order_id')
            ->leftjoin('users as u', 'u.id', '=', 'o.distributor_id')
            ->join('users as uc', 'uc.id', '=', 'o.customer_id')
            ->join('profiles as p', 'p.user_id', '=', 'uc.id')
            ->where('shipping_orders.company_id', TM::getCurrentCompanyId())
            ->whereIn('shipping_orders.code', $code)
            ->groupBy('shipping_orders.code')
            ->groupBy('customer_id')
            ->get()->toArray();
        if (empty($shippingOrder)) {
            return $this->response->errorBadRequest(Message::get("V003", "Shipping Order Code"));
        }
//        $pr               = 0;
//        $promotion_totals = PromotionTotal::model()->where('order_id', $shippingOrder[0]['orderId'])->where('promotion_act_type', '!=', 'accumulate_point')->get();
//        if (!empty($promotion_totals)) {
//            foreach ($promotion_totals as $pt) {
//                print_r(json_encode($pt));die;
//                $pr += $pt->value;
//            }
//        }
        $orderId         = Arr::pluck($shippingOrder, 'orderId');
        $distributorCode = Arr::pluck($shippingOrder, 'distributor_code');
        $distributor     = [];
        foreach ($distributorCode as $value) {
            $distributor[$value] = $value;
        }
        if (count($distributor) > 1) {
            return $this->responseError(Message::get("V006", "Nhà phân phối", "duy nhất"));
        }

        $dataByCustomer = [];
        foreach ($shippingOrder as $key => $value) {
            if (!empty($order_id = $value['orderId'])) {
                $weight          = 0;
                $product_item    = [];
                $sellers         = User::model()
                    ->select(['users.phone as seller_phone', 'p.full_name as seller_name'])
                    ->join('orders as o', 'o.seller_id', '=', 'users.id')
                    ->join('profiles as p', 'p.user_id', '=', 'users.id')
                    ->where('o.id', $order_id)
                    ->first();
                $order           = Order::model()->with('shippingStatusHistories')->where('id', $order_id)->first();
                $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
                if ($order->free_item && $order->free_item != "[]") {
                    foreach (json_decode($order->free_item) as $key => $item) {
                        foreach ($item->text as $text) {
                            $product_item[] = [
                                'product_name' => $text->title_gift ?? $text->product_name, //. "- Sản phẩm quà tặng",
                                'qty'          => !empty($text->qty_gift) ? (int)$text->qty_gift * $item->value : 1,
                                'price'        => "",
                            ];
                            $weight         += $text->weight * ($text->qty_gift ?? 1) * $weight_converts[($text->weight_class)];
                        }
                    }
                }
                $promotion_totals = $order->promotionTotals->map(function ($item) {
                    return [
                        'title' => $item->promotion_name,
                        'text'  => ($item->promotion_type != PROMOTION_TYPE_POINT && $item->promotion_type != PROMOTION_TYPE_FLASH_SALE ? "-" : "") . number_format(round($item->value)) . " " . self::UNIT_FORMAT[$item->promotion_type ?? PROMOTION_TYPE_AUTO],
                    ];
                });
                $totals           = $promotion_totals->toArray();
                if (!empty($order->total_info)) {
                    $total_info = json_decode($order->total_info);
                    $coupon_key = array_search('coupon', array_column($total_info, 'code'));
                    if (isset($coupon_key) && is_integer($coupon_key) == true) {
                        $coupon   = [
                            'title' => $total_info[$coupon_key]->title,
                            'text'  => $total_info[$coupon_key]->text
                        ];
                        $totals[] = $coupon;
                    }
                    $coupon_ship = array_search('coupon_delivery', array_column($total_info, 'code'));

                    if (isset($coupon_ship) && is_integer($coupon_ship) == true) {
                        $coupon_delivery = [
                            'title' => $total_info[$coupon_ship]->title,
                            'text'  => $total_info[$coupon_ship]->text
                        ];

                        $totals[] = $coupon_delivery;
                    }
                }
            }
            $totalPrice            = 0;
            $totalPurchaseDiscount = 0;
            $totalOtherDiscount    = 0;
//            $totalPayment          = 0;
//            $discount              = $pr;
            $discount            = $value['total_discount'];
            $totalPayment        = $value['total_price'];
            $orderDetails        = $this->getOrderDetailsById($value['orderId']);
            $details             = [];
            $allSpecification    = Specification::all()->pluck('value', 'id')->toArray();
            $total_order_details = 0;
            foreach ($orderDetails as $item) {
                // if ($item['gift_item'] && $item['gift_item'] != "[]") {
                //     foreach (json_decode($item['gift_item']) as $gift) {
                //         if (!empty($gift->weight)) {
                //             $product_item[] = [
                //                 'product_name' =>  $gift->product_name . "- Sản phẩm quà tặng",
                //                 'qty' => 1,
                //                 'price' => "",
                //             ];
                //            $weight += $gift->weight * $weight_converts[$gift->weight_class];
                //         }
                //     }
                // }
                $weight              += $item['weight'] * $item['qty'] * $weight_converts[$item['weight_class']];
                $muahang             = 0;                                    // tạm để trống về sau cập nhật
                $khac                = 0;                                    // tạm để trống về sau cập nhật
                $khac                = $item['discount'] * $item['qty'];     // tạm để trống về sau cập nhật
                $sum_total           = $item['price'] * $item['qty'] - $khac;
                $payment             = $sum_total - ($muahang + $khac);
                $total_order_details += $item['price'] * $item['qty'];
                $details[]           = [
                    'product_code'      => $item['product_code'],
                    'product_name'      => $item['product_name'] ?? "",
                    'short_description' => $item['short_description'] ?? "",
                    'QC'                => $allSpecification[$item['specification_id']] ?? "",
                    'qty'               => $item['qty'] ?? "",
                    //                    'le'                => "",
                    'price'             => $item['price'] ?? "",
                    //                    'total'             => $item['total'] ?? "",
                    'discount'          => $item['discount'] * $item['qty'],
                    'total'             => $sum_total ?? "",
                    'mua_hang'          => $muahang,
                    'khac'              => $khac,
                    'payment'           => $payment ?? "",
                ];

                $totalPrice            += $sum_total;
                $totalPurchaseDiscount += $khac;
                $totalOtherDiscount    += $muahang;
//                $totalPayment          += $payment;
            }
            $totalPaymentConvert             = $this->convert_number_to_words($totalPayment - $discount);
            $value['totalPrice']             = $totalPrice ?? "";
            $value['totalPurchaseDiscount']  = $order->is_freeship == 1 ? $total_order_details - $value['total_price'] : $total_order_details - $value['total_price'] + $order->ship_fee;
            $value['totalPaymentNoDiscount'] = $total_order_details;
            // $value['totalPurchaseDiscount']        = ($totalPurchaseDiscount + $discount) ?? "";
            $value['totalOtherDiscount']           = $totalOtherDiscount ?? "";
            $value['totalPayment']                 = ($totalPayment - $discount) ?? "";
            $value['totalPaymentConvert']          = $totalPaymentConvert ?? "";
            $value['details']                      = array_merge($details, $product_item);
            $value['promotion_totals']             = $totals ?? "";
            $dataByCustomer[$value['customer_id']] = $value;
        }
        $data          = [
            "NPP"                   => $shippingOrder[0]['NPP'] ?? "",
            "npp_phone"             => $shippingOrder[0]['phone'] ?? "",
            "customer_phone"        => $shippingOrder[0]['customer_phone'] ?? "",
            "unit"                  => $shippingOrder[0]['unit'] ?? "",
            "MST"                   => "",
            "NVGH"                  => "",
            "SPGH"                  => implode(',', $code) ?? "",
            "print_date"            => date('Y-m-d H:i', time()),
            "seller_name"           => $sellers->seller_name ?? "",
            "seller_phone"          => $sellers->seller_phone ?? "",
            "street_address"        => $shippingOrder[0]['street_address'] ?? "",
            "shipping_address"      => $shippingOrder[0]['shipping_address'] ?? "",
            "details"               => $dataByCustomer,
            "shipping_method"       => $order->shipping_method_name ?? "",
            "ship_fee"              => !empty($order->ship_fee_start) ? $order->ship_fee_start : "",
            "ship_store"            => !empty($order->ship_fee_start) ? ($order->ship_fee_start - $order->ship_fee) : "",
            "shipping_method_code"  => $shippingOrder[0]['code_shipping'] ?? "",
            "seller_shipping"       => $order->shipping_status_histories[0]['name_driver'] ?? "",
            "seller_shipping_phone" => $order->shipping_status_histories[0]['phone_driver'] ?? "",
            "weight"                => $weight,
            "payment_method"        => $order->payment_method ?? ""
        ];
        $name_pdf_code = implode('-', $code) ?? "";
        $date          = date('YmdHis', time());;
        $pdf = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        // set font
        $pdf->SetFont('dejavusans', '', 10);
        // add a page

        $pdf->AddPage();

        $html = view(TM::getCurrentStoreId() == 58 ? "order.print_shipping_order_receive_payment_nutifood" : "order.print_shipping_order_receive_payment", compact("data"));
        //        echo $html;die;
        $pdf->writeHTML($html);
        $name = "ShippingOrder-$name_pdf_code-$date" . ".pdf";
//        $name = "ShippingReceiveAndPayment.pdf";
        if (!file_exists(storage_path() . "/ShippingOrders")) {
            mkdir(storage_path() . "/ShippingOrders", 0755, true);
        }
        $filePdf = storage_path() . "/ShippingOrders/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=$name");
        header('Access-Control-Allow-Origin: *');

        readfile($filePdf);
        return Message::get('print-success', $name);
    }


    public function reportSummaryBySelectedOrder(Request $request)
    {
        $input = $request->all();

        $shippingOrder = ShippingOrder::model()
            ->select([
                'shipping_orders.code as SPGH',
                'o.shipping_address as shipping_address',
                'o.distributor_name as NPP',
                'u.phone as phone',
                'o.full_name as customer_name',
                'o.street_address as street_address',
                'o.id as orderId',
                'shipping_orders.order_id as orderId',
            ])
            ->join('shipping_order_details as sd', 'sd.shipping_order_id', '=', 'shipping_orders.id')
            ->join('orders as o', 'o.id', '=', 'shipping_orders.order_id')
            ->join('users as u', 'u.id', '=', 'o.distributor_id')
            ->join('users as uc', 'uc.id', '=', 'o.customer_id')
            ->join('profiles as p', 'p.user_id', '=', 'uc.id')
            ->where('o.distributor_id', $input['distributor_id'])
            ->where('shipping_orders.company_id', TM::getCurrentCompanyId())
            ->whereIn('shipping_orders.code', explode(",", $input['code']))
            ->groupBy('shipping_orders.code')
            ->get()->toArray();
        if (empty($shippingOrder)) {
            return $this->response->errorBadRequest(Message::get("V003", "Shipping Orders"));
        }
        //        if (!empty($shippingOrder->orderId)) {
        //            $sellers = User::model()
        //                ->select(['users.phone as seller_phone', 'p.full_name as seller_name'])
        //                ->join('orders as o', 'o.seller_id', '=', 'users.id')
        //                ->join('profiles as p', 'p.user_id', '=', 'users.id')
        //                ->where('o.id', $shippingOrder->orderId)
        //                ->first();
        //        }
        $arrayIdOrder = [];
        foreach ($shippingOrder as $value) {
            $arrayIdOrder[] = $value['orderId'];
        }
        $orderDetails = OrderDetail::model()
            ->select([
                'p.short_description as short_description',
                'p.name as product_name',
                'p.code as  product_code',
                'p.code as  product_code',
                'p.specification_id as  specification_id',
                'order_details.price as price',
                'order_details.qty as qty',
                'order_details.total as total',
            ])
            ->join('orders as o', 'o.id', '=', 'order_details.order_id')
            ->join('products as p', 'p.id', '=', 'order_details.product_id')
            ->whereIn('o.id', $arrayIdOrder)
            ->get()->toArray();

        $pdf = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        // set font
        $pdf->SetFont('dejavusans', '', 10);
        // add a page
        $pdf->AddPage();
        $details               = [];
        $totalPrice            = 0;
        $totalPurchaseDiscount = 0;
        $totalOtherDiscount    = 0;
        $totalPayment          = 0;
        $allSpecification      = Specification::all()->pluck('value', 'id')->toArray();
        foreach ($orderDetails as $item) {
            $muahang               = 0;     // tạm để trống về sau cập nhật
            $khac                  = 0;     // tạm để trống về sau cập nhật
            $payment               = $item['total'] - ($muahang + $khac);
            $details[]             = [
                'product_code'      => $item['product_code'],
                'product_name'      => $item['product_name'],
                'short_description' => $item['short_description'] ?? "",
                'QC'                => $allSpecification[$item['specification_id']] ?? "",
                'crates'            => $item['qty'] ?? "",
                'le'                => "",
                'price'             => $item['price'] ?? "",
                'total'             => $item['total'] ?? "",
                'mua_hang'          => $muahang,
                'khac'              => $khac,
                'payment'           => $payment ?? "",
            ];
            $totalPrice            += $item['total'];
            $totalPurchaseDiscount += $khac;
            $totalOtherDiscount    += $muahang;
            $totalPayment          += $payment;
        }
        //        print_r($details);die;
        $totalPaymentConvert = $this->convert_number_to_words($totalPayment);
        $data                = [
            "NPP"                   => $shippingOrder[0]['NPP'] ?? "",
            "npp_phone"             => $shippingOrder[0]['phone'] ?? "",
            "MST"                   => "",
            "SPGH"                  => $input['code'],
            "print_date"            => date('d-m-Y H:i', time()),
            "seller_name"           => $sellers->seller_name ?? "",
            "seller_phone"          => $sellers->seller_phone ?? "",
            "street_address"        => $shippingOrder->street_address ?? "",
            "shipping_address"      => $shippingOrder->shipping_address ?? "",
            "customer_name"         => $shippingOrder->customer_name ?? "",
            "NVGH"                  => "",
            "details"               => $details,
            "totalPrice"            => $totalPrice ?? "",
            "totalPurchaseDiscount" => $totalPurchaseDiscount ?? "",
            "totalOtherDiscount"    => $totalOtherDiscount ?? "",
            "totalPayment"          => $totalPayment ?? "",
            "totalPaymentConvert"   => $totalPaymentConvert ?? "",
        ];
        $html                = view("order.print_summary_by_selected_order", compact("data"));
        $pdf->writeHTML($html);
        //        $name = "ShippingOrder-" . $date . ".pdf";
        $name = "summary_by_selected_order" . ".pdf";
        if (!file_exists(storage_path() . "/ShippingOrders")) {
            mkdir(storage_path() . "/ShippingOrders", 0755, true);
        }
        $filePdf = storage_path() . "/ShippingOrders/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=$name");
        header('Access-Control-Allow-Origin: *');
        readfile($filePdf);

        return Message::get('print-success', $name);
    }

//    public function getQuyCach($input)
//    {
//        if (!empty($input['short_description'])) {
//            $codeQc = substr($input['short_description'], -8, 8);
//
//            $codeQcArray = explode("x", $codeQc);
//            $QC          = "";
//            foreach ($codeQcArray as $item) {
//                $checkQc = strlen($item);
//                if ($checkQc == 2) {
//                    $QC = $item;
//                }
//            }
//            return $QC;
//        }
//    }

    public function printExportOrder($code, Request $request)
    {
        $input = $request->all();
        try {
            $shipping_order  = ShippingOrder::model()->with(['order', 'details'])->where('code', $code)->where('company_id', TM::getCurrentCompanyId())->first();
            $company         = Company::find(TM::getCurrentCompanyId());
            $shipping_detail = ShippingOrderDetail::model()->where('shipping_order_id', $shipping_order->id)->get()->toArray();

            $order = Order::where('code', $shipping_order->code)->first();

            $pdf = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetPrintHeader(false);
            $pdf->SetPrintFooter(false);
            // set font
            $pdf->SetFont('dejavusans', '', 10);
            // add a page
            $pdf->AddPage();
            $customer = json_decode($shipping_order->result_json, true);
//            $data     = [
//                'customer_name'    => $customer['customer_name'] ?? null,
//                'shipping_address' => $customer['shipping_address'] ?? null,
//                'code'             => Arr::get($shipping_order, 'ship_code', null),
//                'payment_method'   => PAYMENT_METHOD_NAME[$customer['payment_method']] ?? null,
//                'shipping_method'  => $customer['shipping_method'] ?? null,
//                'company'          => Arr::get($company, 'name', null),
//                // 'user_address'     => $customer['customer']['profile']['address'] ?? null,
//                'user_address'     => $customer['street_address'] ?? null,
//                'shipping_detail'  => $shipping_detail ?? null,
//                'count_print'      => $shipping_order->count_print ?? null,
//                'reason'           => $shipping_order->reason ?? null
//            ];
            $data = [
                'customer_name'    => $order->customer_name ?? null,
                'shipping_address' => $order->shipping_address ?? null,
                'code'             => Arr::get($shipping_order, 'ship_code', null),
                'payment_method'   => $order->payment_method ?? null,
                'shipping_method'  => $order->payment_method_name ?? null,
                'company'          => Arr::get($company, 'name', null),
                // 'user_address'     => $customer['customer']['profile']['address'] ?? null,
                'user_address'     => $order->shipping_address ?? null,
                'shipping_detail'  => $shipping_detail ?? null,
                'count_print'      => $shipping_order->count_print ?? null,
                'reason'           => $shipping_order->reason ?? null
            ];
//            if ($shipping_order->count_print >= 1) {
//                if (empty($input['reason'])) {
//                    return $this->response->errorBadRequest(Message::get('reason.not-exist'));
//                }
//                $shipping_order->count_print += 1;
//                $shipping_order->reason      = $input['reason'];
//                $data['reason']              = $input['reason'];
//            } else {
//                $shipping_order->count_print += 1;
//            }
//            $shipping_order->save();

            $html = view("order.print_export_order", compact("data"));
            //            echo $html;die;
            $pdf->writeHTML($html);
            $name = "PrintExportOrder.pdf";
            if (!file_exists(storage_path() . "/ExportOrderForm")) {
                mkdir(storage_path() . "/ExportOrderForm", 0755, true);
            }
            $filePdf = storage_path() . "/ExportOrderForm/$name";
            $pdf->Output($filePdf, 'F');
            header("Content-type:application/pdf");
            header("Content-Disposition:attachment;filename=$name");
            header('Access-Control-Allow-Origin: *');
            readfile($filePdf);
            return Message::get('print-success', $name);
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    public function getClientShipFee(Request $request)
    {
        return $this->responseData($this->shippingType);
    }

    public function checkGRAB(Request $request)
    {
        $input    = $request->all();
        $token    = GRAB::getToken(0);
        $client   = new Client();
        $response = $client->post(env("GRAB_END_POINT") . "/deliveries/quotes", [
            'headers' => ['Content-Type' => 'application/json', 'Authorization' => "Bearer " . $token],
            'body'    => json_encode($input),
        ]);
        $response = $response->getBody()->getContents() ?? null;
        $response = !empty($response) ? json_decode($response, true) : [];
        print_r(json_encode($response));
        die;
    }

    public function fakeGrabPushToDMS(Request $request)
    {
        $orderCode = $request->get('order_code');
        $order     = Order::where('code', $orderCode)->first();
        if (!$order) {
            throw new \Exception("Đơn hàng không tồn tại");
        }
        $order->status = ORDER_STATUS_COMPLETED;
        $order->save();

        try {
            $dataUpdateDMS = OrderSyncDMS::updateStatusDMS(array($order->code), "C", $order->status);
            if (!empty($dataUpdateDMS)) {
                $pushOrderStatusDms = OrderSyncDMS::callApiDms($dataUpdateDMS, "UPDATE-ORDER");
                if (!empty($pushOrderStatusDms['errors'])) {
                    foreach ($pushOrderStatusDms['errors'] as $item) {
                        \App\Supports\Log::logSyncDMS($item['data']['orderNumber'], $item['errorMgs'], $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $item);
                    }
                } else {
                    if (!empty($pushOrderStatusDms)) {
                        \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                    }
                    if (empty($pushOrderStatusDms)) {
                        \App\Supports\Log::logSyncDMS($order->code, "Connection Error", $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, $pushOrderStatusDms);
                    }
//                    \App\Supports\Log::logSyncDMS($order->code, null, $dataUpdateDMS ?? [], "UPDATE-STATUS", 1, $pushOrderStatusDms);
                }

            }
            Order::where('code', $order->code)->update(['log_order_dms' => json_encode($dataUpdateDMS)]);
        }
        catch (\Exception $exception) {
            \App\Supports\Log::logSyncDMS($order->code, $exception->getMessage(), $dataUpdateDMS ?? [], "UPDATE-STATUS", 0, null);
        }
        #CDP
        try {
            CDP::pushOrderCdp($order, 'fakeGrabPushToDMS - ShippingOrderController - line:2131');
        } catch (\Exception $e) {
        }
        return $this->responseData();
    }

    public function fakeChangeOrderToOnline(Request $request)
    {
        $orderCode = $request->get('order_code');
        $order     = Order::where('code', $orderCode)->first();
        if (!$order) {
            throw new \Exception("Đơn hàng không tồn tại");
        }
        $order->payment_status         = 1;
        $order->payment_method         = 'bank_transfer';
        $order->shipping_method_code   = 'GRAB';
        $order->shipping_method_name   = 'Grab Express';
        $order->shipping_service       = 'INSTANT';
        $order->shipping_note          = 'Tất cả các ngày trong tuần';
        $order->saving                 = '81600';
        $order->ship_fee               = '16000';
        $order->ship_fee_start         = '61000';
        $order->estimated_deliver_time = '48 giờ';
        $order->lading_method          = 'standard';
        $order->save();

        return $this->responseData();
    }
}
