<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 22:10
 */

namespace App\V1\Transformers\ConfigShipping;


use App\Company;
use App\ConfigShipping;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class ConfigShippingTransformer extends TransformerAbstract
{
    public function transform(ConfigShipping $item)
    {
        try {
            $output = [
                'id' => $item->id,
                'code' => $item->code,
                'delivery_code' => $item->delivery_code ??null,
                'delivery_name' => $item->delivery_name ?? null,
                'time_from' => $item->time_from ?? null,
                'time_to' => $item->time_to ?? null,
                'time_type' => $item->time_type ??null,
                'shipping_partner_code' => $item->shipping_partner_code ?? null,
                'shipping_partner_name' => $item->shipping_partner_name ?? null,
                'shipping_fee' => $item->shipping_fee ?? null,
                'shipping_fee_formatted' => number_format($item->shipping_fee)."đ" ?? null,
                'is_active' => $item->is_active  ?? null,
                'is_active_text' =>  $item->is_active == 0 ? "Đang bật" : 'Đang tắt',
                'created_at' => $item->created_at->format('d-m-Y') ?? null,
                'created_by' => $item->created_by ?? null,
                'updated_at' => $item -> updated_at->format('d-m-Y') ?? null,
                'updated_by' => $item->updated_by ?? null
            ];

            // 
            $cs_condition_tran = [];

            foreach($item->config_shipping_conditions ?? [] as $key => $config_shipping_condition) {
                $cs_condition_tran[] = [
                    'id' => $config_shipping_condition -> id,
                    'config_shipping_id' => $config_shipping_condition->config_shipping_id ?? null,
                    'config_shipping_code' => $config_shipping_condition->config_shipping_code ?? null,
                    'condition_name' => $config_shipping_condition->condition_name ?? null,
                    'condition_type' => $config_shipping_condition->condition_type ?? null,
                    'condition_number' => $config_shipping_condition->condition_number ?? null,
                    'condition_arrays' => $config_shipping_condition->condition_arrays ? json_decode($config_shipping_condition->condition_arrays) : null,
                    // 'created_at' => $config_shipping_condition->created_at ?? null,
                    // 'created_by' => $config_shipping_condition->created_by ?? null,
                    // 'updated_at' => $config_shipping_condition->updated_at ?? null,
                    // 'updated_by' => $config_shipping_condition->updated_by ?? null
                ];
            }
            $output['shipping_conditions'] = $cs_condition_tran;


            return $output;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
