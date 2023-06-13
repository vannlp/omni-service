<?php


namespace App\V1\Transformers\Coupon;


use App\Coupon;
use App\CouponCategory;
use App\CouponProduct;
use App\Product;
use App\Order;
use App\RotationDetail;
use App\TM;
use App\User;
use App\Supports\TM_Error;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;

class CouponApplied extends TransformerAbstract
{
    protected $cart;

    public function __construct($cart)
    {
        $this->cart = $cart;
    }

    public function CouponProducts(Coupon $coupon)
    {
        $couponProductIds = explode(',', $coupon->product_ids);

        foreach ($this->cart['details'] as $cartDetail) {
            if (!empty($couponProductIds)) {
                if (in_array($cartDetail->product_id, $couponProductIds)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    public function CouponCategoris(Coupon $coupon)
    {
        $couponCategoryIds = explode(',', $coupon->category_ids);

        foreach ($this->cart['details'] as $cartDetail) {
            if (!$cartDetail->product_id) {
                continue;
            }
            $productCategories = Arr::get($cartDetail, 'product.category_ids');
            $productCategories = explode(',', $productCategories);
            if (!$productCategories) {
                continue;
            }
            $dataFor = count($productCategories) > count($couponCategoryIds) ? $productCategories : $couponCategoryIds;
            foreach ($dataFor as $key => $item) {
                if (!empty($couponCategoryIds)) {
                    if (in_array($item, $couponCategoryIds)) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
    }

    public function CouponProductExcept(Coupon $coupon)
    {
        $couponProductExceptIds = explode(',', $coupon->product_except_ids);

        foreach ($this->cart['details'] as $cartDetail) {
            if (!empty($couponProductExceptIds)) {
                if (in_array($cartDetail->product_id, $couponProductExceptIds)) {
                    return true;
                }
            } else {
                return true;
            }
        }

        return false;
    }

    public function CouponCategorisExcept(Coupon $coupon)
    {

        $couponCategoryExceptIds = explode(',', $coupon->category_except_ids);

        foreach ($this->cart['details'] as $cartDetail) {
            if (!$cartDetail->product_id) {
                continue;
            }
            $productCategories = Arr::get($cartDetail, 'product.category_ids');
            $productCategories = explode(',', $productCategories);
            if (!$productCategories) {
                continue;
            }
            $dataFor = count($productCategories) > count($couponCategoryExceptIds) ? $productCategories : $couponCategoryExceptIds;
            foreach ($dataFor as $key => $item) {
                if (!empty($couponCategoryExceptIds)) {
                    if (in_array($item, $couponCategoryExceptIds)) {
                        return true;
                    }
                } else {
                    return true;
                }
            }
        }

        return false;
    }


    public function transform(Coupon $coupon)
    {
        $is_apply = 0;
        $total = 0;
        $couponProductIds = explode(',', $coupon->product_ids);
        $couponCategorys = explode(',', $coupon->category_ids);

        $couponProductExceptIds = explode(',', $coupon->product_except_ids);
        $couponCategoryExceptIds = explode(',', $coupon->category_except_ids);

        if ($coupon->type_apply == "apply_products") {
            foreach ($this->cart['details'] as $cartDetail) {
                $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategoryExceptIds);
                if (in_array($cartDetail->product_id, $couponProductExceptIds) || !empty($check)) {
                } else {
                    if (!empty($couponProductIds) || !empty($couponCategorys)) {
                        $check = array_intersect($couponCategorys, explode(',', $cartDetail->product_category));
                        if (in_array($cartDetail->product_id, $couponProductIds) || !empty($check)) {
                            $total += $cartDetail->total;
                        }
                    }
                }
            }
        }
        if ($coupon->type_apply == "apply_cart" || empty($coupon->type_apply)) {
            foreach ($this->cart['details'] as $cartDetail) {
                $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategoryExceptIds);
                if (in_array($cartDetail->product_id, $couponProductExceptIds) || !empty($check)) {
                } else {
                    $total += $cartDetail->total;
                }
            }
        }

        if (empty($coupon->product_except_ids) && empty($coupon->category_except_ids) && empty($coupon->category_ids) && empty($coupon->product_ids)) {
            $is_apply = 1;
        }


        foreach ($this->cart['details'] as $cartDetail) {
            if (!empty($coupon->product_ids) || !empty($coupon->category_ids)) {
                $check = array_intersect(explode(',', $cartDetail->product_category), $couponCategorys);
                if (in_array($cartDetail->product_id, $couponProductIds) || !empty($check)) {
                    $is_apply = 1;
                    break;
                } else {
                    $is_apply = 0;
                }
            }
        }

        if (!empty($coupon->mintotal) && !empty($coupon->maxtotal)) {
            if ($total < $coupon->mintotal ||  $total > $coupon->maxtotal) {
                $is_apply = 0;
            }
            
        } else if (!empty($coupon->mintotal)) {
            if ($total < $coupon->mintotal) {
                $is_apply = 0;
            }
        } else if (!empty($coupon->maxtotal)) {
            if ($total > $coupon->maxtotal) {
                $is_apply = 0;
            }
        }



        if (!empty($coupon->condition)) {
            switch ($coupon->condition) {
                case 'first_order_app':
                    $order_first_check = Order::where('customer_id', TM::getCurrentUserId())->where('order_channel', 'MOBILE')->first();
                    if (!empty($order_first_check) || get_device() != 'APP') {
                        $is_apply = 0;
                    }
                    break;
                case 'first_order_web':
                    $order_first_check = Order::where('customer_id', TM::getCurrentUserId())->whereIn('order_channel', ['WEB', 'MWEB'])->first();
                    if (!empty($order_first_check) || get_device() == 'APP') {
                        $is_apply = 0;
                    }
                    break;
                case 'apply_app':
                    if (get_device() != 'APP') {
                        $is_apply = 0;
                    }
                    break;
                case 'apply_web':
                    if (get_device() == 'APP') {
                        $is_apply = 0;
                    }
                    break;
                case 'first_order':
                    $order_first_check = Order::where('customer_id', TM::getCurrentUserId())->first();
                    if (!empty($order_first_check)) {
                        $is_apply = 0;
                    }
                    break;
                case 'rotation':
                    $detail = RotationDetail::join('rotation_results as rr', 'rr.code', 'rotation_details.rotation_code')
                        ->where('rr.coupon_id', $coupon->id)
                        ->where('user_id', TM::getCurrentUserId())->first();
                    if (empty($detail)) {
                        $is_apply = 0;
                    }
                    break;
                default:
                    break;
            }
        }



        if (!empty($this->cart->ship_fee) && $is_apply == 1) {

            if ($coupon->type === 'P') {
                $discountPrice = $this->cart->ship_fee * $coupon->discount / 100;
                if (!empty($coupon->limit_price) && $coupon->limit_price > 0) {
                    if ($discountPrice >= ($coupon->limit_price ?? 0)) {
                        $discountPrice = $coupon->limit_price;
                    }
                }
            }
            if ($coupon->type === 'F') {
                $discountPrice = $coupon->discount;
                if ($discountPrice >= $this->cart->ship_fee) {
                    $discountPrice = $this->cart->ship_fee;
                }
            }
        }

        $coupon_code = Coupon::join('coupon_codes', 'coupon_codes.coupon_id', '=', 'coupons.id')
            ->where('coupons.code', $coupon->code)
            ->where('coupon_codes.is_active', 0)
            ->first();

        try {
            return [
                'id'                => $coupon->id,
                'code'              => !empty($coupon_code->code) ? $coupon_code->code : $coupon_code,
                'name'              => $coupon->name,
                'type'              => $coupon->type,
                'content'           => $coupon->content,
                'discount'          => $coupon->discount,
                'total'             => $coupon->total,
                'type_discount'     => $coupon->type_discount,
                'free_shipping'     => $coupon->free_shipping,
                'product_ids'       => $coupon->product_ids,
                'product_codes'     => $coupon->product_codes,
                'product_names'     => $coupon->product_names,
                'category_ids'      => $coupon->category_ids,
                'category_codes'    => $coupon->category_codes,
                'category_names'    => $coupon->category_names,
                'date_start'        => date('d-m-Y', strtotime($coupon->date_start)),
                'date_end'          => date('d-m-Y', strtotime($coupon->date_end)),
                'uses_total'        => $coupon->uses_total,
                'uses_customer'     => $coupon->uses_customer,
                'status'            => $coupon->status,
                'is_apply'          => $is_apply,
                'discount_price'    => $discountPrice ?? null,
                'coupon_products'   => $coupon->couponProducts->map(function ($item) {
                    return [
                        'product_id'   => $item->product_id,
                        'product_code' => $item->product_code,
                        'product_name' => $item->product_name,
                    ];
                }),
                'coupon_categories' => $coupon->couponCategories->map(function ($item) {
                    return [
                        'category_id'   => $item->category_id,
                        'category_code' => $item->category_code,
                        'category_name' => $item->category_name,
                    ];
                }),
                'created_at'        => date('d-m-Y', strtotime($coupon->created_at)),
                'updated_at'        => date('d-m-Y', strtotime($coupon->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
    private function getIds($data)
    {
        foreach ($data['details'] as $key => $datum) {
            $product = Product::model()->where('id', $datum['product_id'])->first();
            $product_ids[] = $product->id;
            $category_ids = explode(',', $product->category_ids);
        };
        return [
            'category_ids' => $category_ids,
            'product_id'   => $product_ids
        ];
    }
}
