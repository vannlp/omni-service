<?php


namespace App\V1\Models;


use App\OrderHistory;
use App\Profile;
use App\TM;

class OrderHistoryModel extends AbstractModel
{
    public function __construct(OrderHistory $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $param = [
            'order_id'  => $input['order_id'],
            'status'    => $input['status'],
            'is_active' => 1,
        ];
        $order_histories = $this->create($param);
        return $order_histories;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $query->groupBy('user_id');
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
