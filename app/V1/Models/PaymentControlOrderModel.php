<?php
/**
 * User: kpistech2
 * Date: 2020-07-02
 * Time: 22:47
 */

namespace App\V1\Models;


use App\Order;
use App\PaymentControlOrder;
use App\Supports\Message;
use App\TM;
use Illuminate\Support\Facades\DB;

class PaymentControlOrderModel extends AbstractModel
{
    public function __construct(PaymentControlOrder $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $order = Order::model()->where('id', $input['order_id'])->first();
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            /** @var PaymentControlOrder $item */
            $item = PaymentControlOrder::find($id);
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->order_id = $input['order_id'];
            $item->order_code = $order->code;
            $item->order_price = $order->total_price;
            $item->payment_price = $input['payment_price'];
            $item->price_diff = $input['payment_price'] - $order->total_price;
            $item->control_date = date('Y-m-d');
            $item->payment_type = !empty($input['payment_type']) ? $input['payment_type'] : 'CASH';
            $item->account_name = !empty($input['account_name']) ? $input['account_name'] : null;
            $item->account_number = !empty($input['account_number']) ? $input['account_number'] : null;
            $item->payment_date = !empty($input['payment_date']) ? date("Y-m-d", strtotime($input['payment_date'])) : $item->payment_date;
            $item->store_id = TM::getCurrentStoreId() ?? $order->store_id;
            $item->company_id = TM::getCurrentCompanyId();
            $item->updated_at = date("Y-m-d H:i:s", time());
            $item->updated_by = TM::getCurrentUserId();
            $item->save();
        } else {
            $param = [
                'order_id'       => $input['order_id'],
                'order_code'     => $order->code,
                'order_price'    => $order->total_price,
                'payment_price'  => $input['payment_price'],
                'price_diff'     => $input['payment_price'] - $order->total_price,
                'control_date'   => date('Y-m-d'),
                'payment_type'   => !empty($input['payment_type']) ? $input['payment_type'] : 'CASH',
                'account_name'   => !empty($input['account_name']) ? $input['account_name'] : null,
                'account_number' => !empty($input['account_number']) ? $input['account_number'] : null,
                'payment_date'   => !empty($input['payment_date']) ? date("Y-m-d", strtotime($input['payment_date'])) : null,
                'store_id'       => TM::getCurrentStoreId() ?? $order->store_id,
                'company_id'     => TM::getCurrentCompanyId(),
                'is_active'      => 1,
            ];

            $item = $this->create($param);
        }

        return $item;
    }

    public function getOverview()
    {
        $totalOrder = Order::model()->select([
            DB::raw("count(id) as total_order"),
            DB::raw("sum(total_price) as total_price")
        ])->where('store_id', TM::getCurrentStoreId())->first();

        $controlOrder = PaymentControlOrder::model()->select([
            DB::raw("count(id) as total_order"),
            DB::raw("sum(payment_price) as total_price")
        ])->where('store_id', TM::getCurrentStoreId())->first();

        $total_order = $totalOrder->total_order ?? 0;
        $control_order = $controlOrder->total_order ?? 0;
        $total_price = $totalOrder->total_price ?? 0;
        $control_price = $controlOrder->total_price ?? 0;

        return [
            'total_order'      => $control_order . "/" . $total_order,
            'total_order_text' => number_format($control_order) . "/" . number_format($total_order),
            'total_price'      => $control_price . "/" . $total_price,
            'total_price_text' => number_format($control_price) . "đ/" . number_format($total_price) . "đ",
        ];
    }
}