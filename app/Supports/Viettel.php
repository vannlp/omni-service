<?php
/**
 * User: dai.ho
 * Date: 25/02/2021
 * Time: 10:01 AM
 */

namespace App\Supports;

use SoapClient;

class Viettel
{
    public static function syncOrder($params)
    {
        try {
            $headers = [
                'encoding'           => 'UTF-8',
                'verifypeer'         => false,
                'verifyhost'         => false,
                'soap_version'       => SOAP_1_2,
                'trace'              => 1,
                'exceptions'         => 1,
                "connection_timeout" => 180,
                'stream_context'     => stream_context_create([
                    'ssl' => [
                        'ciphers'          => 'RC4-SHA',
                        'verify_peer'      => false,
                        'verify_peer_name' => false,
                    ],
                ]),
            ];
//            $params = json_decode(json_encode([
//                'orderNumber'       => 1,
//                'createDate'        => '23/02/2021',
//                'updateDate'        => '',
//                'shortCode'         => '10002982',
//                'customerName'      => 'a',
//                'phone'             => '1',
//                'address'           => '1',
//                'lat'               => '1',
//                'lng'               => '1',
//                'shopCode'          => 10000146,
//                'status'            => 1,
//                'deliveryDate'      => '',
//                'description'       => '',
//                'amount'            => '1',
//                'discountAmountSo'  => 1,
//                'discountPercentSo' => 1,
//                'discount'          => '',
//                'total'             => '1',
//                'totalDetail'       => 1,
//                'soOAMDetails'      => [
//                    json_decode(json_encode([
//                        'productCode'          => '401000037',
//                        'productName'          => 'vinamilk',
//                        'quantity'             => 1,
//                        'isFreeItem'           => 1,
//                        'price'                => 1,
//                        'amount'               => 1,
//                        'discountAmount'       => '',
//                        'discountPercent'      => '',
//                        'promotionProgramCode' => '',
//                        'vat'                  => 1,
//                        'lineNumber'           => 1,
//                    ])),
//                ],
//            ]));
            $client = new SoapClient(env('VIETTEL_SYNC_URL', null), $headers);
            //test
//            print_r($params);die;
//            $result = $client->__soapCall("getTestSo", ['getTestSo' => $params]);
            $result = $client->syncSo(['SoOAM' => $params]);

            $data = json_decode(json_encode($result), true);
            switch ($data['IsSuccess'] ?? null) {
                case 'F':
                    if (!empty($data['SoOAMResult'])) {
                        return [
                            'status'  => 'error',
                            'message' => $data['SoOAMResult']['errorMsg'] ?? ($data['SoOAMResult'][0]['errorMsg'] ?? "SoOAM Input has problem"),
                            'data'    => $data,
                        ];
                    }
                    return [
                        'status'  => 'error',
                        'message' => $data['ErrorResult']['errors']['message'] ?? "Something went wrong",
                        'data'    => $data,
                    ];
                case 'S':
                    return ['status' => 'success', 'message' => 'The Order has synchronized'];
                default:
                    return ['status' => 'error', 'message' => 'Order Syncing has some errors'];
            }

//            if (empty($data['SoResult']) && $data['SoResult']['cusCode'] != 'SUCCESS') {
//                return ['status' => 'error', 'message' => 'Order Syncing has some errors'];
//            }
//
//            return ['status' => 'success', 'message' => 'The Order has synchronized'];

        } catch (\Exception $ex) {
            return ['status' => 'error', 'message' => $ex->getMessage() . "-" . $ex->getLine() . "-" . $ex->getFile()];
        }
    }
}