<?php


namespace App\V1\Models;


use App\NotificationHistory;
use App\TM;
use App\UserStatusNotification;

class NotificationHistoryModel extends AbstractModel
{
    public function __construct(NotificationHistory $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        if (!empty($input['type'])) {
            $query = $query->where('type', $input['type']);
        }
        if (TM::getCurrentRole() != USER_ROLE_ADMIN) {
            $query = $query->where(function ($q) {
                $q->where('notify_type', 'SYSTEM')
                    ->orWhere('created_by', TM::getCurrentUserId());
            });
            if (!empty($input['read'])) {
                $readIds = UserStatusNotification::model()->select('notification_id as id')->where('user_id',
                    TM::getCurrentUserId())->get()->pluck('id')->toArray();
                if ($readIds) {
                    $query = $query->whereIn('id', $readIds);
                }
            }
        }
        $query = $query->where('company_id', TM::getCurrentCompanyId());
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->paginate();
        }
    }
}