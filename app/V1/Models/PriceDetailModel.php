<?php


namespace App\V1\Models;


use App\PriceDetail;
use App\Product;
use App\Supports\Message;

class PriceDetailModel extends AbstractModel
{
    /**
     * PriceDetailModel constructor.
     * @param PriceDetail|null $model
     */
    public function __construct(PriceDetail $model = null)
    {
        parent::__construct($model);
    }

    public function create(array $data)
    {
        $allProductPrice = Product::all()->pluck('price', 'id')->toArray();
        $allProductName = Product::all()->pluck('name', 'id')->toArray();
        if (!empty($data['details'])) {
            foreach ($data['details'] as $detail) {
                $priceId = $data['price_id'] ?? $detail['price_id'];
                $isCheck = PriceDetail::model()
                    ->where('price_id', $priceId)
                    ->where('product_id', $detail['product_id'])
                    ->first();
                if ($isCheck) {
                    throw new \Exception(Message::get("V008", "Sản phẩm " . $allProductName[$detail['product_id']]));
                }
                $param = [
                    'price_id'   => $priceId,
                    'product_id' => $detail['product_id'],
                    'from'       => $data['from'] ?? $detail['from'],
                    'to'         => $data['to'] ?? $detail['to'],
                    'price'      => !empty($data['price']) ? $data['price'] : (!empty($detail['product_price']) ? $detail['product_price'] : (!empty($detail['price_ex']) ? $detail['price_ex'] : $allProductPrice[$detail['product_id']])),
                    'status'     => $data['status'] ?? $detail['status'],
                ];
                $this->model->create($param);
            }
        }
    }
}