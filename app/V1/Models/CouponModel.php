<?php


namespace App\V1\Models;


use App\Coupon;
use App\CouponCategory;
use App\CouponCategoryexcept;
use App\CouponCodes;
use App\CouponProduct;
use App\CouponProductexcept;
use App\Supports\Message;
use App\TM;

class CouponModel extends AbstractModel
{
    /**
     * CouponModel constructor.
     * @param Coupon|null $model
     */
    public function __construct(Coupon $model = null)
    {
        parent::__construct($model);
    }

    public function searchs($input = [], $with = [], $limit){
        $query = $this->make($with);

        $this->sortBuilder($query, $input);

        if(!empty($input['name'])){
            $query->where('name','like',"%{$input['name']}%");
        }

        if(!empty($input['code'])){
            $query->where('code','like',"%{$input['code']}%");
        }

        if(!empty($input['type'])){
            $query->where('type','like',"%{$input['type']}%");
        }
       
        if(isset($input['status'])){ 
            $query->where('status',$input['status']);
        }

        if(!empty($input['coupon_code'])){
            $query->whereHas('coupon', function($q) use($input){
                $q->where('code', 'like', "%{$input['coupon_code']}%");
            });
        }

        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if (!empty($input['coupon_products'])) {
            $productId   = array_pluck($input['coupon_products'], 'product_id');
            $productId   = implode(',', $productId);
            $productCode = array_pluck($input['coupon_products'], 'product_code');
            $productCode = implode(',', $productCode);
            $productName = array_pluck($input['coupon_products'], 'product_name');
            $productName = implode(',', $productName);
        }
        if (!empty($input['coupon_categories'])) {
            $categoryId   = array_pluck($input['coupon_categories'], 'category_id');
            $categoryId   = implode(',', $categoryId);
            $categoryCode = array_pluck($input['coupon_categories'], 'category_code');
            $categoryCode = implode(',', $categoryCode);
            $categoryName = array_pluck($input['coupon_categories'], 'category_name');
            $categoryName = implode(',', $categoryName);
        }

        if (!empty($input['coupon_products_except'])) {
            $productexceptId   = array_pluck($input['coupon_products_except'], 'product_id');
            $productexceptId   = implode(',', $productexceptId);
            $productexceptCode = array_pluck($input['coupon_products_except'], 'product_code');
            $productexceptCode = implode(',', $productexceptCode);
            $productexceptName = array_pluck($input['coupon_products_except'], 'product_name');
            $productexceptName = implode(',', $productexceptName);
        }
        if (!empty($input['coupon_categories_except'])) {
            $categoryexceptId   = array_pluck($input['coupon_categories_except'], 'category_id');
            $categoryexceptId   = implode(',', $categoryexceptId);
            $categoryexceptCode = array_pluck($input['coupon_categories_except'], 'category_code');
            $categoryexceptCode = implode(',', $categoryexceptCode);
            $categoryexceptName = array_pluck($input['coupon_categories_except'], 'category_name');
            $categoryexceptName = implode(',', $categoryexceptName);
        }
        if ($id) {
            $coupon = Coupon::find($id);
            if (empty($coupon)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $coupon->code           = array_get($input, 'code', $coupon->code);
            $coupon->name           = array_get($input, 'name', $coupon->name);
            // $coupon->type           = array_get($input, 'type', $coupon->type);
            $coupon->content        = array_get($input, 'content', $coupon->content);
            $coupon->type_discount  = array_get($input, 'type_discount', $coupon->type_discount);
            $coupon->type_apply     = array_get($input, 'type_apply', $coupon->type_apply);
            $coupon->apply_discount = array_get($input, 'apply_discount', $coupon->apply_discount);
            $coupon->stack_able = array_get($input, 'stack_able', $coupon->stack_able);
            $coupon->condition      = array_get($input, 'condition', null);
            // $coupon->discount       = array_get($input, 'discount', $coupon->discount);
            // $coupon->limit_price    = array_get($input, 'limit_price', $coupon->limit_price);
            $coupon->free_shipping  = array_get($input, 'free_shipping', $coupon->free_shipping);
            $coupon->date_start     = date('Y-m-d H:i:s', strtotime(array_get($input, 'date_start', $coupon->date_start)));
            $coupon->date_end       = date('Y-m-d H:i:s', strtotime(array_get($input, 'date_end', $coupon->date_end)));
            $coupon->uses_total     = array_get($input, 'uses_total', $coupon->uses_total);
            // $coupon->total          = array_get($input, 'total', $coupon->total);
            $coupon->mintotal          = array_get($input, 'mintotal', $coupon->mintotal);
            $coupon->maxtotal          = array_get($input, 'maxtotal', $coupon->maxtotal);
            $coupon->uses_customer  = array_get($input, 'uses_customer', $coupon->uses_customer);
            $coupon->thumbnail_id   = array_get($input, 'thumbnail_id', $coupon->thumbnail_id);
            $coupon->thumbnail      = array_get($input, 'thumbnail', $coupon->thumbnail);
            $coupon->status         = array_get($input, 'status', $coupon->status);
            $coupon->product_ids    = $productId ?? null;
            $coupon->product_codes  = $productCode ?? null;
            $coupon->product_names  = $productName ?? null;
            $coupon->category_ids   = $categoryId ?? null;
            $coupon->category_codes = $categoryCode ?? null;
            $coupon->category_names = $categoryName ?? null;
            $coupon->product_except_ids    = $productexceptId ?? null;
            $coupon->product_except_codes  = $productexceptCode ?? null;
            $coupon->product_except_names  = $productexceptName ?? null;
            $coupon->category_except_ids   = $categoryexceptId ?? null;
            $coupon->category_except_codes = $categoryexceptCode ?? null;
            $coupon->category_except_names = $categoryexceptName ?? null;
            $coupon->updated_at     = date("Y-m-d H:i:s", time());
            $coupon->updated_by     = TM::getCurrentUserId();
            $coupon->save();
        } else {
            $param  = [
                'name'           => $input['name'],
                'code'           => $input['code'],
                // 'type'           => $input['type'],
                'content'        => $input['content'],
                'type_discount'  => $input['type_discount'],
                'type_apply'     => $input['type_apply'],
                'apply_discount' => $input['apply_discount'],
                'stack_able'     => $input['stack_able'],
                'condition'      => $input['condition'] ?? null,
                'thumbnail'      => array_get($input, 'thumbnail', null),
                'thumbnail_id'   => array_get($input, 'thumbnail_id', null),
                // 'discount'       => array_get($input, 'discount', null),
                // 'limit_price'       => array_get($input, 'limit_price', 0),
                // 'total'          => array_get($input, 'total', null),
                'mintotal'          => array_get($input, 'mintotal', null),
                'maxtotal'          => array_get($input, 'maxtotal', null),
                'free_shipping'  => array_get($input, 'free_shipping', 0),
                'product_ids'    => $productId ?? null,
                'product_codes'  => $productCode ?? null,
                'product_names'  => $productName ?? null,
                'category_ids'   => $categoryId ?? null,
                'category_codes' => $categoryCode ?? null,
                'category_names' => $categoryName ?? null,
                'product_except_ids'    => $productexceptId ?? null,
                'product_except_codes'  => $productexceptCode ?? null,
                'product_except_names'  => $productexceptName ?? null,
                'category_except_ids'   => $categoryexceptId ?? null,
                'category_except_codes' => $categoryexceptCode ?? null,
                'category_except_names' => $categoryexceptName ?? null,
                'date_start'     => date('Y-m-d H:i:s', strtotime(array_get($input, 'date_start', null))),
                'date_end'       => date('Y-m-d H:i:s', strtotime(array_get($input, 'date_end', null))),
                'uses_total'     => array_get($input, 'uses_total', null),
                'uses_customer'  => array_get($input, 'uses_customer', null),
                'status'         => array_get($input, 'status', 0),
                'company_id'     => TM::getCurrentCompanyId(),
                'store_id'       => TM::getCurrentStoreId(),
            ];
            $coupon = $this->create($param);
        }

        if (!empty($input['coupon_code']) && $input['coupon_code'] != "[]") {
            foreach ($input['coupon_code'] as $coupon_code) {

                $check_coupon = CouponCodes::model()->where('code', $coupon_code['code'])->first();
                if(!empty($check_coupon)){
                    $code = $coupon_code['code'];
                    return ['status_code' => 400, 'message' => "$code: đã tồn tại, vui lòng chọn mã khác"];
                }


                $couponCodeModel = new CouponCodes();
                $couponCodeModel->create([
                    'coupon_id'      => $coupon->id,
                    'code'           => $coupon_code['code'],
                    'is_active'      => 0,
                    'type'           => $coupon_code['type'],
                    'discount'       => $coupon_code['discount'],
                    'limit_discount' => $coupon_code['limit_discount'],
                    'user_code'      => !empty($coupon_code['user_code']) ? $coupon_code['user_code'] : null,
                    'start_date'     => !empty($coupon_code['start_date']) ? date('Y-m-d', strtotime($coupon_code['start_date'])) : null,
                    'end_date'       => !empty($coupon_code['end_date']) ? date('Y-m-d', strtotime($coupon_code['end_date'])) : null,
                ]);
            }
        }

        if (!empty($input['coupon_products'])) {
            CouponProduct::where('coupon_id', $coupon->id)->delete();
            foreach ($input['coupon_products'] as $item) {
                $couponProductModel = new CouponProductModel();
                $couponProductModel->create([
                    'coupon_id'    => $coupon->id,
                    'product_id'   => $item['product_id'],
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name']
                ]);
            }
        } else {
            CouponProduct::where('coupon_id', $coupon->id)->delete();
        }

        if (!empty($input['coupon_categories'])) {
            CouponCategory::where('coupon_id', $coupon->id)->delete();
            foreach ($input['coupon_categories'] as $item) {
                $couponCategoryModel = new CouponCategoryModel();
                $couponCategoryModel->create([
                    'coupon_id'     => $coupon->id,
                    'category_id'   => $item['category_id'],
                    'category_code' => $item['category_code'],
                    'category_name' => $item['category_name']
                ]);
            }
        } else {
            CouponCategory::where('coupon_id', $coupon->id)->delete();
        }

        if (!empty($input['coupon_products_except'])) {
            CouponProductexcept::where('coupon_id', $coupon->id)->delete();
            foreach ($input['coupon_products_except'] as $item) {
                $couponProductExceptModel = new CouponProductExceptModel();
                $couponProductExceptModel->create([
                    'coupon_id'    => $coupon->id,
                    'product_id'   => $item['product_id'],
                    'product_code' => $item['product_code'],
                    'product_name' => $item['product_name']
                ]);
            }
        } else {
            CouponProductexcept::where('coupon_id', $coupon->id)->delete();
        }

        if (!empty($input['coupon_categories_except'])) {
            CouponCategoryexcept::where('coupon_id', $coupon->id)->delete();
            foreach ($input['coupon_categories_except'] as $item) {
                $couponCategoryExceptModel = new CouponCategoryExceptModel();
                $couponCategoryExceptModel->create([
                    'coupon_id'     => $coupon->id,
                    'category_id'   => $item['category_id'],
                    'category_code' => $item['category_code'],
                    'category_name' => $item['category_name']
                ]);
            }
        } else {
            CouponCategoryexcept::where('coupon_id', $coupon->id)->delete();
        }
        return $coupon;
    }
}
