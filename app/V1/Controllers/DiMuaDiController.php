<?php

namespace App\V1\Controllers;

use App\AccessTradeSetting;
use App\Age;
use App\City;
use App\Country;
use App\District;
use App\LogAccesstradeOrder;
use App\Order;
use App\OrderDetail;
use App\Product;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\AccessTradeModel;
use App\V1\Models\AccessTradeSettingModel;
use App\V1\Models\AgeModel;
use App\V1\Transformers\AccessTradeSetting\AccessTradeSettingTransformer;
use App\V1\Validators\Age\AgeUpsertValidator;
use App\Ward;
use Illuminate\Http\Request;

use function GuzzleHttp\json_encode;

class DiMuaDiController extends BaseController
{
    protected $model;
    public function postBack(Request $request)
    {
        try {
            $input                  = $request->all();
            $campaign_id            = $input['campaign_id'];
            $access_trade_click_id  = $input['click_id'];
            $phone                  = $input['phone'];
            $name                   = $input['name'];
            $order_code             = $input['order'];
            $address                = $input['address'];

            $checkAccesstrade   = AccesstradeSetting::where('campaign_id', $campaign_id)->first();
            if (empty($checkAccesstrade)) {
                throw new \Exception(Message::get("V068"));
            }

            $address            = explode(";", $address);
            $address_full       = explode(":", $address[1]);

            $address_detail     = explode(",", $address_full[1]);

            $street_address     = trim($address_detail[0]);
            $ward               = Ward::where('name', trim($address_detail[1]))->select('type', 'code', 'name')->first(); // xa,phuong
            $district           = District::where('name', trim($address_detail[2]))->select('type', 'code', 'name')->first(); // quan,huyen
            $province           = City::where('name', trim($address_detail[3]))->select('type', 'code', 'name')->first();; // tinh,thanh pho
            $country            = Country::where('name', str_replace('.', '', trim($address_detail[4])))->select('code')->first();; // quoc gia


            $wardFull       = $ward->type . ' ' . $ward->name;
            $districtFull   = $district->type . ' ' . $district->name;
            $provinceFull   = $province->type . ' ' . $province->name;


            $address_full = $street_address . ',' . $wardFull . ',' . $districtFull . ',' . $provinceFull;
            if (empty($ward) || empty($district) || empty($province) || empty($country)) {
                return $this->response->errorBadRequest('Địa chỉ không hợp lệ');
            }

            $note       = explode(":", $address[2]);
            $order      = new Order();
            $findProduct = Product::where('code', 'FNTTK71101')->first();

            if (empty($findProduct)) {
                return [];
            }

            $order = $order->create([
                'code'                              => $order_code,
                'status'                            => 'NEW',
                'status_text'                       => 'Chờ xử lý',
                'order_type'                        => 'GUEST',
                'qr_scan'                           => 0,
                'customer_name'                     => $name,
                'paid'                              => 0,
                'note'                              => trim($note[1]) ?? null,
                'phone'                             => $phone,
                'shipping_address'                  => $address_full ?? null,
                'campaign_id'                       => $campaign_id,
                'access_trade_id'                   => $checkAccesstrade->id,
                'access_trade_click_id'             => $access_trade_click_id,
                'order_source'                      => 'AT_DIMUADI',
                'original_price'                    => $findProduct->price,
                'sub_total_price'                   => $findProduct->price,
                'total_price'                       => $findProduct->price,
                'total_discount'                    => 0,
                'created_date'                      => date('Y-m-d H:i:s'),
                'street_address'                    => $street_address,
                'shipping_address_ward_code'        => $ward->code,
                'shipping_address_ward_type'        => $ward->type,
                'shipping_address_ward_name'        => $ward->name,
                'shipping_address_district_code'    => $district->code,
                'shipping_address_district_type'    => $district->type,
                'shipping_address_district_name'    => $district->name,
                'shipping_address_city_code'        => $province->code,
                'shipping_address_city_type'        => $province->type,
                'shipping_address_city_name'        => $province->name,
                'shipping_address_phone'            => $phone,
            ]);

            $orderDetail = new OrderDetail();

            $orderDetail->create([
                'order_id'          => $order->id,
                'product_id'        => $findProduct->id,
                'product_code'      => $findProduct->code,
                'product_name'      => $findProduct->name,
                'product_category'  => $findProduct->category_ids,
                'qty'               => 1,
                'price'             => $findProduct->price,
                'real_price'        => $findProduct->price,
                'price_down'        => 0,
                'total'             => $findProduct->price,
                'status'            => 'PENDING',
                'discount'          => 0,
                'is_active'         => 1,
                'total_price'       => $findProduct->price,
            ]);
        } catch (\Exception $e) {
            $log_accesstrade = LogAccesstradeOrder();
            $log_accesstrade->create([
                'order_id'      => $order->id,
                'click_id'      => $access_trade_click_id,
                'campaign_id'   => $campaign_id,
                'conversion_id' => null,
                'data'          => json_encode($e),
                'status'        => 'ERROR',
            ]);
        }
    }
}
