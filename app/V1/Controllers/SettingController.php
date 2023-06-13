<?php

/**
 * User: Ho Sy Dai
 * Date: 9/28/2018
 * Time: 10:10 AM
 */

namespace App\V1\Controllers;


use App\City;
use App\CityHasRegion;
use App\Country;
use App\Region;
use App\Setting;
use App\Store;
use App\File;
use App\Supports\DataUser;
use App\Foundation\PromotionHandle;
use App\V1\Models\ProductAttributeModel;
use Illuminate\Support\Arr;
use App\Product;
use App\OrderDetail;
use App\Category;
use App\ProductComment;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\PromotionProgram;
use App\V1\Models\CityModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\SettingModel;
use App\V1\Models\WardModel;
use App\V1\Transformers\Setting\CityTransformer;
use App\V1\Transformers\Setting\DistrictTransformer;
use App\V1\Transformers\Setting\SettingClientTransformer;
use App\V1\Transformers\Product\ProductDetailClientTransformer;
use App\V1\Transformers\Setting\SettingTransformer;
use App\V1\Transformers\Setting\SettingDataStringTransformer;
use App\V1\Transformers\Setting\WardTransformer;
use App\V1\Validators\Setting\SettingCreateValidator;
use App\V1\Validators\Setting\SettingUpdateValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SettingController extends BaseController
{
    protected $cityModel;
    protected $districtModel;
    protected $wardModel;
    protected $model;
    protected $systemKey = "SYSTEM-SETTING";

    /**
     * Create a new controller instance.
     * @return void
     */
    public function __construct()
    {
        $this->cityModel = new CityModel();
        $this->districtModel = new DistrictModel();
        $this->wardModel = new WardModel();
        $this->model = new SettingModel();
    }

    /**
     * @return array
     */
    public function index()
    {
        return ['status' => '0k'];
    }

    /**
     * All countries
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCountries()
    {
        $countries = Country::all();
        return response()->json($countries);
    }

    /**
     * @param $country_id
     * @param CityTransformer $cityTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function getCity($country_id, Request $request, CityTransformer $cityTransformer)
    {
        $input = $request->all();
        $input['is_active'] = 1;
        $input['country_id'] = $country_id;
        $input['sort']['name'] = "asc";
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        try {
            $cities = $this->cityModel->search($input, ['country']);
            Log::view($this->cityModel->getTable());
            if($this->chkCache($country_id)){
                $cities = $this->getCache($country_id);
            } else {
                $this->setCache($country_id, $cities, 86400);
                $cities = $this->getCache($country_id);
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->collection($cities, $cityTransformer)->header('Cache-Control', 'max-age=86400');
    }

    public function updateCity($city_code, Request $request)
    {
        $input = $request->all();
        if (empty($input['region_code'])) {
            throw new \Exception(Message::get("V065"));
        }
        $region = Region::model()->where('code', $input['region_code'])->where('company_id', TM::getCurrentCompanyId())->first();
        if (empty($region)) {
            return $this->response->errorBadRequest(Message::get('V003', Message::get('citis')));
        }
        $city = CityHasRegion::model()
            ->where('code_city', $city_code)
            ->where('company_id', TM::getCurrentCompanyId())
            ->where('store_id', TM::getCurrentStoreId())->first();
        try {
            DB::beginTransaction();
            if (!empty($city)) {
                $city->code_region = $input['region_code'];
                $city->name_region = $input['region_name'];
                $city->save();
            }
            if (empty($city)) {
                CityHasRegion::create([
                    'code_city'   => $city_code,
                    'code_region' => $input['region_code'],
                    'name_region' => $input['region_name'],
                    'company_id'  => TM::getCurrentCompanyId(),
                    'store_id'    => TM::getCurrentStoreId(),
                ]);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("city.update-succes", $city_code)];
    }

    /**
     * @param $city_code
     * @param DistrictTransformer $districtTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function getDistrict($city_code, Request $request, DistrictTransformer $districtTransformer)
    {
        $input = $request->all();
        $input['is_active'] = 1;
        $input['city_code'] = $city_code;
        $input['sort']['full_name'] = "asc";
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        try {
            $districts = $this->districtModel->search($input, ['city', 'city.country']);
            Log::view($this->districtModel->getTable());

            if($this->chkCache($city_code)){
                $districts = $this->getCache($city_code);
            } else {
                $this->setCache($city_code, $districts, 86400);
                $districts = $this->getCache($city_code);
            }

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->collection($districts, $districtTransformer)->header('Cache-Control', 'max-age=86400');
    }

    /**
     * @param $district_code
     * @param WardTransformer $wardTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function getWard($district_code, Request $request, WardTransformer $wardTransformer)
    {
        $input = $request->all();
        $input['is_active'] = 1;
        $input['district_code'] = $district_code;
        $input['sort']['full_name'] = "asc";
        if (!empty($input['name'])) {
            $input['name'] = ['like' => "%{$input['name']}%"];
        }
        try {
            $wards = $this->wardModel->search($input, ['district', 'district.city', 'district.city.country']);
            Log::view($this->wardModel->getTable());

            if($this->chkCache($district_code)){
                $wards = $this->getCache($district_code);
            } else {
                $this->setCache($district_code, $wards, 86400);
                $wards = $this->getCache($district_code);
            }

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->collection($wards, $wardTransformer)->header('Cache-Control', 'max-age=86400');
    }

    public function search(Request $request, SettingTransformer $settingTransformer)
    {
        $input = $request->all();

        try {
            if (!empty($input['type'])) {
                $input['type'] = ['=' => $input['type']];
            } else {
                $input['type'] = ['=' => null];
            }


            $items = $this->model->search($input, [], array_get($input, 'limit', 20));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        return $this->response->paginator($items, $settingTransformer);
    }

    /**
     * @param $id
     * @param SettingTransformer $settingTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function detail($code, SettingTransformer $settingTransformer)
    {
        try {
            $item = $this->model->getFirstWhere(['code' => $code, 'company_id' => TM::getCurrentCompanyId()]);
            if (empty($item)) {
                $item = collect([]);
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return $this->response->item($item, $settingTransformer);
    }

    /**
     * @param Request $request
     * @param SettingCreateValidator $settingCreateValidator
     * @param SettingTransformer $settingTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function store(
        Request                $request,
        SettingCreateValidator $settingCreateValidator,
        SettingTransformer     $settingTransformer
    ) {
        $input = $request->all();
        $settingCreateValidator->validate($input);

        try {
            DB::beginTransaction();
            $item = $this->model->upsert($input);
            Log::view($this->model->getTable());
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($item, $settingTransformer);
    }

    /**
     * @param $code
     * @param Request $request
     * @param SettingUpdateValidator $settingUpdateValidator
     * @param SettingTransformer $settingTransformer
     * @return \Dingo\Api\Http\Response|void
     */
    public function update(
        $code,
        Request $request,
        SettingUpdateValidator $settingUpdateValidator,
        SettingTransformer $settingTransformer
    ) {
        $input = $request->all();
        $settingUpdateValidator->validate($input);
        try {
            DB::beginTransaction();
            $input['code'] = $code;
            $item = $this->model->upsert($input, $code);
            Log::update($this->model->getTable(), "#Code:" . $code, null, $item->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($item, $settingTransformer);
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($code)
    {
        try {
            DB::beginTransaction();
            $item = Setting::model()->where('code', $code)->where('store_id', TM::getCurrentStoreId())->first();
            if (empty($item)) {
                return $this->response->errorBadRequest(Message::get("V003", "#$code"));
            }
            // 1. Delete
            $item->delete();
            Log::delete($this->model->getTable(), "#" . $item->code . "-" . $item->name);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => 'OK', 'message' => "Delete Successful"];
    }

    ########################################### NOT AUTHENTICATION ############################################

    public function getClientSetting(Request $request, SettingClientTransformer $settingClientTransformer)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
            $group_id = TM::getCurrentGroupId();
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
                }
            }
        }

        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['store_id'] = $store_id;
        $input['publish'] = 1;
        $items = $this->model->search($input, [], $limit);

        return $this->response->paginator($items, $settingClientTransformer);
    }

    public function returnProduct($id)
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
        $group_id = TM::getCurrentGroupId();
        $product = Product::model()
            //            ->withCount('favorites')
            ->where('id', $id)
            ->whereHas('stores', function ($query) use ($store_id) {
                $query->where('store_id', $store_id);
            });
        if (!empty($group_id)) {
            $product->where(DB::raw("CONCAT(',',group_code_ids,',')"), 'like', "%,$group_id,%")->orWhere('id', $id)->where('store_id', $store_id);
        }
        $product = $product->with(['priceDetail', 'stores:id,name', 'storeOrigin:id,name', 'shop:id,name', 'brand:id,name'])->first();
        if (empty($product)) {
            return null;
        }
        $promotionPrograms = (new PromotionHandle())->getPromotionByActType(
            PromotionHandle::TYPE_USING_PRODUCT,
            $company_id
        );
        $product_gift = [];
        $iframe_image_id = null;
        $iframe_image = null;
        $PromotionsGiftAndIframe = (new PromotionProgram())->PromotionsGiftAndIframe($product); 
        
        $price = Arr::get($product->priceDetail($product), 'price', $product->price);

        if ($price < $product->price) {
            $percent_price = $product->price - $price;
            $percentage_price_old = round(($percent_price / $product->price) * 100);
        }

        $special = null;
        $special_formated = null;
        $special_percentage = 0;

        $promotionPrograms = (new PromotionHandle())->promotionApplyProduct($promotionPrograms, $product);
        $promotionPrice = 0;
        if (!empty($promotionPrograms) && !$promotionPrograms->isEmpty()) {
            foreach ($promotionPrograms as $promotion) {

                $prod = array_pluck($promotion->productPromotion, 'product_id');
                $search_prod = array_search($product->id, $prod);
                
                if (is_numeric($search_prod)) {
                    $iframe_image_id = $promotion->iframe_image_id;
                    $iframe_image = $promotion->iframeImage->code ?? null;
                }


                $promotionPrice += (new PromotionProgramController())->promotionPrice($promotion->productPromotion, $product->id,  $price, $promotion->discount_by, $promotion->act_sale_type, $promotion->act_price);
                // if ($promotion->discount_by == "product") {
                //     $promotionPrice += (new PromotionHandle())->parsePriceByProducts($product->code, $price, $promotion);
                // } else {
                //     $promotionPrice += (new PromotionHandle())->parsePriceBySaleType($price, $promotion);
                // }
            }
            $special = $price - $promotionPrice;
            $special_formated = number_format($special) . "đ";
            if ($special) {
                $special_percentage = $price != 0 || !empty($price) ? round(($promotionPrice / $price) * 100) : 0;
//                $special_percentage = round(($promotionPrice / $price) * 100);
            }
        }
        setlocale(LC_MONETARY, 'vi_VN');
        $fileCode = object_get($product, 'file.code', null);
        $output = [
            'id'                           => $product->id,
            'code'                         => $product->code,
            'name'                         => $product->name,
            'slug'                         => $product->slug,
            'url'                          => env('APP_URL') . "/product/{$product->slug}",
            'star'                         => [
                'total_rate' => [
                    'total' => $product->count_rate,
                ],
                'avg_star'   => [
                    'avg'        => $avg = $product->rate_avg,
                    'avg_format' => ($avg ?? "0") . "/5",
                ]
            ],
            'star_rating'                  => 0,
            'thumbnail'                    => !empty($fileCode) ? env('UPLOAD_URL') . '/file/' . $fileCode : null,
            'iframe_image_id'              => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? $PromotionsGiftAndIframe['iframe_image_id'] : $iframe_image_id,
            'iframe_image'                 => !empty($PromotionsGiftAndIframe['iframe_image_id']) ? env('GET_FILE_URL') . $PromotionsGiftAndIframe['iframe_image'] : (!empty($iframe_image) ? env('GET_FILE_URL') . $iframe_image : null),
            'price'                        => $price,
            'price_formatted'              => number_format($price) . "đ",
            'original_price'               => $price,
            'original_price_formatted'     => number_format($price) . "đ",
            'old_product_price'            => $product->price == $price ? 0 :  $product->price,
            'old_product_price_formatted'  => number_format($product->price) . "đ",
            'percentage_price_old'         => ($percentage_price_old ?? 0) . "%",
            'promotion_price'              => $promotionPrice,
            'promotion_price_formatted'    => number_format($promotionPrice) . "đ",
            'special'                      => $special,
            'special_formatted'            => $special_formated,
            'special_percentage'           => $special_percentage,
            'special_percentage_formatted' => $special_percentage . "%",
            'qty'                          => Arr::get($product->warehouse, 'quantity', 0),
            'sold_count'                   => $sold_count = $product->sold_count ?? 0,
            'sold_count_formatted'         => format_number_in_k_notation($sold_count),
            'product_gift'                 => $PromotionsGiftAndIframe['product_gift'] ?? [],
        ];
        return $output;
    }

    public function viewClientSetting($code, Request $request, SettingTransformer $settingTransformer, SettingClientTransformer $settingClientTransformer)
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
        $input = $request->all();
        try {
            $item = $this->model->getFirstWhere(['code' => $code, 'company_id' => $company_id]);
            if (empty($item)) {
                return ['data' => []];
            }
           
            // Log::view($this->model->getTable());
            //            if ($code == 'CONFIG-BRAND') {
            //                $products = [];
            //                $sections = [];
            //                foreach (json_decode($item->data) as $sec) {
            //                    foreach ($sec->products as $key => $prod) {
            //                        $b = $this->returnProduct($prod->id);
            //
            //                        if (!empty($b)) {
            //                            array_push($products, $b);
            //                        }
            //                    }
            //                    $sec->products = $products;
            //                    $products = [];
            //                    array_push($sections, $sec);
            //                }
            //                $item->data = json_encode($sections);
            //            }
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        if($this->chkCache($code)){
            $item = $this->getCache($code);
        } else {
            $this->setCache($code, $item, 300);
            $item = $this->getCache($code);
        }
        
        $sysConfig = DB::table('system_configs')->where('code', 'CONFIG-BRAND-CACHE')->value('value');

        if (!empty($input['is_client']) && $input['is_client'] == 1) {
            if($sysConfig){
                return $this->response->item($item, $settingClientTransformer)->header('Cache-Control', 'max-age=300');
            }
            return $this->response->item($item, $settingClientTransformer)->header('Cache-Control', 'no-cache, max-age=0');
        }
        if($sysConfig){
            return $this->response->item($item, $settingTransformer)->header('Cache-Control', 'max-age=300');
        }
        return $this->response->item($item, $settingTransformer)->header('Cache-Control', 'no-cache, max-age=0');
    }

    public function viewClientSettingBySlug($slug, Request $request, SettingTransformer $settingTransformer, SettingClientTransformer $settingClientTransformer)
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
        try {
            $item = $this->model->getFirstWhere(['slug' => $slug, 'company_id' => $company_id]);
            if (empty($item)) {
                return ['data' => null];
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        if ($item->code == 'CONFIG-BRAND') {
            $products = [];
            $sections = [];
            foreach (json_decode($item->data) as $sec) {
                foreach ($sec->products as $prod) {
                    $b = $this->returnProduct($prod->id);
                    array_push($products, $b);
                }
                $sec->products = $products;
                $products = [];
                array_push($sections, $sec);
            }
            $item->data = json_encode($sections);
        }
        if($this->chkCache($slug)){
            $item = $this->getCache($slug);
        } else {
            $this->setCache($slug, $item, 300);
            $item = $this->getCache($slug);
        }
        
        $sysConfig = DB::table('system_configs')->where('code', 'CONFIG-BRAND-CACHE')->value('value');

        if (!empty($input['is_client']) && $input['is_client'] == 1) {
            if($sysConfig){
                return $this->response->item($item, $settingClientTransformer)->header('Cache-Control', 'max-age=300');
            }
            return $this->response->item($item, $settingClientTransformer)->header('Cache-Control', 'no-cache, max-age=0');
        }
        if($sysConfig){
            return $this->response->item($item, $settingTransformer)->header('Cache-Control', 'max-age=300');
        }
        return $this->response->item($item, $settingTransformer)->header('Cache-Control', 'no-cache, max-age=0');
        // if (isset($input['is_client']) && $input['is_client'] == 1) {
        //     return $this->response->item($item, $settingClientTransformer);
        // }
        // return $this->response->item($item, $settingTransformer);



        // $input = [
        //     'code'       => $code,
        //     'store_id'   => $store_id,
        //     'company_id' => $company_id,
        //     'publish'    => 1
        // ];

        // $setting = Setting::model()->where($input)->first();
        // if (!$setting) {
        //     return response()->json(['data' => []]);
        // }
        // return response()->json(['data' => json_decode($setting->data, true)]);
    }

    public function viewClientSettingBySlugDataString($slug, Request $request, SettingDataStringTransformer $settingTransformer, SettingClientTransformer $settingClientTransformer)
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
        try {
            $item = $this->model->getFirstWhere(['slug' => $slug, 'company_id' => $company_id]);
            if (empty($item)) {
                return ['data' => null];
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }
        if ($item->code == 'CONFIG-BRAND') {
            $products = [];
            $sections = [];
            foreach (json_decode($item->data) as $sec) {
                foreach ($sec->products as $prod) {
                    $b = $this->returnProduct($prod->id);
                    array_push($products, $b);
                }
                $sec->products = $products;
                $products = [];
                array_push($sections, $sec);
            }
            $item->data = json_encode($sections);
        }
        if (isset($input['is_client']) && $input['is_client'] == 1) {
            return $this->response->item($item, $settingClientTransformer);
        }
        return $this->response->item($item, $settingTransformer);
        // $input = [
        //     'code'       => $code,
        //     'store_id'   => $store_id,
        //     'company_id' => $company_id,
        //     'publish'    => 1
        // ];

        // $setting = Setting::model()->where($input)->first();
        // if (!$setting) {
        //     return response()->json(['data' => []]);
        // }
        // return response()->json(['data' => json_decode($setting->data, true)]);
    }

    //    private function count_star($product_id, $star)
    //    {
    //        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
    //        $data = ProductComment::model()
    //            ->where('store_id', $store_id)
    //            ->where('company_id', $company_id)
    //            ->where('type', PRODUCT_COMMENT_TYPE_RATE)
    //            ->where('product_id', $product_id);
    //        if (!empty($star)) {
    //            $data = $data->where('rate', $star);
    //        }
    //        $data = $data->select('rate')->get()->toArray();
    //        return (int)count($data);
    //    }
    //
    //    public function getStarRate($id)
    //    {
    //        $star_1 = $this->count_star($id, 1);
    //        $star_2 = $this->count_star($id, 2);
    //        $star_3 = $this->count_star($id, 3);
    //        $star_4 = $this->count_star($id, 4);
    //        $star_5 = $this->count_star($id, 5);
    //        $total = $this->count_star($id, null);
    //
    //        $result['total_rate'] = [
    //            'total' => $total,
    //        ];
    //        $start = $star_1 + $star_2 + $star_3 + $star_4 + $star_5;
    //        $result['avg_star'] = [
    //            'avg'        => $start > 0 ? $avg = round(($star_1 * 1 + $star_2 * 2 + $star_3 * 3 + $star_4 * 4 + $star_5 * 5) / $start, 2) : 0,
    //            'avg_format' => $avg ?? "0" . "/5",
    //        ];
    //        return $result;
    //    }
    //
    //    private function stringToImage($ids)
    //    {
    //        if (empty($ids)) {
    //            return [];
    //        }
    //        $result = [];
    //        $images = File::model()->whereIn('id', explode(",", $ids))->get();
    //        foreach ($images as $key => $image) {
    //            $result[$key]['id'] = $image->id;
    //            $result[$key]['url'] = env('GET_FILE_URL') . $image->code;
    //        }
    //        return $result;
    //    }
    //
    //    private function getNameCategory($ids)
    //    {
    //        if (empty($ids)) {
    //            return [];
    //        }
    //        $category = Category::model()->select(['name'])->whereIn('id', explode(",", $ids))->get()->toArray();
    //        $category = array_pluck($category, 'name');
    //        $category = implode(', ', $category);
    //        return $category;
    //    }

    public function getDataFirst()
    {
        list($store_id, $company_id) = DataUser::getInstance()->info();
        $item = $this->model->getFirstWhere(['code' => 'CONFIG-BRAND', 'company_id' => $company_id]);
        if (empty($item)) {
            return ['data' => []];
        }
        return ['data' => array(['data_first' => json_decode($item->data_first, true)])];
    }

    public function clearAllCacheRedis()
    {
        $this->clearAllCache();
        return $this->response->array(['message' => 'Clear all cache success']);
    }

    public function returnInfoVpbank()
    {
        $data = [
            'version'     => env('VPBANK_VERSION'),
            'mid'         => env('VPBANK_MERCHANT'),
            'api_url'     => env('API_VPBANK'),
            'form_url'    => env('FORM_URL')
        ];
        return response()->json(['data' => $data]);
    }
}
