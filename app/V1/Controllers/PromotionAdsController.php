<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:28 PM
 */

namespace App\V1\Controllers;

use App\PromotionAds;
use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\V1\Models\PromotionAdsModel;
use App\V1\Transformers\PromotionAds\PromotionAdsTransformer;
// 
use App\V1\Validators\PromotionAds\PromotionAdsCreateValidator;
use App\V1\Validators\PromotionAds\PromotionAdsUpdateValidator;
// 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionAdsController extends BaseController
{

    protected $promotionAdsModel;

    public function __construct()
    {
        $this->promotionAdsModel = new PromotionAdsModel();
    }

    public function search(Request $request, PromotionAdsTransformer $transformer)
    {

        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['company_id'] = TM::getCurrentCompanyId();
        $result = $this->promotionAdsModel->search($input, [], $limit);

        return $this->response->paginator($result, $transformer);
    }

    public function view($id, PromotionAdsTransformer $transformer)
    {
        $result = PromotionAds::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
        if (!$result) {
            return ["data" => null];
        }
        return $this->response->item($result, $transformer);
    }

    public function create(Request $request, PromotionAdsCreateValidator $createValidator)
    {
        $input = $request->all();
        $createValidator->validate($input);

        try {
            DB::beginTransaction();
            $promotion = $this->promotionAdsModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R001", $promotion->title)];
    }

    public function update($id, Request $request, PromotionAdsUpdateValidator $updateValidator)
    {
        $input = $request->all();
        $input['id'] = $id;
        $updateValidator->validate($input);

        try {
            DB::beginTransaction();

            $promotion = $this->promotionAdsModel->upsert($input);

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R002", $promotion->title)];
    }

    public function delete($id)
    {
        try {
            $result = PromotionAds::find($id);
            if (empty($result)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }
            $result->delete();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("R003", $result->title)];
    }
    #################################### NO AUTHENTICATION ####################################
    public function getClientPromotionAds(Request $request, PromotionAdsTransformer $transformer)
    {
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
        $result = $this->promotionAdsModel->search($input, [], $limit);

        return $this->response->paginator($result, $transformer);
    }

    public function getClientPromotionAdsDetail($id, PromotionAdsTransformer $transformer, Request $request)
    {
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
        $result = PromotionAds::model()->where(['id' => $id, 'company_id' => $company_id])->first();
        if (!$result) {
            return ["data" => null];
        }
        return $this->response->item($result, $transformer);
    }
}
