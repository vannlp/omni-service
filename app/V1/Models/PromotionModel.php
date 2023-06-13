<?php
/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;


use App\Image;
use App\Order;
use App\OrderDetail;
use App\Promotion;
use App\PromotionDetail;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\User;

class PromotionModel extends AbstractModel
{
    public function __construct(Promotion $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                // Update Promotion
                $param['id'] = $id;
                $promotion = Promotion::find($id);
                if (empty($promotion)) {
                    throw new \Exception(Message::get("promotions.not-exist", "#$id"));
                }
                $promotion->title = array_get($input, 'title', $promotion->title);
                $promotion->code = array_get($input, 'code', $promotion->code);
                $promotion->from = date("Y-m-d H:i:s", strtotime(array_get($input, 'from', $promotion->from)));
                $promotion->to = date("Y-m-d H:i:s", strtotime(array_get($input, 'to', $promotion->to)));
                $promotion->discount_rate = $input['discount_rate'];
                $promotion->max_discount = !empty($input['max_discount']) ? $input['max_discount'] : null;
                $promotion->condition_ids = !empty($input['condition_ids']) ? $input['condition_ids'] : null;
                $promotion->description = array_get($input, 'description', $promotion->description);
                $promotion->type = array_get($input, 'type', $promotion->type);
                $promotion->point = $input['type'] == "POINT" ? $input['point'] : null;
                $promotion->ranking_id = $input['type'] == "RANKING" ? $input['ranking_id'] : null;
                $promotion->image_id = !empty($input['image_id']) ? $input['image_id'] : null;
                $promotion->updated_at = date('Y-m-d H:i:s', time());
                $promotion->updated_by = TM::getCurrentUserId();
                $promotion->save();
            } else {
                // Create Promotion
                $param = [
                    'title'         => $input['title'],
                    'code'          => $input['code'],
                    'from'          => date("Y-m-d H:i:s", strtotime(array_get($input, 'from'))),
                    'to'            => date("Y-m-d H:i:s", strtotime(array_get($input, 'to'))),
                    'discount_rate' => $input['discount_rate'],
                    'max_discount'  => array_get($input, 'max_discount'),
                    'condition_ids' => array_get($input, 'condition_ids'),
                    'description'   => array_get($input, 'description'),
                    'type'          => array_get($input, 'type'),
                    'point'         => $input['type'] == "POINT" ? $input['point'] : null,
                    'ranking_id'    => $input['type'] == "RANKING" ? $input['ranking_id'] : null,
                    'image_id'      => !empty($input['image_id']) ? $input['image_id'] : null,
                ];
                $promotion = $this->create($param);
            }

            // Create|Update Promotion Detail
//            $allPromotionDetail = PromotionDetail::model()->where('promotion_id', $promotion->id)->get()->toArray();
//            $allPromotionDetail = array_pluck($allPromotionDetail, 'id', 'id');
//            $allPromotionDetailDelete = $allPromotionDetail;
//            if (!empty($input['details'])) {
//                foreach ($input['details'] as $detail) {
//                    // Create Detail
//                    $param = [
//                        'promotion_id'    => $promotion->id,
//                        'product_id'      => !empty($detail['product_id']) ? $detail['product_id'] : null,
//                        'category_id'     => !empty($detail['category_id']) ? $detail['category_id'] : null,
//                        'qty'             => !empty($detail['qty']) ? $detail['qty'] : null,
//                        'point'           => !empty($detail['point']) ? $detail['point'] : null,
//                        'price'           => !empty($detail['price']) ? $detail['price'] : null,
//                        'sale_off'        => !empty($detail['sale_off']) ? $detail['sale_off'] : null,
//                        'gift_product_id' => !empty($detail['gift_product_id']) ? $detail['gift_product_id'] : null,
//                        'qty_gift'        => !empty($detail['qty_gift']) ? $detail['qty_gift'] : null,
//                        'price_gift'      => !empty($detail['price_gift']) ? $detail['price_gift'] : null,
//                        'discount'        => !empty($detail['discount']) ? $detail['discount'] : null,
//                        'qty_from'        => !empty($detail['qty_from']) ? $detail['qty_from'] : null,
//                        'qty_to'          => !empty($detail['qty_to']) ? $detail['qty_to'] : null,
//                        'customer_type'   => $detail['customer_type'],
//                        'note'            => !empty($detail['note']) ? $detail['note'] : null,
//                        'is_active'       => !empty($detail['is_active']) ? $detail['is_active'] : null,
//                    ];
//                    if (empty($detail['id']) || empty($allPromotionDetail[$detail['id']])) {
//                        // Create Detail
//                        $promotionDetail = new PromotionDetail();
//                        $promotionDetail->create($param);
//                        continue;
//                    }
//                    // Update
//                    $this->refreshModel();
//                    $param['id'] = $detail['id'];
//                    $detailModel = new PromotionDetailModel();
//                    $detailModel->update($param);
//                    unset($allPromotionDetailDelete[$detail['id']]);
//                }
//            }
//            if (!empty($allPromotionDetailDelete)) {
//                PromotionDetail::model()->whereIn('id', array_values($allPromotionDetailDelete))->delete();
//            }

