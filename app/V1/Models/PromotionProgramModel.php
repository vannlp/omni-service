<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\Foundation\PromotionHandle;
use App\Price;
use App\Product;
use App\ProductPromotion;
use App\PromotionProgram;
use App\PromotionProgramCondition;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class PromotionProgramModel extends AbstractModel
{
    public function __construct(PromotionProgram $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            // Check total_use_customer <= total_user
            if ($input['total_use_customer'] > $input['total_user']) {
                throw new \Exception(Message::get("V013", "total_use_customer", "total_user"));
            }

            $id = !empty($input['id']) ? $input['id'] : 0;

            $actions    = !empty($input['actions']) ? $input['actions'] : [];
            $conditions = !empty($input['conditions']) ? $input['conditions'] : [];

            if (!empty($actions)) {
                foreach ($actions as $key => $action) {
                    $input[$key] = $action;
                }
            }

            if (!empty($conditions)) {
                foreach ($conditions as $key => $condition) {
                    $input[$key] = $condition;
                }
            }

            if ($id) {
                $param['id'] = $id;
                $promotion   = PromotionProgram::find($id);

                PromotionProgramCondition::model()->where('promotion_program_id', $id)->delete();

                if (empty($promotion)) {
                    throw new \Exception(Message::get("promotions.not-exist", "#$id"));
                }


                $promotion->code             = $input['code'];
                $promotion->name             = array_get($input, 'name', $promotion->name);
                $promotion->type             = array_get($input, 'type', $promotion->type);
                $promotion->thumbnail_id     = array_get($input, 'thumbnail_id', null);
                $promotion->iframe_image_id     = array_get($input, 'iframe_image_id', null);
                $promotion->description      = array_get($input, 'description', $promotion->description);
                $promotion->tags             = json_encode(array_get($input, 'tags', null));
                $promotion->store_id         = array_get($input, 'store_id', $promotion->store_id);
                $promotion->general_settings = array_get($input, 'general_settings', $promotion->general_settings);
                //$promotion->to = date("Y-m-d H:i:s", strtotime(array_get($input, 'to', $promotion->to)));
                $promotion->status                    = array_get($input, 'status', $promotion->status);
                $promotion->stack_able                = array_get($input, 'stack_able', $promotion->stack_able);
                $promotion->multiply                  = array_get($input, 'multiply', $promotion->multiply);
                $promotion->sort_order                = array_get($input, 'sort_order', $promotion->sort_order);
                $promotion->start_date                = !empty($input['start_date']) ? date(
                    "Y-m-d H:i:s",
                    strtotime($input['start_date'])
                ) : null;
                $promotion->end_date                  = !empty($input['end_date']) ? date(
                    "Y-m-d H:i:s",
                    strtotime($input['end_date'])
                ) : null;
                $promotion->total_user                = array_get($input, 'total_user', $promotion->total_user);
                $promotion->total_use_customer        = array_get(
                    $input,
                    'total_use_customer',
                    $promotion->total_use_customer
                );
                $promotion->promotion_type            = array_get($input, 'promotion_type', $promotion->promotion_type);
                $promotion->coupon_code               = array_get($input, 'coupon_code', $promotion->coupon_code);
                $promotion->need_login                = array_get($input, 'need_login', $promotion->need_login);
                $promotion->condition_combine         = array_get($input, 'condition_combine', $promotion->condition_combine);
                $promotion->condition_bool            = array_get($input, 'condition_bool', $promotion->condition_bool);
                $promotion->act_type                  = array_get($input, 'act_type', $promotion->act_type);
                $promotion->act_sale_type             = array_get($input, 'act_sale_type', $promotion->act_sale_type);
                $promotion->discount_by               = array_get($input, 'discount_by', $promotion->discount_by);
                $promotion->act_price                 = array_get($input, 'act_price', $promotion->act_price);
                $promotion->act_exchange              = !empty($input['act_exchange']) ? $input['act_exchange'] : $promotion->act_exchange;
                $promotion->act_point                 = !empty($input['act_point']) ? $input['act_point'] : $promotion->act_point;
                $promotion->default_store             = json_encode(array_get($input, 'default_store', $promotion->default_store));
                $promotion->group_customer            = json_encode(array_get(
                    $input,
                    'group_customer',
                    $promotion->group_customer
                ));
                $promotion->area                      = json_encode(array_get(
                    $input,
                    'area',
                    $promotion->area
                ));
                $promotion->area_ids                  = json_encode(array_get(
                    $input,
                    'area_ids',
                    $promotion->area_ids
                ));
                $promotion->act_not_product_condition = array_get(
                    $input,
                    'act_not_product_condition',
                    $promotion->act_not_product_condition
                );
                $promotion->act_not_special_product   = array_get(
                    $input,
                    'act_not_special_product',
                    $promotion->act_not_special_product
                );
                $promotion->act_max_quality           = array_get($input, 'act_max_quality', $promotion->act_max_quality);
                $promotion->act_not_products          = json_encode(array_get(
                    $input,
                    'act_not_products',
                    $promotion->act_not_products
                ));
                $promotion->act_categories            = json_encode(array_get(
                    $input,
                    'act_categories',
                    $promotion->act_categories
                ));
                $promotion->act_not_categories        = json_encode(array_get(
                    $input,
                    'act_not_categories',
                    $promotion->act_not_categories
                ));
                $promotion->act_products              = json_encode(array_get(
                    $input,
                    'act_products',
                    $promotion->act_products
                ));
                $promotion->act_products_gift         = json_encode(array_get(
                    $input,
                    'act_products_gift',
                    $promotion->act_products_gift
                ));
                $promotion->limit_qty_flash_sale               = array_get($input, 'limit_qty_flash_sale', null);
                $promotion->min_qty_sale              = array_get($input, 'min_qty_sale', null);
                $promotion->act_time                  = array_get($input, 'act_time', $promotion->act_time);
                $promotion->act_gift                  = array_get($input, 'act_gift', $promotion->act_gift);
                $promotion->limit_buy                 = array_get($input, 'limit_buy', null);
                $promotion->limit_price                 = array_get($input, 'limit_price', $promotion->limit_price);
                $promotion->act_quatity               = array_get($input, 'act_quatity', $promotion->act_quatity);
                $promotion->act_quatity_sale          = array_get($input, 'act_quatity_sale', $promotion->act_quatity_sale);
                $promotion->act_approval              = !empty($input['act_approval']) ? $input['act_approval'] : null;
                $promotion->is_exchange               = array_get($input, 'is_exchange', $promotion->is_exchange);
                $promotion->company_id                = TM::getCurrentCompanyId();
                $promotion->updated_at                = date('Y-m-d H:i:s', time());
                $promotion->updated_by                = TM::getCurrentUserId();
                $promotion->save();

                ProductPromotion::model()->where('promotion_id', $promotion->id)->delete();

                foreach (json_decode($promotion->act_products) as $id_product) {
                    $price_discount = 0;
                    $price_discount = $this->PriceDiscount($promotion->discount_by, $id_product->discount, $promotion->act_sale_type, $promotion->act_price);

                    if (!empty($promotion->area)) {
                        foreach (json_decode($promotion->area) as $area) {
                            if (empty($area->districts)) {
                                $product_promotions = new ProductPromotion();
                                $product_promotions->product_id = $id_product->product_id;
                                $product_promotions->promotion_id = $promotion->id;
                                $product_promotions->city_code_promotion = $area->code;
                                $product_promotions->price = $price_discount;
                                $product_promotions->save();
                            }

                            if (!empty($area->districts)) {
                                foreach ($area->districts as $district) {
                                    $product_promotions = new ProductPromotion();
                                    $product_promotions->product_id = $id_product->product_id;
                                    $product_promotions->promotion_id = $promotion->id;
                                    $product_promotions->city_code_promotion = $area->code;
                                    $product_promotions->district_code_promotion = $district->code;
                                    $product_promotions->price = $price_discount;

                                    $product_promotions->save();
                                }
                            }
                        }
                    }
                    if ($promotion->area == "[]") {
                        $product_promotions = new ProductPromotion();
                        $product_promotions->product_id = $id_product->product_id;
                        $product_promotions->promotion_id = $promotion->id;
                        $product_promotions->price = $price_discount;
                        $product_promotions->save();
                    }
                };

                if ($promotion->promotion_type == 'FLASH_SALE') {
                    foreach (json_decode($promotion->act_products) as $id_product) {
                        Product::model()->where("id", $id_product->product_id)
                            ->update([
                                'qty_flash_sale' => $id_product->qty_flash_sale
                            ]);
                    }
                    foreach (json_decode($promotion->act_categories) as $id_category) {
                        Product::model()->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%," . $id_category->category_id . ",%")
                            ->update([
                                'qty_flash_sale' => $id_category->qty_flash_sale
                            ]);
                    }
                    // foreach($data as $key => $val){
                    //     Product::model()->where('id', $val['product_id'])
                    //     ->update([
                    //         'qty_flash_sale'=>!empty($val['qty_flash_sale']) ? $val['qty_flash_sale'] : 0
                    //     ]);
                    // }
                    // foreach($qty_cat as $key => $qty){
                    //     Product::model()
                    //     ->where(function ($query) use ($category_id) {
                    //         for ($i = 0; $i < count($category_id); $i++) {
                    //             $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%," . $category_id[$i] . ",%");
                    //         }
                    //     })
                    //     ->update([
                    //         'qty_flash_sale'=>!empty($qty['qty_flash_sale']) ? $qty['qty_flash_sale'] : 0
                    //     ]);
                    // }
                }
            } else {
                // Create Promotion
                $param     = [
                    'code'                      => $input['code'],
                    'name'                      => array_get($input, 'name', ''),
                    'type'                      => array_get($input, 'type', null),
                    'thumbnail_id'              => array_get($input, 'thumbnail_id', null),
                    'iframe_image_id'           => array_get($input, 'iframe_image_id', null),
                    'description'               => array_get($input, 'description', null),
                    'tags'                      => json_encode(array_get($input, 'tags', null)),
                    'general_settings'          => array_get($input, 'general_settings', ''),
                    'status'                    => array_get($input, 'status', 0),
                    'stack_able'                => array_get($input, 'stack_able', 0),
                    'multiply'                  => array_get($input, 'multiply', 0),
                    'sort_order'                => array_get($input, 'sort_order', null),
                    'start_date'                => !empty($input['start_date']) ? date(
                        "Y-m-d H:i:s",
                        strtotime($input['start_date'])
                    ) : null,
                    'end_date'                  => !empty($input['end_date']) ? date(
                        "Y-m-d H:i:s",
                        strtotime($input['end_date'])
                    ) : null,
                    'total_user'                => array_get($input, 'total_user', null),
                    'total_use_customer'        => array_get($input, 'total_use_customer', null),
                    'promotion_type'            => array_get($input, 'promotion_type', PROMOTION_TYPE_AUTO),
                    'coupon_code'               => array_get($input, 'coupon_code', null),
                    'need_login'                => array_get($input, 'need_login', 0),
                    'condition_combine'         => array_get($input, 'condition_combine', ''),
                    'condition_bool'            => array_get($input, 'condition_bool', null),
                    'act_type'                  => array_get($input, 'act_type', null),
                    'act_sale_type'             => array_get($input, 'act_sale_type', null),
                    'discount_by'               => array_get($input, 'discount_by', null),
                    'act_price'                 => array_get($input, 'act_price', null),
                    'act_exchange'              => !empty($input['act_exchange']) ? $input['act_exchange'] : null,
                    'act_point'                 => !empty($input['act_point']) ? $input['act_point'] : null,
                    'default_store'             => json_encode(array_get($input, 'default_store', null)),
                    'group_customer'            => json_encode(array_get($input, 'group_customer', null)),
                    'area'                      => json_encode(array_get($input, 'area', null)),
                    'area_ids'                  => json_encode(array_get($input, 'area_ids', null)),
                    'act_not_product_condition' => array_get($input, 'act_not_product_condition', null),
                    'act_not_special_product'   => array_get($input, 'act_not_special_product', null),
                    'act_max_quality'           => array_get($input, 'act_max_quality', null),
                    'act_not_products'          => json_encode(array_get($input, 'act_not_products', null)),
                    'act_categories'            => json_encode(array_get($input, 'act_categories', null)),
                    'act_not_categories'        => json_encode(array_get($input, 'act_not_categories', null)),
                    'act_products'              => json_encode(array_get($input, 'act_products', null)),
                    'act_products_gift'         => json_encode(array_get($input, 'act_products_gift', null)),
                    'act_time'                  => array_get($input, 'act_time', null),
                    'act_gift'                  => json_encode(array_get($input, 'act_gift', null)),
                    'act_quatity_sale'          => array_get($input, 'act_quatity_sale', null),
                    'limit_buy'                 => array_get($input, 'limit_buy', null),
                    'limit_price'               => array_get($input, 'limit_price', null),
                    'limit_qty_flash_sale'               => array_get($input, 'limit_qty_flash_sale', null),
                    'min_qty_sale'              => array_get($input, 'min_qty_sale', null),
                    'act_quatity'               => array_get($input, 'act_quatity', null),
                    'is_exchange'               => array_get($input, 'is_exchange', "no"),
                    'act_approval'              => !empty($input['act_approval']) ? $input['act_approval'] : null,
                    'company_id'                => TM::getCurrentCompanyId(),
                ];
                $promotion = $this->create($param);

                foreach (json_decode($promotion->act_products) as $id_product) {
                    $price_discount = 0;
                    $price_discount = $this->PriceDiscount($promotion->discount_by, $id_product->discount, $promotion->act_sale_type, $promotion->act_price);

                    if (!empty($promotion->area)) {
                        foreach (json_decode($promotion->area) as $area) {
                            if (empty($area->districts)) {
                                $product_promotions = new ProductPromotion();
                                $product_promotions->product_id = $id_product->product_id;
                                $product_promotions->promotion_id = $promotion->id;
                                $product_promotions->city_code_promotion = $area->code;
                                $product_promotions->price = $price_discount;
                                $product_promotions->save();
                            }

                            if (!empty($area->districts)) {
                                foreach ($area->districts as $district) {
                                    $product_promotions = new ProductPromotion();
                                    $product_promotions->product_id = $id_product->product_id;
                                    $product_promotions->promotion_id = $promotion->id;
                                    $product_promotions->city_code_promotion = $area->code;
                                    $product_promotions->district_code_promotion = $district->code;
                                    $product_promotions->price = $price_discount;

                                    $product_promotions->save();
                                }
                            }
                        }
                    }
                    if ($promotion->area == "[]") {
                        $product_promotions = new ProductPromotion();
                        $product_promotions->product_id = $id_product->product_id;
                        $product_promotions->promotion_id = $promotion->id;
                        $product_promotions->price = $price_discount;
                        $product_promotions->save();
                    }
                };

                if ($promotion->promotion_type == 'FLASH_SALE') {
                    foreach (json_decode($promotion->act_products) as $id_product) {
                        Product::model()->where("id", $id_product->product_id)
                            ->update([
                                'qty_flash_sale' => $id_product->qty_flash_sale
                            ]);
                    };
                    foreach (json_decode($promotion->act_categories) as $id_category) {
                        if(empty($id_category->qty_flash_sale)){
                            throw new \Exception("Vui lòng nhập số lượng Stock");  
                        }
                        Product::model()->where(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%," . $id_category->category_id . ",%")
                            ->update([
                                'qty_flash_sale' => $id_category->qty_flash_sale
                            ]);
                    }
                    // foreach($data as $key => $val){
                    //     Product::model()->where('id', $val['product_id'])
                    //     ->update([
                    //         'qty_flash_sale'=>$val['qty_flash_sale']
                    //     ]);
                    // }
                    // foreach($qty_cat as $key => $qty){
                    //     Product::model()
                    //     ->where(function ($query) use ($category_id) {
                    //         for ($i = 0; $i < count($category_id); $i++) {
                    //             $query->orWhere(DB::raw("CONCAT(',',category_ids,',')"), 'like', "%," . $category_id[$i] . ",%");
                    //         }
                    //     })
                    //     ->update([
                    //         'qty_flash_sale'=>$qty['qty_flash_sale']
                    //     ]);
                    // }
                }
            }

            if (!empty($conditions['details'])) {
                $conditionModel = new PromotionProgramConditionModel();

                foreach ($conditions['details'] as $detail) {
                    $detail['promotion_program_id'] = $promotion->id;

                    $conditionModel->upsert($detail);
                }
            }
        } catch (\Exception $ex) {
            // $response = TM_Error::handle($ex);
            throw new \Exception($ex->getMessage(), $ex->getCode());
        }
        return $promotion;
    }

    private function PriceDiscount($discount_by, $discount, $act_sale_type, $act_price)
    {
        if ($discount_by == "product") {
            if ($act_sale_type == 'percentage') {
                if (!empty($discount)) {
                    return $discount;
                }
            } else {
                return $discount ?? 0;
            }
        } else {
            if ($act_sale_type == 'percentage') {
                return $act_price;
            } else {
                return $act_price ?? 0;
            }
        }
        return 0;
    }
}
