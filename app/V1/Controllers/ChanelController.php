<?php
/**
 * User: kpistech2
 * Date: 2020-05-29
 * Time: 00:14
 */

namespace App\V1\Controllers;


use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChanelController extends BaseController
{
    protected $allChanels = [
        "ZAL" => [
            "omni_chanel_code"        => "ZAL",
            "omni_chanel_name"        => "Zalo",
            "omni_chanel_description" => "Kênh bán hàng trên Zalo shop, yêu cầu bạn phải có 1 cửa hàng trên Zalo để kết nối",
        ],
        "SHO" => [
            "omni_chanel_code"        => "SHO",
            "omni_chanel_name"        => "Shopee",
            "omni_chanel_description" => "Kênh bán hàng trên Shopee, yêu cầu bạn phải có gian hàng bán hàng trên Shopee để kết nối",
        ],
        "LAZ" => [
            "omni_chanel_code"        => "LAZ",
            "omni_chanel_name"        => "Lazada",
            "omni_chanel_description" => "Kênh bán hàng trên Lazada, yêu cầu bạn phải có tài khoản người bán hàng trên Lazada để kết nối",
        ],
        "TIK" => [
            "omni_chanel_code"        => "TIK",
            "omni_chanel_name"        => "Tiki",
            "omni_chanel_description" => "Kênh bán hàng trên Tiki, yêu cầu bạn phải có tài khoản người bán hàng trên Tiki để kết nối",
        ],
    ];

    public function __construct()
    {

    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $data = array_values($this->allChanels);

        return response()->json(['data' => $data]);
    }

    public function activeChanel($store_id, Request $request)
    {
        $input = $request->all();

        try {
            DB::beginTransaction();
            $store = Store::model()->where('id', $store_id)->first();
            if (empty($store)) {
                return $this->responseError(Message::get("V002", Message::get("stores")));
            }

            if (!in_array($input['omni_chanel_code'], array_column($this->allChanels, 'omni_chanel_code'))) {
                return $this->responseError(Message::get("V002", Message::get("chanel")));
            }

            $chanels = !empty($store->chanels) ? json_decode($store->chanels, true) : [];
            $new_chanels = [];
            foreach ($chanels as $chanel) {
                $new_chanels[$chanel['omni_chanel_code']] = $chanel;
            }

            $new_chanels[$input['omni_chanel_code']] = [
                'omni_chanel_code' => $input['omni_chanel_code'],
                'active'           => $input['active'] == 1 ? 1 : 0,
            ];

            $store->chanels = json_encode(array_values($new_chanels));
            $store->save();
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("stores.update-success", $store->name)];
    }

    public function listActiveChanel($store_id)
    {
        $store = Store::model()->where('id', $store_id)->first();
        if (empty($store)) {
            return $this->responseError(Message::get("V002", Message::get("stores")));
        }

        $chanels = !empty($store->chanels) ? json_decode($store->chanels, true) : [];
        $new_chanels = [];
        foreach ($chanels as $chanel) {
            if ($chanel['active'] == 1) {
                $new_chanels[] = [
                    'omni_chanel_code' => $chanel['omni_chanel_code'],
                    'omni_chanel_name' => $this->allChanels[$chanel['omni_chanel_code']]['omni_chanel_name'] ?? null,
                ];
            }
        }

        return response()->json(['data' => $new_chanels]);
    }
}