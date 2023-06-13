<?php


namespace App\V1\Models;


use App\ProductUser;
use App\Supports\Message;
use App\TM;

class ProductUserModel extends AbstractModel
{
    public function __construct(ProductUser $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $productUser = ProductUser::find($id);
            if (empty($productUser)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $stock = array_get($input, 'stock', $productUser->stock);
            $total_qty = array_get($input, 'total_qty', $productUser->total_qty);
            if ($stock > $total_qty) {
                throw new \Exception(Message::get("V036"));
            }
            $productUser->user_id = array_get($input, 'user_id', $productUser->user_id);
            $productUser->product_id = array_get($input, 'product_id', $productUser->product_id);
            $productUser->stock = $stock;
            $productUser->total_qty = $total_qty;
            $productUser->updated_at = date("Y-m-d H:i:s", time());
            $productUser->updated_by = TM::getCurrentUserId();
            $productUser->save();
        } else {
            $allProductUser = ProductUser::model()->where('user_id', $input['user_id'])->get()->toArray();
            $allProductUser = array_pluck($allProductUser, 'product_id', 'product_id');
            foreach ($input['details'] as $detail) {
                if (!empty($allProductUser[$detail['product_id']])) {
                    throw new \Exception(Message::get("V037", $detail['product_id']));
                }
                if ($detail['stock'] > $detail['total_qty']) {
                    throw new \Exception(Message::get("V036"));
                }
                $productUser = new ProductUser();
                $productUser->create([
                    'user_id'    => $input['user_id'],
                    'product_id' => $detail['product_id'],
                    'stock'      => $detail['stock'],
                    'total_qty'  => $detail['total_qty'],
                    'is_active'  => 1,
                ]);
            }
        }
        return $productUser;
    }
}