<?php


namespace App\V1\Controllers;


use App\NotificationHistory;
use App\Notify;
use App\Supports\Log;
use App\Supports\Message;
use App\TM;
use App\UserStatusNotification;
use App\V1\Models\NotificationHistoryModel;
use App\V1\Transformers\NotificationHistory\NotificationHistoryTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NotificationHistoryController extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new NotificationHistoryModel();
    }

    public function search(Request $request, NotificationHistoryTransformer $notificationHistoryTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $input['sort'] = $input['sort'] ?? [];
        $input['sort']['id'] = 'desc';
        $result = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($result, $notificationHistoryTransformer);
    }

    public function view($id, NotificationHistoryTransformer $notificationHistoryTransformer)
    {
        $result = NotificationHistory::model()->where([
            'id'         => $id,
            'company_id' => TM::getCurrentCompanyId(),
        ])->first();
        if (empty($result)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($result, $notificationHistoryTransformer);
    }

    public function read($id)
    {
        $notify = NotificationHistory::model()->where([
            'id'         => $id,
            'company_id' => TM::getCurrentCompanyId(),
        ])->first();
        if (empty($notify)) {
            return $this->responseError(Message::get("V002", "#$id"));
        }

        $userStatusNotification = UserStatusNotification::model()->where([
            'notification_id' => $id,
            'user_id'         => TM::getCurrentUserId(),
        ])->first();

        if (empty($userStatusNotification)) {
            $userStatusNotification = new UserStatusNotification();
        }

        $userStatusNotification->read = 1;
        $userStatusNotification->user_id = TM::getCurrentUserId();
        $userStatusNotification->notification_id = $id;
        $userStatusNotification->save();

        return ["message" => "Thank you. Notification Read!"];
    }

    public function readAll()
    {
        $myType = TM::getMyUserType();
        $noticeIds = Notify::model()->select('id')->whereIn('notify_for',
            ['ALL', $myType])->where('company_id', TM::getCurrentCompanyId())->get()->pluck('id')->toArray();
        $readIds = UserStatusNotification::model()->where('user_id',
            TM::getCurrentUserId())->get()->pluck('notification_id')->toArray();

        $notifies = NotificationHistory::model()->select([
            'id as notification_id',
            DB::raw(TM::getCurrentUserId() . " as user_id"),
            DB::raw('now() as created_at'),
        ])->where(function ($q) use ($noticeIds) {
            $q->where('created_by', TM::getCurrentUserId());
            $q->where('company_id', TM::getCurrentCompanyId());
            if (!empty($noticeIds)) {
                $q->orWhere(function ($q2) use ($noticeIds) {
                    $q2->where('notify_type', 'SYSTEM')->whereIn('item_id', $noticeIds);
                });
            }
        })->whereNotIn('id', $readIds)->get()->toArray();

        if (!empty($notifies)) {
            UserStatusNotification::insert($notifies);
        }

        return ["message" => "Thank you. Notification Read!"];
    }
}