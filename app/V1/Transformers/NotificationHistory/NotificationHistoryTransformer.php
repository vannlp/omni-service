<?php


namespace App\V1\Transformers\NotificationHistory;


use App\NotificationHistory;
use App\Supports\TM_Error;
use App\TM;
use App\UserStatusNotification;
use League\Fractal\TransformerAbstract;

class NotificationHistoryTransformer extends TransformerAbstract
{
    public function transform(NotificationHistory $notificationHistory)
    {
        try {
            $userStatus = $notificationHistory->userStatus(TM::getCurrentUserId());
            $readIds = UserStatusNotification::model()->select('notification_id as id')->where('user_id',
                TM::getCurrentUserId())->get()->pluck('id')->toArray();
            $notificationUnRead = NotificationHistory::model()->whereNotIn('id', $readIds);
            $notificationUnRead = $notificationUnRead->where(function ($q) {
                $q->where('notify_type', 'SYSTEM')
                    ->orWhere('created_by', TM::getCurrentUserId());
            });
            $notificationUnRead = $notificationUnRead->count();
            return [
                'id'          => $notificationHistory->id,
                'title'       => $notificationHistory->title,
                'body'        => $notificationHistory->body,
                'message'     => $notificationHistory->message,
                'notify_type' => $notificationHistory->notify_type,
                'type'        => $notificationHistory->type,
                'extradata'   => $notificationHistory->extradata,
                'receiver'    => $notificationHistory->receiver,
                'action'      => $notificationHistory->action,
                'item_id'     => $notificationHistory->item_id,
                'read'        => $userStatus->read ?? 0,
                'unread'      => $notificationUnRead,
                'target_id'   => object_get($notificationHistory, 'notify.target_id', null),
                'created_at'  => date('d-m-Y', strtotime($notificationHistory->created_at)),
                'updated_at'  => strtotime($notificationHistory->updated_at) > 0 ? date('d-m-Y',
                    strtotime($notificationHistory->updated_at)) : '',
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}