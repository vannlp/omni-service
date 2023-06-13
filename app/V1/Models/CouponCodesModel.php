<?php


namespace App\V1\Models;


use App\CouponCodes;

class CouponCodesModel extends AbstractModel
{
    /**
     * CouponCategoryModel constructor.
     * @param CouponCodes|null $model
     */
    public function __construct(CouponCodes $model = null)
    {
        parent::__construct($model);
    }
    
    public function searchDetail($input = [], $with = [], $limit)
    {
        $query = $this->make($with);

        $this->sortBuilder($query, $input);
        $query->where('coupon_id',$input['id']);
        if(!empty($input['code'])){
            $query->where('code','like',"%{$input['code']}%");
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
}