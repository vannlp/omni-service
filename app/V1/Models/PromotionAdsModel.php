<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\Supports\Message;
use App\PromotionAds;
use App\TM;
use phpDocumentor\Reflection\Types\Nullable;

class PromotionAdsModel extends AbstractModel
{
    public function __construct(PromotionAds $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {

        $id        = !empty($input['id']) ? $input['id'] : 0;
        $companyId = TM::getCurrentCompanyId();

        if ($id) {
            $promotionAds = PromotionAds::find($id);
            if (empty($promotionAds)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $promotionAds->title       = array_get($input, 'title', $promotionAds->title);
            $promotionAds->description = array_get($input, 'description', $promotionAds->description);
            $promotionAds->coupon      = array_get($input, 'coupon', $promotionAds->coupon);
            $promotionAds->image_id    = array_get($input, 'image_id', $promotionAds->image_id);
            $promotionAds->updated_at  = date("Y-m-d H:i:s", time());
            $promotionAds->updated_by  = TM::getCurrentUserId();
            $promotionAds->save();
        }
        else {
            $param        = [
                'title'       => $input['title'],
                'description' => array_get($input, 'description', null),
                'coupon'      => $input['coupon'],
                'image_id'    => $input['image_id'],
                'company_id'  => $companyId,
            ];
            $promotionAds = $this->create($param);
        }
        return $promotionAds;
    }

}
