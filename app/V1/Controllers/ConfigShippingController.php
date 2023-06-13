<?php
/**
 * User: Phan Văn
 */

namespace App\V1\Controllers;

use App\Cart;
use App\Category;
use App\Company;
use App\ConfigShipping;
use App\ConfigShippingCondition;
use App\Product;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserCompany;
use App\V1\Models\CartModel;
use App\V1\Models\CompanyModel;
use App\V1\Models\ConfigShippingConditionModel;
use App\V1\Models\ConfigShippingModel;
use App\V1\Transformers\Company\CompanyTransformer;
use App\V1\Transformers\ConfigShipping\ConfigShippingClientTransformer;
use App\V1\Transformers\ConfigShipping\ConfigShippingTransformer;
use App\V1\Validators\CompanyCreateValidator;
use App\V1\Validators\CompanyUpdateValidator;
use App\V1\Validators\ConfigShipping\ConfigShippingCreateValidator;
use App\V1\Validators\ConfigShipping\ConfigShippingUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConfigShippingController extends BaseController
{
    protected $model_config_shipping;
    protected $model_condition_shipping;
    public function __construct()
    {
        $this->model_config_shipping = new ConfigShippingModel();
        $this->model_condition_shipping = new ConfigShippingConditionModel();
    }
    
    protected const CONSTANT_VALUE_CONDITION = [
        'UNDER_CATEGORY' => 'under_category',
        'UNDER_AREA'     => 'under_area',
        'CART_TOTAL'     => 'cart_total',
        'TOTAL_WEIGHT'   => 'total_weight',
    ];

    protected const CONDITION = [
        'LHB'            => 'LHB',
        'NHB'            => 'NHB',
        'B'              => 'B',
        'NH'             => 'NH',
        'LH'             => 'LH',
    ];

    protected const CONSTANT_VALUE = [
        'DELIVERY_CODE_GHN' => '',
        'DELIVERY_NAME_GHN' => '',
        'DELIVERY_CODE_GHTK' => '',
        'DELIVERY_NAME_GHTK' => '',
    ];


     /**
     * danh saách của admin
     * 
     */

    // thêm config
    public function create(Request $request, ConfigShippingCreateValidator $config_shipping)
    {
        $input = $request->all();
        $config_shipping->validate($input);
        try {

            DB::beginTransaction();
            $result = $this->model_config_shipping->insert($input);
            // Log::create($this->model->getTable(), $result->title);
            DB::commit();
            return response()->json([
                'message' => 'Thêm thành công',
                'status'  => "success"
            ], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

    }
    // danh sách
    public function search(Request $request) {
        $input = $request->all();
        try {
            $config_shipping = $this->model_config_shipping->searchConfigShipping($input, [], $input['limit'] ?? null);
            return $this->response->paginator($config_shipping, new ConfigShippingTransformer());
        } catch (\Exception $ex) {   
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    // chi tiết
    public function detail(Request $request, $id) {
        $input = $request->all();
        try {
            $result = ConfigShipping::findOrFail($id);
            if (empty($result)) {
                return ['data' => []];
            }
            return $this->response->item($result, new ConfigShippingTransformer());
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

     // chi tiết
     public function update(Request $request, $id, ConfigShippingUpdateValidator $updateValidator) {
        $input       = $request->all();
        $input['id'] = $id;
        $updateValidator->validate($input);
        if (!empty($input['name'])) {
            $input['name'] = str_clean_special_characters($input['name']);
        }
        if (!empty($input['code'])) {
            $input['code'] = str_clean_special_characters($input['code']);
        }
        $updateValidator->validate($input);
        try {
            DB::beginTransaction();
            $result = $this->model_config_shipping->update($input);
            // if (empty($result->id) && $result['status_code'] == 400) {
            //     return response()->json(['message' => $result['message']], 400);
            // }
            // Log::update($this->model->getTable(), "#ID:" . $result->id, null, $result->name);
            DB::commit();
            return response()->json([
                'message' => "Cập nhập thành công",
                'status' => 'success'
            ], 200);
        } catch (\Exception $ex) {

            DB::rollBack();
            // $response = TM_Error::handle($ex);
            return $this->response->error($ex->getMessage(), $ex->getCode());
        }
        return ['status' => Message::get("coupons.update-success", $result->name)];
    }

    public function update_status($id)
    {
        $config_shipping = ConfigShipping::find($id);

        try {
            DB::beginTransaction();
            if ($config_shipping->is_active === 1) {
                $msgCode              = "config-shipping.inactive-success";
                $config_shipping->is_active = "0";
            } else {
                $config_shipping->is_active = "1";
                $msgCode              = "config-shipping.active-success";
            }
            $config_shipping->save();
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest(Message::get($msgCode, $config_shipping->phone));
        }

        return ['status' => Message::get($msgCode, $config_shipping->code)];

    }


    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $ConfigShipping = ConfigShipping::find($id);
            if (empty($ConfigShipping)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            ConfigShippingCondition::model()->where('config_shipping_id', $ConfigShipping->id)->delete();
            $ConfigShipping->delete();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("config-shipping.delete-success", $ConfigShipping->code)];
    }

    /**
     * Get list client
     * 
     * 
     */

     public function listClient(Request $request) {
        $input = $request->all();

        try {
            if(empty($input['cart_id'])){
                return $this->responseError("Vui lòng nhập id của giỏ hàng", 400);
            }

            $cart = Cart::findOrFail($input['cart_id']);
            $sub_total = (new CartModel())->getSubTotal($cart);
            $customer_city_code = $cart->customer_city_code ?? null;
            $cart_categories = $this->getCategoryCart($cart);
            $inputGet = [
                'sub_total' => $sub_total,
                'customer_city_code' => $customer_city_code,
                'cart_categories' => $cart_categories
            ];
            // dd($inputGet);
            $config_shipping = ConfigShipping::model()
                ->where('is_active', 0)
                ->whereHas('config_shipping_conditions',function($query) use ($inputGet) {
                    $query->where(function ($query) use ($inputGet){
                        $query->where('condition_name' , 'under_area')
                                ->whereJsonContains('condition_arrays', $inputGet['customer_city_code']);
                    });
                })->get();
            $config_shipping_clone = collect();
            $config_shipping_clone = $this->handleFilterListClient($config_shipping, $cart, $inputGet);
        
            return $this->response->collection($config_shipping_clone, new ConfigShippingClientTransformer());      
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.set-shipping-method-success")];
    }

    public function setShippingMethod(Request $request)
    {
        $input  = $request->all();
        $userId = TM::getCurrentUserId();
        if ($userId) {
            $cart = Cart::model()->select('id')->where('user_id', $userId)->first();
        } else {
            if (empty($input['session_id'])) {
                return $this->responseError(Message::get("V001", Message::get("session_id")));
            }
            $cart = Cart::model()->select('id')->where('session_id', $input['session_id'])->first();
        }

        if (empty($cart)) {
            return $this->responseError(Message::get("V003", Message::get("carts")));
        }
        //        $key = array_search('total', array_column($cart->total_info, 'code'));
        try {
            DB::beginTransaction();
            $cart->shipping_method        = $input['shipping_method'] ?? null;
            $cart->shipping_method_code   = $input['shipping_code'] ?? null;
            $cart->shipping_method_name   = $input['shipping_name'] ?? null;
            $cart->shipping_service       = $input['shipping_service'] ?? null;
            $cart->shipping_note          = $input['shipping_note'] ?? null;
            $cart->shipping_diff          = $input['shipping_diff'] ?? null;
            $cart->service_name           = $input['service_name'] ?? null;
            $cart->extra_service          = $input['extra_service'] ?? null;
            $cart->ship_fee               = $input['ship_fee'] ?? null;
            $cart->estimated_deliver_time = $input['time_from'] . ' - ' . $input['time_to'] . ' '. $input['time_type'] ?? null;
            $cart->lading_method          = $input['lading_method'] ?? null;
            $cart->ship_fee_start         = $input['ship_fee_start'] ?? 0;
            $cart->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("carts.set-shipping-method-success")];
    }


    protected function getCategoryCart (Cart $cart) {
        $cart_details = $cart->details ?? [];

        $output = [];
        // dd($cart_details);
        foreach($cart_details as $key => $cart_detail) {
            $category_ids = $cart_detail->product_category ?? null;
            if($category_ids){
                $a = explode(',', $category_ids);
                $a = Category::whereIn('id', $a)->pluck('code')->toArray();
                $output = array_merge($output, $a);
            }
        }
        $output = array_unique($output);
        return $output;
    }

    protected function handleFilterListClient($config_shipping, $cart, $inputGet = []) {
        $config_shipping_clone = collect();
        // dd($config_shipping);
        foreach($config_shipping as $key1 => $cfs) {
            $config_shipping_condition = $cfs->config_shipping_conditions ?? [];
            $check_condition = true;
            // if($config_shipping->code == 'shipping-method-4') {
            //     dd(123);
            // }
            foreach($config_shipping_condition as $key => $csd) {
                if($csd->condition_name == 'under_category') {
                    $arr = $csd->condition_arrays ? json_decode($csd->condition_arrays) : [];
                    $arr_check = array_intersect($inputGet['cart_categories'], $arr);
                    // dd($arr_check);
                    if(count($arr_check) <= 0){
                        $check_condition = false;
                    }
                    // dd($config_shipping->code);
                   
                }
                if($csd->condition_name == 'cart_total'){
                    $calculation = $csd->condition_type ?? null;
                    $condition_number = $csd->condition_number;
                    // dd($this->switchCondition($calculation, $condition_number, $inputGet['sub_total']));
                    if(!$this->switchCondition($calculation, $condition_number, $inputGet['sub_total'])){
                        $check_condition = false;
                    }
                }
                if($csd->condition_name == 'total_weight') {
                    $condition_number = $csd->condition_number ?? null;
                    $cart_weight = (float) $cart->total_weight ?? null;
                    $calculation = $csd->condition_type ?? null;
                    // dd($cart_weight);
                    // $condition_number = $csd->condition_number;
                    if(!$this->switchCondition($calculation, $condition_number, $cart_weight)) {
                        $check_condition = false;
                    }
                }
            }
            if($check_condition){
                // dd($config_shipping->pull($key1));
                $config_shipping_clone[] = $config_shipping->pull($key1);
            }
        }

        return $config_shipping_clone;
    }

    protected function switchCondition($calculation, $condition_number, $condition_value) {
        switch ($calculation) {
            case 'LHB':
                if(!$condition_value >= $condition_number){
                    return false;
                }
            break;
            
            case 'NHB':
                if(!$condition_value <= $condition_number){
                    return false;
                }
            break;
            
            case 'B':
                if(!$condition_value == $condition_number){
                    return false;
                }
            break;
            
            case 'NH':
                if(!$condition_value < $condition_number){
                    return false;
                }
            break;
            
            case 'LH':
                // dd(123);
                if(!$condition_value > $condition_number){
                    return false;
                }
            break;
            
            // default:
            //     $check_cart_total = false;
            // break;
        };

        return true;
    }

}
