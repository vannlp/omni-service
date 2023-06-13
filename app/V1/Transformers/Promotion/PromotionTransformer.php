<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\V1\Transformers\Promotion;

use App\Promotion;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class PromotionTransformer extends TransformerAbstract
{
    public function transform(Promotion $promotion)
    {
//        $details = $promotion->details;
//        $promotionDetails = [];
//        foreach ($details as $detail) {
//            $promotionDetails[] = [
//                'promotion_id'             => $detail->promotion_id,
//                'product_id'               => $detail->product_id,
//                'product_name'             => object_get($detail, 'product.name'),
//                'product_code'             => object_get($detail, 'product.code'),
//                'product_description'      => object_get($detail, 'product.description'),
//                'product_price'            => object_get($detail, 'product.price'),
//                'product_thumbnail'        => object_get($detail, 'product.thumbnail'),
//                'category_id'              => $detail->category_id,
//                'category_code'            => object_get($detail, 'category.code'),
//                'category_name'            => object_get($detail, 'category.name'),
//                'category_description'     => object_get($detail, 'category.description'),
//                'gift_product_id'          => $detail->gift_product_id,
//                'gift_product_code'        => object_get($detail, 'giftProduct.code'),
//                'gift_product_name'        => object_get($detail, 'giftProduct.name'),
//                'gift_product_description' => object_get($detail, 'giftProduct.description'),
//                'discount'                 => object_get($detail, 'discount'),
//                'note'                     => object_get($detail, 'note'),
//                'customer_type'            => object_get($detail, 'customer_type'),
//                'qty'                      => $detail->qty,
//                'qty_from'                 => $detail->qty_from,
//                'qty_to'                   => $detail->qty_to,
//                'point'                    => $detail->point,
//                'price'                    => $detail->price,
//                'sale_off'                 => $detail->sale_off,
//                'qty_gift'                 => $detail->qty_gift,
//                'price_gift'               => $detail->price_gift,
//            ];
//        }
        try {
            $folder_path = object_get($promotion, 'file.folder.folder_path');
            if (!empty($folder_path)) {
                $folder_path = str_replace("/", ",", $folder_path);
            } else {
                $folder_path = "uploads";
            }

            $folder_path = url('/v0') . "/img/" . $folder_path;

            $imageUrl = object_get($promotion, 'file.file_name');
            return [
                'id'            => $promotion->id,
                'code'          => $promotion->code,
                'title'         => $promotion->title,
                'from'          => date("d-m-Y", strtotime($promotion->from)),
                'to'            => date("d-m-Y", strtotime($promotion->to)),
                'discount_rate' => $promotion->discount_rate,
                'max_discount'  => $promotion->max_discount,
                'condition_ids' => $promotion->condition_ids,
                'type'          => $promotion->type,
                'point'         => $promotion->point,
                'ranking_id'    => $promotion->ranking_id,
                'description'   => $promotion->description,
                'image_id'      => object_get($promotion, 'image_id'),
                'image_url'     => $imageUrl ? "$folder_path,$imageUrl" : null,
//                'details'     => $promotionDetails,
                'is_active'     => $promotion->is_active,
                'created_at'    => date('d-m-Y', strtotime($promotion->created_at)),
                'updated_at'    => date('d-m-Y', strtotime($promotion->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
