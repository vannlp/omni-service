<?php
/**
 * User: dai.ho
 * Date: 3/06/2020
 * Time: 10:21 AM
 */

namespace App\V1\Models;


use App\EnterpriseOrder;
use App\EnterpriseOrderDetail;
use App\Order;
use App\OrderDetail;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\DB;

class EnterpriseOrderModel extends AbstractModel
{
    public function __construct(EnterpriseOrder $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $orderId
     * @param $input
     * @return Order
     * @throws \Exception
     */
    public function createEnterprises($orderId, $input)
    {
        /** @var Order $order */
        $order = Order::model()->where('id', $orderId)->whereNotIn('status', [
            ORDER_STATUS_NEW,
            ORDER_STATUS_COMPLETED,
        ])->first();
        if (!$order) {
            throw new \Exception(Message::get("V003", Message::get("orders") . " #$orderId"));
        }

        // Validate
        $enterprises = [];
        $now = date("Y-m-d H:i:s", time());
        foreach ($input['details'] as $detail) {
            $order_detail = OrderDetail::model()->where([
                'order_id'   => $orderId,
                'product_id' => $detail['product_id'],
                'id'         => $detail['order_detail_id'],
            ])->first();
            if (!$order_detail) {
                throw new \Exception(Message::get("V008", Message::get("products") . ": #{$detail['product_id']}"));
            }

            $count_product = EnterpriseOrderDetail::model()->select(DB::raw("sum(qty) as qty"))
                ->where('order_detail_id', $detail['order_detail_id'])->first();
            if (!empty($count_product) && ($count_product->qty + $detail['qty']) > $order_detail->qty) {
                throw new \Exception(Message::get("V051", Message::get("products") . ": #{$detail['product_id']}"));
            }
            $enterprises[$detail['enterprise_id']][] = [
                'order_detail_id' => $detail['order_detail_id'],
                'product_id'      => $detail['product_id'],
                'qty'             => !empty($detail['qty']) ? $detail['qty'] : null,
                'price'           => !empty($detail['price']) ? $detail['price'] : null,
                'real_price'      => !empty($detail['real_price']) ? $detail['real_price'] : null,
                'price_down'      => !empty($detail['price_down']) ? $detail['price_down'] : null,
                'total'           => !empty($detail['total']) ? $detail['total'] : null,
                'created_at'      => $now,
                'created_by'      => TM::getCurrentCompanyId(),
            ];
        }

        foreach ($enterprises as $enterprise_id => $enterprise) {
            // Create Enterprise Order
            $enterprise_order = $this->create([
                'order_id'         => $orderId,
                'code'             => $this->generateEnterpriseCode($order->code),
                'status'           => $order->status,
                'enterprise_id'    => $enterprise_id,
                'shipping_address' => array_get($input, 'shipping_address'),
                'created_date'     => $now,
            ]);

            // Enterprise Order Detail
            foreach ($enterprise as $item) {
                $item['enterprise_order_id'] = $enterprise_order->id;
                $enterprise_detail_model = new EnterpriseOrderDetailModel();
                $enterprise_detail_model->create($item);

                // Update Order Detail
                OrderDetail::model()->where('id', $item['order_detail_id'])->update([
                    'enterprise_id' => $enterprise_id,
                ]);
            }
        }

        return $order;
    }

    private function generateEnterpriseCode($order_code)
    {
        $order_code = strtoupper($order_code);
        $last_enterprise = EnterpriseOrder::model()->where('code', 'like', "$order_code-M-%")->orderBy('id',
            'desc')->first();
        if (!$last_enterprise) {
            return $order_code . "-M-01";
        }

        $codes = explode("-M-", $last_enterprise->code);
        $index = (int)($codes[1] ?? 0);

        return $order_code . "-M-" . str_pad(++$index, 2, "0", STR_PAD_LEFT);
    }
}