            // Upload Image
//            if (!empty($input['image_upload'])) {
////                $imgUpload = explode(';base64,', $input['image_upload']);
////                if (empty($imgUpload[1])) {
////                    throw new \Exception(Message::get("V002", Message::get("image")));
////                }
////
////                $base64 = base64_decode($imgUpload[1]);
//                $imageId = $promotion->image_id;
//                $promotion->image_id = Image::uploadImage($base64);
//                $promotion->save();
//
//                if ($imageId && ($oldImage = Image::find($imageId))) {
//                    Image::removeImage($oldImage->code);
//                }
//            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $promotion;
    }

    /**
     * @param $orderId
     *
     * @return array
     */
    public function getPromotionsForOrder($orderId)
    {
        $now = date("Y-m-d H:i:s");
        // Promotions
        $promotions = Promotion::model()->with(['details'])
            ->where('from', '<=', $now)
            ->where('to', '>=', $now)
            ->where('is_active', '1')
            ->get()->toArray();

        if (empty($promotions)) {
            return [];
        }
        // All Product in Order
        $orderDetails = OrderDetail::model()->where('order_id', $orderId)->get()->toArray();
        $orderDetails = array_pluck($orderDetails, null, 'product_id');
        $promotionsOrder = [];
        foreach ($promotions as $promotion) {
            // Details
            $point = 0;
            $dt = [];
            $promotion['details'] = array_pluck($promotion['details'], null, 'product_id');
            foreach ($orderDetails as $productId => $orderDetail) {
                if (!empty($promotion['details'][$productId])) {
                    $pro = $promotion['details'][$productId];
                    if ($orderDetail['qty'] >= $pro['qty']) {
                        $number = floor($orderDetail['qty'] / $pro['qty']);
                        // Increase Point
                        $point += ($number * $pro['point']);
                        $dt[] = [
                            'product_id'      => $productId,
                            'order_qty'       => $orderDetail['qty'],
                            'condition_qty'   => $pro['qty'],
                            'number_allow'    => $number,
                            'condition_point' => $pro['point'],
                            'point'           => ($number * $pro['point']),
                        ];
                    }
                }
            }

            $promotionsOrder[] = [
                'promotion_id'    => $promotion['id'],
                'promotion_title' => $promotion['title'],
                'promotion_code'  => $promotion['code'],
                'point'           => $point,
                'details'         => $dt,
            ];
        }

        return $promotionsOrder;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (TM::getCurrentRole() != USER_ROLE_ADMIN) {
            $order_coupon = Order::model()->where('created_by',
                TM::getCurrentUserId())->get()->pluck('coupon_code')->toArray();
            $query->whereNotIn('id', array_filter($order_coupon));
        }
        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['title'])) {
            $query->where('title', 'like', "%{$input['title']}%");
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

    public function searchMyPromotion($input = [], $with = [], $limit = null)
    {
        $user = User::find(TM::getCurrentUserId());

        $query = $this->make($with);
        $this->sortBuilder($query, $input);

        $query->where('from', '<=', date('Y-m-d H:i:s', time()))
            ->where('to', '>=', date('Y-m-d H:i:s', time()));

        $order_coupon = Order::model()->where('customer_id', TM::getCurrentUserId())
            ->get()->pluck('coupon_code')->toArray();

        if (!empty($order_coupon)) {
            $query->whereNotIn('code', array_filter($order_coupon));
        }

        $query->where(function ($q) use ($user) {
            $q->where(function ($q1) use ($user) {
                $q1->where('point', '<=', (int)$user->point)
                    ->where('type', 'POINT');
            })->orWhere(function ($q2) use ($user) {
                $q2->where('ranking_id', (int)$user->ranking_id)
                    ->where('type', 'RANKING');
            })->orWhere('type', 'ALL');
        });

        if (!empty($input['code'])) {
            $query->where('code', 'like', "%{$input['code']}%");
        }
        if (!empty($input['title'])) {
            $query->where('title', 'like', "%{$input['title']}%");
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
