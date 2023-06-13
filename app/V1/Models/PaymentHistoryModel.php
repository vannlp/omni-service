<?php


namespace App\V1\Models;


use App\TM;

class PaymentHistoryModel extends AbstractModel
{
    public function __construct($model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (TM::getCurrentRole() != USER_ROLE_ADMIN) {
            $query = $query->where('user_id', TM::getCurrentUserId());
        }
        if (!empty($input['user_id'])) {
            $query = $query->where('user_id', $input['user_id']);
        }
        if (!empty($input['status'])) {
            $query = $query->where('status', $input['status']);
        }
        if (!empty($input['type'])) {
            $query = $query->where('type', $input['type']);
        }
        if (!empty($input['content'])) {
            $query = $query->where('content', 'like', "%{$input['content']}%");
        }
        if (!empty($input['date'])) {
            $query = $query->whereDate('date', date('Y-m-d', strtotime($input['date'])));
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