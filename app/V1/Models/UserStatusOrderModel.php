<?php
/**
 * User: dai.ho
 * Date: 10/14/2019
 * Time: 10:41 AM
 */

namespace App\V1\Models;


use App\TM;
use App\UserStatusOrder;

class UserStatusOrderModel extends AbstractModel
{
    public function __construct(UserStatusOrder $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $param = [
            'user_id'   => TM::getCurrentUserId(),
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