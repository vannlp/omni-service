<?php


namespace App\V1\Transformers\Cart;


use App\Cart;
use App\CartDetail;
use App\Category;
use App\Setting;
use App\Order;
use App\Product;
use App\PromotionProgram;
use App\PromotionTotal;
use App\TM;
use App\User;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class CartTransformer extends TransformerAbstract
{
    public function transform(Cart $cart)
    {
        try {
            $details            = [];
            $special_percentage = 0;
            $total_product_promotion = 0;
            $total_product = 0;
            $is_nutizen = 0;
            $is_nutifood = 0;
            $date = date('Y-m-d H:i:s', time());

            $categoryNutizen = Category::model()->where(['category_publish' => 1, 'is_nutizen' => 1])->get()->pluck('id')->toArray();

            foreach ($cart->details as $key => $detail) {
                $detail->special_percentage = 0;
                $special_percentage = null;
                $fileCode        = object_get($detail, 'file.code');
                $price           = $detail->price;
                $special         = !empty($detail->promotion_price) ? ($price * $detail->quantity) - ($detail->promotion_price * $detail->quantity) : 0;
                $promotion_price = $detail->promotion_price ?? 0;
                $total           = $detail->total;
                if (isset($special)) {
                    $special_percentage = ($promotion_price / $price) * 100;
                    $detail->special_percentage = $special_percentage ?? 0;
                    $detail->save();
                }
                $category_ids = explode(',', $detail->getProduct->category_ids);
                ///check NutiStore
                $checkNutiStore = $this->checkProductStore($categoryNutizen, $category_ids);
                if ($is_nutizen != 1) {
                    $is_nutizen = $checkNutiStore['is_nutizen'];
                }
                if ($is_nutifood != 1) {
                    $is_nutifood = $checkNutiStore['is_nutifood'];
                }
                $item_gift = json_decode($detail->getProduct->gift_item);
                if (empty($item_gift)) {
                    $category = Category::model()->whereIn('id', $category_ids)->get();
                    foreach ($category as $value) {
                        if ($value->gift_item) {
                            foreach (json_decode($value->gift_item) as $key => $value) {
                                $item_gift[] = $value;
                            }
                        }
                    }
                }

                if ($detail->promotion_price > 0 || !empty($detail->promotion_check)) {
                    $total_product_promotion += !empty($detail->promotion_price) ? ($price - $promotion_price == 0 ? $detail->promotion_price : $price - $promotion_price) * ($detail->quantity) : $detail->price * ($detail->quantity);
                } else {
                    $total_product += ($detail->price * ($detail->quantity));
                }

                $flash_sale = 0;
                $limit_qty_flash_sale = null;
                $min_qty_flash_sale = null;
                $promotion_flashsale = PromotionProgram::model()->where('promotion_type', 'FLASH_SALE')
                ->where('start_date', "<=", $date)->where('end_date', '>=', $date)
                ->where('status', 1)->where('deleted', 0)->where('company_id', TM::getCurrentCompanyId())->get();
                if (!empty($promotion_flashsale)) {
                    foreach ($promotion_flashsale as $flashsale) {
                        if ($flashsale->act_type == 'sale_off_on_products') {
                            if (!empty($flashsale->act_products) && $flashsale->act_products != "[]") {
                                $prod_promo = json_decode($flashsale->act_products);
                                $act_products = array_pluck(json_decode($flashsale->act_products), 'product_code');
                                $check_prod = array_search($detail->product_code, $act_products);
                                if(is_numeric($check_prod)){
                                    $flash_sale = 1;
                                    if (empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale <= 0 && !empty($prod_promo[$check_prod]->limit_qty_flash_sale) && $prod_promo[$check_prod]->limit_qty_flash_sale > 0) {
                                        if ($detail->quantity > $prod_promo[$check_prod]->limit_qty_flash_sale) {
                                            $limit_qty_flash_sale = $prod_promo[$check_prod]->limit_qty_flash_sale ?? null;
                                        }
                                    }
                                    if (empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale <= 0 && !empty($prod_promo[$check_prod]->min_qty_sale) && $prod_promo[$check_prod]->min_qty_sale > 0) {
                                        if ($detail->quantity < $prod_promo[$check_prod]->min_qty_sale) {
                                            $min_qty_flash_sale = $prod_promo[$check_prod]->min_qty_sale ?? null;
                                        }
                                    }
                                }
                            }
                        }
                        if ($flashsale->act_type == 'sale_off_on_categories') {
                            if (!empty($flashsale->act_categories) && $flashsale->act_categories != "[]") {
                                $Category = !empty($flashsale->act_categories) ? array_pluck(json_decode($flashsale->act_categories), 'category_id') : [];
                                foreach (json_decode($flashsale->act_categories) as $act_category) {
                                    $check = array_intersect($Category, explode(',', $detail->product_category));
                                    if (!empty($check)) {
                                        $flash_sale = 1;
                                        if (empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale <= 0 && !empty($act_category->limit_qty_flash_sale) && $act_category->limit_qty_flash_sale > 0) {
                                            if ($detail->quantity > $act_category->limit_qty_flash_sale) {
                                                $limit_qty_flash_sale = $act_category->limit_qty_flash_sale ?? null;
                                            }
                                        }
                                        if (empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale <= 0 && !empty($act_category->min_qty_sale) && $act_category->min_qty_sale > 0) {
                                            if ($detail->quantity < $act_category->min_qty_sale) {
                                                $min_qty_flash_sale = $act_category->min_qty_sale ?? null;
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                $limit_qty_sale = null;
                $min_qty_sale = null;
                $sales = PromotionProgram::model()->whereIn('promotion_type', ['AUTO', 'DISCOUNT'])
                ->where('start_date', "<=", $date)->where('end_date', '>=', $date)
                ->where('status', 1)->where('deleted', 0)->where('company_id', TM::getCurrentCompanyId())->get();
                if (!empty($sales)) {
                    foreach ($sales as $sale) {
                        foreach ($sale->conditions as $condition) {
                            if ($condition->condition_name == "product_group") {
                                if ($sale->act_type == 'sale_off_on_products' && !empty($sale->act_products) && $sale->act_products != "[]") {
                                    $prod_promo = json_decode($sale->act_products);
                                    $act_products = array_pluck(json_decode($sale->act_products), 'product_code');
                                    $check_prod = array_search($detail->product_code, $act_products);
                                    if(is_numeric($check_prod)){
                                        if (empty($sale->limit_qty_flash_sale) && $sale->limit_qty_flash_sale <= 0 && !empty($prod_promo[$check_prod]->limit_qty_flash_sale) && $prod_promo[$check_prod]->limit_qty_flash_sale > 0) {
                                            if ($detail->quantity > $prod_promo[$check_prod]->limit_qty_flash_sale) {
                                                $limit_qty_sale = $prod_promo[$check_prod]->limit_qty_flash_sale ?? null;
                                            }
                                        }
                                        if (empty($sale->min_qty_sale) && $sale->min_qty_sale <= 0 && !empty($prod_promo[$check_prod]->min_qty_sale) && $prod_promo[$check_prod]->min_qty_sale > 0) {
                                            if ($detail->quantity < $prod_promo[$check_prod]->min_qty_sale) {
                                                $min_qty_sale = $prod_promo[$check_prod]->min_qty_sale ?? null;
                                            }
                                        }

                                    }
                                }
                                if ($sale->act_type == 'sale_off_on_categories' && !empty($sale->act_categories) && $sale->act_categories != "[]") {
                                    $Category = !empty($sale->act_categories) ? array_pluck(json_decode($sale->act_categories), 'category_id') : [];
                                    foreach (json_decode($sale->act_categories) as $act_category) {
                                        $check = array_intersect($Category, explode(',', $detail->product_category));
                                        if (!empty($check)) {
                                            if (empty($sale->limit_qty_flash_sale) && $sale->limit_qty_flash_sale <= 0 && !empty($act_category->limit_qty_flash_sale) && $act_category->limit_qty_flash_sale > 0) {
                                                if ($detail->quantity > $act_category->limit_qty_flash_sale) {
                                                    $limit_qty_sale = $act_category->limit_qty_flash_sale ?? null;
                                                }
                                            }
                                            if (empty($sale->min_qty_sale) && $sale->min_qty_sale <= 0 && !empty($act_category->min_qty_sale) && $act_category->min_qty_sale > 0) {
                                                if ($detail->quantity < $act_category->min_qty_sale) {
                                                    $min_qty_sale = $act_category->min_qty_sale ?? null;
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }

                                break;
                            }
                        }
                    }
                }

                $total_info = $cart->total_info?? null;
                $promotion_codes = [];
                $discount = 0;

                if($total_info){
                    foreach($total_info as $ti){
                        if(!empty($ti['type'])){
                            if($ti['type']  == 'promotion_by_product') $promotion_codes[] = $ti['code'];
                        }
                    }
                    foreach($promotion_codes as $pc) {
                        $promtion = PromotionProgram::where('code', $pc)->first();
                        $act_products = json_decode($promtion->act_products, true) ?? [];
                        if(count($act_products) > 0) {
                            foreach($act_products as $ap) {
                                if($ap['product_id'] == $detail->product_id){
                                    $discount_js = $ap['discount'];
                                    $price_down = (int) $ap['price'] * ( (int) $discount_js / 100);
                                    $discount += $price_down ;
                                }
                            }
                        }
                    }
                }

                $infoAddress = $cart->cart_info ?? null;
                $weight_converts = ['GRAM' => 0.001, 'KG' => 1];
                $details[] = [
                    'discount'                     => $discount,
                    'id'                           => $detail->id,
                    'cart_id'                      => $detail->cart_id,
                    'weight'                       => !empty($detail->getProduct->weight) ? round($detail->getProduct->weight * $weight_converts[$detail->getProduct->weight_class ?? "KG"], 1) : 0,
                    'product_id'                   => $detail->product_id,
                    'product_code'                 => $detail->product_code,
                    'product_name'                 => $detail->product_name,
                    'qty_sale_re'                  => $detail->qty_sale_re ?? null,
                    'qty_not_sale'                 => $detail->qty_not_sale ?? null,
                    'product_slug'                 => Arr::get($detail, 'getProduct.slug', null),
                    'gift_item'                    => !empty($item_gift) ? array_unique($item_gift, SORT_REGULAR) : [],
                    'product_thumb'                => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                    'quantity'                     => $detail->quantity,
                    'coupon_apply'                 => $detail->coupon_apply ?? null,
                    'unit'                         => Arr::get($detail, 'getProduct.unit.name', null),
                    'price'                        => round($price),
                    'price_formatted'              => number_format(round($price)) . "đ",
                    'old_product_price'            => round($detail->old_product_price),
                    'old_product_price_formatted'  => number_format(round($detail->old_product_price)) . "đ",
                    'special'                      => !empty($promotion_price) ? $special : 0,
                    'special_formatted'            => !empty($promotion_price) ? number_format($special) . "đ" : 0 . "đ",
                    'limit_qty_sale'               => !empty($limit_qty_sale) ? (int)$limit_qty_sale : null,
                    'min_qty_sale'                 => !empty($min_qty_sale) ? (int)$min_qty_sale : null,
                    'limit_qty_flash_sale'         => !empty($limit_qty_flash_sale) ? (int)$limit_qty_flash_sale : null,
                    'min_qty_flash_sale'           => !empty($min_qty_flash_sale) ? (int)$min_qty_flash_sale : null,
                    'flash_sale'                   => !empty($flash_sale) ? (int)$flash_sale : 0,
                    'promotion_price'              => round($promotion_price),
                    'promotion_price_formatted'    => number_format(round($promotion_price)) . "đ",
                    'promoted_price'               => round($promoted_price = $promotion_price > 0 ? $price - $promotion_price : 0),
                    'promoted_price_formatted'     => number_format(round($promoted_price)) . "đ",
                    //                    'total_promotion_price'           => $promotion_price * $detail->quantity,
                    //                    'total_promotion_price_formatted' => number_format($promotion_price * $detail->quantity) . " đ",
                    'total'                        => round($total),
                    'total_formatted'              => number_format(round($total)) . "đ",
                    'special_percentage'           => !empty($promotion_price) ? $special_percentage : 0,
                    'special_percentage_formatted' => !empty($promotion_price) ? $special_percentage . "%" : 0 . "%",
                    'note'                         => $detail->note,
                    'notify_limit'                 => !empty($cart->distributor_id) ? $cart->checklimitproductHub($cart->distributor_id, $detail->product_code, $detail->quantity, $detail->product_name) : null,
                    'notify'                       => (!empty($infoAddress) && !empty($infoAddress->ward_code)) ? $cart->checkAddress($detail->product->sale_area, $infoAddress->city_code, $infoAddress->district_code, $infoAddress->ward_code) : null,
                    'promotion_check'              => $detail->promotion_check,
                    'options'                      => $detail->options,
                    'item_type'                    => $detail->item_type ?? null,
                    'item_value'                   => $detail->item_value ?? null,
                    'is_nutizen'                   => $checkNutiStore['is_nutizen'],
                    'option_details'               => $cart->getOption($detail->options),
                    'created_at'                   => date('d-m-Y', strtotime($detail->created_at)),
                    'created_by'                   => object_get($cart, 'createdBy.profile.full_name', $detail->created_by),
                    'updated_at'                   => date('d-m-Y', strtotime($detail->updated_at)),
                    'updated_by'                   => object_get($cart, 'updatedBy.profile.full_name', $detail->updated_by),
                    // 'promtion'                     => json_decode($promotionDMS)
                ];
            }
            $by_totals = $cart->total_info;
            if ($by_totals) {
                $key_point = array_search('TICH-DIEM', array_column($by_totals, 'code'));
                if (isset($key_point) && is_integer($key_point) == true) {
                    $promotion_point[] = $by_totals[$key_point];
                    unset($by_totals[$key_point]);
                }
            }
            $totals = $by_totals;

            if (!empty($promotion_point)){
                $totals = array_merge($by_totals, $promotion_point);
            }

//            $totals = array_merge($by_totals, $promotion_point ?? []);

            
            if (!empty($cart->free_item) && $cart->free_item != "[]") {
                foreach ($cart->free_item as $item) {
                    foreach ($item['text'] as $prod) {
                        if (!empty($prod['qty_gift'])) {
                            $freeitem[] =
                                [
                                    'is_exchange' => $item['is_exchange'],
                                    'code' => $item['code'],
                                    'title' => $item['title'],
                                    'act_type' => $item['act_type'],
                                    'text' => $prod,
                                    'value' => $prod['qty_gift'] * $item['value']
                                ];
                        } else {
                            $freeitem[] =
                                [
                                    'is_exchange' => $item['is_exchange'],
                                    'code' => $item['code'],
                                    'title' => $item['title'],
                                    'act_type' => $item['act_type'],
                                    'text' => $prod,
                                    'value' => $item['value']
                                ];
                        }
                    }
                };
            }
            if (!empty($promotion_flashsale)) {
                foreach ($promotion_flashsale as $flashsale) {
                    foreach ($flashsale->conditions as $condition) {
                        if ($condition->condition_name == "product_group") {
                            $product_sale = [];

                            $quantity_flash_sale = 0;
                            foreach ($cart->details as $key => $detail) {
                                if ($flashsale->act_type == 'sale_off_on_products' && !empty($flashsale->act_products) && $flashsale->act_products != "[]") {
                                    $prod_promo = json_decode($flashsale->act_products);
                                    $act_products = array_pluck(json_decode($flashsale->act_products), 'product_code');
                                    $check_prod = array_search($detail->product_code, $act_products);
                                    if(is_numeric($check_prod)){
                                        $quantity_flash_sale += $detail->quantity;
                                        $fileCode        = object_get($detail, 'file.code');
                                        $product_sale[] = [
                                            'product_code'                 => $detail->product_code,
                                            'product_name'                 => $detail->product_name,
                                            'product_slug'                 => Arr::get($detail, 'getProduct.slug', null),
                                            'product_thumb'                => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                                            'price'                        => $detail->price,
                                        ];
                                    }
                                }
                                if ($flashsale->act_type == 'sale_off_on_categories' && !empty($flashsale->act_categories) && $flashsale->act_categories != "[]") {
                                    $Category = !empty($flashsale->act_categories) ? array_pluck(json_decode($flashsale->act_categories), 'category_id') : [];
                                    foreach (json_decode($flashsale->act_categories) as $act_category) {
                                        $check = array_intersect($Category, explode(',', $detail->product_category));
                                        if (!empty($check)) {
                                            $quantity_flash_sale += $detail->quantity;
                                            $fileCode        = object_get($detail, 'file.code');
                                            $product_sale[] = [
                                                'product_code'                 => $detail->product_code,
                                                'product_name'                 => $detail->product_name,
                                                'product_slug'                 => Arr::get($detail, 'getProduct.slug', null),
                                                'product_thumb'                => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                                                'price'                        => $detail->price,
                                            ];
                                        }
                                    }
                                }
                            }
                            $promotion_total = PromotionTotal::model()->where('order_customer_id', TM::getCurrentUserId())->where('promotion_id', $flashsale->id)->whereRaw("created_at BETWEEN '$flashsale->start_date' AND '$flashsale->end_date'")->get();

                            if (!empty($product_sale) && $product_sale != "[]") {
                                $limit_sale[] = [
                                    "title" => $flashsale->name,
                                    "min_qty_sale" => !empty($flashsale->min_qty_sale) && $flashsale->min_qty_sale > 0 ? ($quantity_flash_sale < $flashsale->min_qty_sale ? (int)$flashsale->min_qty_sale : null) : null,
                                    "limit_sale" => !empty($flashsale->limit_qty_flash_sale) && $flashsale->limit_qty_flash_sale > 0 ? ($quantity_flash_sale > $flashsale->limit_qty_flash_sale ? (int)$flashsale->limit_qty_flash_sale : null) : null,
                                    "limit_buy_order" => !empty($promotion_total) && count($promotion_total) >= $flashsale->limit_buy ? $flashsale->limit_buy : null,
                                    "products" =>  $product_sale ?? [],
                                ];
                            }
                            break;
                        }
                    }
                }
            }
            //check
            if (!empty($sales)) {
                foreach ($sales as $sale) {
                    foreach ($sale->conditions as $condition) {
                        if ($condition->condition_name == "product_group") {
                            $product_sale = [];
                            $quantity_sale = 0;
                            foreach ($cart->details as $key => $detail) {
                                if ($sale->act_type == 'sale_off_on_products' && !empty($sale->act_products) && $sale->act_products != "[]") {
                                    $prod_promo = json_decode($sale->act_products);
                                    $act_products = array_pluck(json_decode($sale->act_products), 'product_code');
                                    $check_prod = array_search($detail->product_code, $act_products);
                                    if(is_numeric($check_prod)){
                                        $quantity_sale += $detail->quantity;
                                        $fileCode        = object_get($detail, 'file.code');
                                        $product_sale[] = [
                                            'product_code'                 => $detail->product_code,
                                            'product_name'                 => $detail->product_name,
                                            'product_slug'                 => Arr::get($detail, 'getProduct.slug', null),
                                            'product_thumb'                => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                                            'price'                        => $detail->price,
                                        ];
                                    }
                                }
                                if ($sale->act_type == 'sale_off_on_categories' && !empty($sale->act_categories) && $sale->act_categories != "[]") {
                                    $Category = !empty($sale->act_categories) ? array_pluck(json_decode($sale->act_categories), 'category_id') : [];
                                    foreach (json_decode($sale->act_categories) as $act_category) {
                                        $check = array_intersect($Category, explode(',', $detail->product_category));
                                        if (!empty($check)) {
                                            $quantity_sale += $detail->quantity;
                                            $fileCode        = object_get($detail, 'file.code');
                                            $product_sale[] = [
                                                'product_code'                 => $detail->product_code,
                                                'product_name'                 => $detail->product_name,
                                                'product_slug'                 => Arr::get($detail, 'getProduct.slug', null),
                                                'product_thumb'                => !empty($fileCode) ? env('GET_FILE_URL') . $fileCode : null,
                                                'price'                        => $detail->price,
                                            ];
                                        }
                                    }
                                }
                            }

                            $promotion_total = PromotionTotal::model()->where('order_customer_id', TM::getCurrentUserId())->where('promotion_id', $sale->id)->whereRaw("created_at BETWEEN '$sale->start_date' AND '$sale->end_date'")->get();

                            if (!empty($product_sale) && $product_sale != "[]") {
                                $limit_sale[] = [
                                    "title" => $sale->name,
                                    "min_qty_sale" => !empty($sale->min_qty_sale) && $sale->min_qty_sale > 0 ? ($quantity_sale < $sale->min_qty_sale ? (int)$sale->min_qty_sale : null) : null,
                                    "limit_sale" => !empty($sale->limit_qty_flash_sale) && $sale->limit_qty_flash_sale > 0 ? ($quantity_sale > $sale->limit_qty_flash_sale ? (int)$sale->limit_qty_flash_sale : null) : null,
                                    "limit_buy_order" => !empty($promotion_total) && count($promotion_total) >= $sale->limit_buy ? $sale->limit_buy : null,
                                    "products" =>  $product_sale ?? [],
                                ];
                            }
                            break;
                        }
                    }
                }
            }

            //endcheck

            // gioi han so luong mua tren gio hang theo ttpp/npp
            $user = User::model()->where('id', $cart->distributor_id)->first();
            $now          = date('Y-m-d');
            if (!empty($user)) {
                $orders = Order::model()->where('distributor_id', $cart->distributor_id)->whereDate('created_at', $now)->count();
                !empty($orders) && $orders >= $user->qty_max_day ? $qty_max_day = $user->qty_max_day : $qty_max_day = null;
                $cart->details->sum('total') < $user->min_amt ? $min_amt = $user->min_amt : $min_amt = null;
                $cart->details->sum('total') > $user->max_amt ? $max_amt = $user->max_amt : $max_amt = null;
            }

            //end

            // gioi han mua hang trong ngay
            $limit_orderday = Setting::model()->where('code', 'LIMITORDER')->first();

            !empty($limit_orderday) ? $data = json_decode($limit_orderday->data) : null;

            if (!empty($data)) {
                $now = date('Y-m-d');
                $order = Order::whereDate('created_at', $now)
                    ->where('customer_id', TM::getCurrentUserId())
                    ->get();
                if ($data[0]->value > 0) {
                    if (count($order) >= $data[0]->value) {
                        $limit_order_day = $data[0]->value;
                    }
                }
            }
            //end

            return [
                'id'                         => $cart->id,
                'user_id'                    => object_get($cart, 'user_id', null),
                'session_id'                 => object_get($cart, 'session_id', null),
                'address'                    => $cart->address,
                'ship_address_latlong'       => $cart->ship_address_latlong,
                'shipping_address_id'        => $cart->shipping_address_id,
                'address_window_id'          => $cart->address_window_id,
                'street_address'             => $cart->street_address,
                'shipping_address_ward'      => object_get($cart, 'getShippingAddress.getWard.full_name', null),
                'shipping_address_district'  => object_get($cart, 'getShippingAddress.getDistrict.full_name', null),
                'shipping_address_city'      => object_get($cart, 'getShippingAddress.getCity.full_name', null),
                'description'                => $cart->description,
                'phone'                      => $cart->phone,
                'shipping_address_full_name' => object_get($cart, 'getShippingAddress.full_name', null),
                'payment_method'             => $cart->payment_method,
                'payment_method_name'        => PAYMENT_METHOD_NAME[$cart->payment_method] ?? null,
                'shipping_method'            => $cart->shipping_method,
                'shipping_method_code'       => $cart->shipping_method_code ?? null,
                'shipping_method_name'       => $cart->shipping_method_name ?? null,
                'shipping_service'           => $cart->shipping_service ?? null,
                'is_freeship'                => $cart->is_freeship ?? 0,
                'shipping_note'   => $cart->shipping_note ?? null,
                'shipping_diff'   => $cart->shipping_diff ?? null,
                'service_name'               => $cart->service_name ?? null,
                'extra_service'              => $cart->extra_service ?? null,
                'ship_fee'                   => $cart->ship_fee ?? null,
                'ship_fee_down'              => $cart->ship_fee_down ?? null,
                'ship_fee_real'              => $cart->ship_fee_real ?? 0,
                'estimated_deliver_time'                   => $cart->estimated_deliver_time ?? null,
                'lading_method'            => $cart->lading_method ?? null,
                'ship_fee_start'            => $cart->ship_fee_start ?? null,
                //                'shipping_method_name'       => object_get($cart, 'getShippingMethod.name', null),
                'receiving_time'             => !empty($cart->receiving_time) ? date(
                    'd-m-Y H:i:s',
                    strtotime($cart->receiving_time)
                ) : null,
                'total'                      => round($cart->details->sum('total')),
                // 'limit_qty_order'            => !empty($limit_qty_order) ? (int)$limit_qty_order : null,
                'point'                      => $cart->point ?? null,
                'exchangepoint'              => $cart->ex_change_point ?? null,
                'total_product_promotion'    => $total_product_promotion ?? null,
                'total_product'              => $total_product ?? null,
                'total_product_promotion_fortmated'    => number_format(round($total_product_promotion ?? null)) . " đ",
                'total_product_fortmated'              => number_format(round($total_product ?? null)) . " đ",
                'point_use'                  => number_format(round($cart->point_use ?? null)) . " đ",
                'promotion'                  => $cart->promotion ?? [],
                'saving'                     => $cart->saving ?? null,
                'saving_fortmated'           => number_format(round($cart->saving ?? 0)) . " đ",
                'total_fortmated'            => number_format(round($cart->details->sum('total'))) . " đ",
                'total_down'                 => !empty($cart->total_info) ? number_format(round($cart->total_info[count($cart->total_info) - 1]['value'] ?? null)) . " đ" : null,
                'total_quantity'             => $cart->details->sum('quantity'),
                'coupon_code'                => $cart->coupon_code ?? null,
                'coupon_discount_code'       => $cart->coupon_discount_code ?? null,   
                'coupon_price'               => round($cart->coupon_price ?? null),
                'coupon_name'                => $cart->coupon_name ?? null,
                'coupon_delivery_name'       => $cart->coupon_delivery_name ?? null,
                'coupon_delivery_code'       => $cart->coupon_delivery_code ?? null,
                'coupon_delivery_price'      => round($cart->coupon_delivery_price ?? null),
                'qty_limit_buy'              => !empty($qty_limit_buy) ? (int)$qty_limit_buy : null,
                'min_qty_buy'                => !empty($min_qty_flash_sale) ? (int)$min_qty_flash_sale : null,
                'qty_buy_order'              => !empty($qty_buy_order) ? (int)$qty_buy_order : null,
                'voucher_code'               => $cart->voucher_code ?? null,
                'voucher_title'              => $cart->voucher_title ?? null,
                'voucher_value'              => $cart->voucher_value ?? null,
                'voucher_value_use'          => $cart->voucher_value_use ?? 0,
                'created_at'                 => date('d-m-Y', strtotime($cart->created_at)),
                'created_by'                 => object_get($cart, 'createdBy.profile.full_name', object_get($cart, 'created_by', null)),
                'updated_at'                 => date('d-m-Y', strtotime($cart->updated_at)),
                'updated_by'                 => object_get($cart, 'updatedBy.profile.full_name', object_get($cart, 'updated_by', null)),
                'details'                    => $details,
                'limit_sale'                 => !empty($limit_sale) ? $limit_sale : null,
                'limit_order_day'            => !empty($limit_order_day) ? (int)$limit_order_day : null,
                'qty_max_day'                => $qty_max_day ?? null,
                'min_amt'                    => $min_amt ?? null,
                'min_amt_formatted'          => number_format(round($min_amt ?? 0)) . "đ",
                'max_amt'                    => $max_amt ?? null,
                'max_amt_formatted'          => number_format(round($max_amt ?? 0)) . "đ",
                'totals'                     => $totals ?? [],
                'free_item'                  => $freeitem ?? [],
                'cart_info'                  => json_decode($cart->cart_info) ?? [],
                'full_name'                 => $cart->full_name,
                'email'                     => $cart->email,
                'distributor_id'            => $cart->distributor_id,
                'distributor_code'          => $cart->distributor_code,
                'distributor_name'          => $cart->distributor_name,
                'distributor_phone'         => $cart->distributor_phone,
                'distributor_lat'           => $cart->distributor_lat,
                'distributor_long'          => $cart->distributor_long,
                'distributor_postcode'      => $cart->distributor_postcode,
                'order_type'                => $cart->order_type,
                'distributor_city_code'     => $cart->distributor_city_code,
                'distributor_district_code' => $cart->distributor_district_code,
                'distributor_ward_code'     => $cart->distributor_ward_code,
                'distributor_city_name'     => $cart->distributor_city_name,
                'distributor_district_name' => $cart->distributor_district_name,
                'distributor_ward_name'     => $cart->distributor_ward_name,
                'customer_city_code'        => $cart->customer_city_code,
                'customer_point'            => $cart->User->point ?? 0,
                'customer_district_code'    => $cart->customer_district_code,
                'customer_ward_code'        => $cart->customer_ward_code,
                'customer_city_name'        => Arr::get($cart, 'getCity.full_name', null),
                'customer_district_name'    => Arr::get($cart, 'getDistrict.full_name', null),
                'customer_ward_name'        => Arr::get($cart, 'getWard.full_name', null),
                'customer_full_address'     => $cart->customer_full_address,
                'customer_lat'              => $cart->customer_lat,
                'customer_long'             => $cart->customer_long,
                'customer_postcode'         => $cart->customer_postcode,
                'total_weight'              => round($cart->total_weight, 1) ?? 0,
                'notify_limit_weight'       => !empty($cart->total_weight) && $cart->total_weight > 30 && $cart->shipping_method_code != SHIPPING_PARTNER_TYPE_DEFAULT ? "Vượt quá khối lượng giao hàng của đơn vị vận chuyển (Tối đa 30kg)." : null,
                //                'street_address'=>$cart->distributor_code,
                'seller_id'                 => $cart->seller_id,
                'seller_code'               => $cart->seller_code,
                'seller_name'               => $cart->seller_name,
                'notification_distributor'  => (int)$cart->notification_distributor,
                'is_product_not_store'      => $is_nutifood == 1 && $is_nutizen == 1 ? 1 : 0,
                'shipping_error'            => $cart->shipping_error,
                'order_source'              => $cart->order_source,
                'discount_admin_input'      => $cart->discount_admin_input,
                'discount_admin_input_type' => $cart->discount_admin_input_type,
                'coupon_admin'              => $cart->coupon_admin ? json_decode($cart->coupon_admin) : null
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
    function checkProductStore($categoryNutizen, $categories)
    {
        if (empty($categoryNutizen)) {
            return [
                'is_nutizen' => 0,
                'is_nutifood' => 0
            ];
        }
        foreach ($categories as $category) {
            if (in_array($category, $categoryNutizen)) {
                return [
                    'is_nutizen' => 1,
                    'is_nutifood' => 0
                ];
            }
        }
        return [
            'is_nutizen' => 0,
            'is_nutifood' => 1
        ];
    }
}
