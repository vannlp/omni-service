<?php


namespace App\V1\Models;


use App\OrderStatus;
use App\Supports\Message;
use App\TM;

class OrderStatusModel extends AbstractModel
{
    /**
     * OrderStatusModel constructor.
     * @param OrderStatus|null $model
     */
    public function __construct(OrderStatus $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return bool|mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $orderStatus = OrderStatus::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
            if (empty($orderStatus)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $orderStatus->code        = array_get($input, 'code', $orderStatus->code);
            $orderStatus->name        = array_get($input, 'name', $orderStatus->name);
            $orderStatus->description = array_get($input, 'description', $orderStatus->description);
            $orderStatus->order       = array_get($input, 'order', $orderStatus->order);
            $orderStatus->company_id  = array_get($input, 'company_id', $orderStatus->company_id);
            $orderStatus->status_for  = array_get($input, 'status_for', $orderStatus->status_for);
            $orderStatus->is_active   = array_get($input, 'is_active', $orderStatus->is_active);
            $orderStatus->updated_at  = date("Y-m-d H:i:s", time());
            $orderStatus->updated_by  = TM::getCurrentUserId();
            $orderStatus->save();
        } else {
            $param       = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'description' => $input['description'] ?? null,
                'order'       => $input['order'] ?? null,
                'status_for'  => $input['status_for'] ?? null,
                'company_id'  => $input['company_id'],
                'is_active'   => $input['is_active'] ?? 1
            ];
            $orderStatus = $this->create($param);
        }
        return $orderStatus;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
//        $this->sortBuilder($query, $input);
        $query = $query->where('company_id', TM::getCurrentCompanyId());
        foreach ($input as $column => $value) {
            if (in_array($column, ['code', 'name', 'description', 'status_for', 'is_active']) && isset($value)) {
                $query = $query->where($column, 'like', "%{$value}%");
            }
        }
//        if (isset($input['is_active'])) {
//            $query = $query->where('is_active', $input['is_active']);
//        }
        $query = $query->orderBy('order', 'ASC');
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