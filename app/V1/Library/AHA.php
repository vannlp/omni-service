<?php
/**
 * User: kpistech2
 * Date: 2020-07-06
 * Time: 10:43
 */

namespace App\V1\Library;


use GuzzleHttp\Client;
use Illuminate\Http\Request;

class AHA
{
    public static final function getApiToken(Request $request)
    {
        $input = $request->all();
        try {
            $param = [
                'mobile'  => '',
                'name'    => 'Test',
                'api_key' => env("AHA_TOKEN")
            ];
            $client = new Client();
            $response = $client->get(env("AHA_END_POINT") . "v1/partner/register_account", ['query' => $param]);
            $response = $response->getBody()->getContents() ?? null;
            $response = !empty($response) ? json_decode($response, true) : [];

            if (empty($response['success'])) {
                throw new \Exception($response['message'] ?? "Some thing went Wrong!");
            }
        } catch (\Exception $exception) {
            return ['status' => 'error', 'success' => false, 'message' => $exception->getMessage()];
        }

        return ['status' => 'success', 'success' => true, "data" => $response['order']];
    }
}