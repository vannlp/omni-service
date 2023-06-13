<?php


namespace App\V1\Controllers;


use App\NotificationHistory;
use App\Notify;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\V1\Models\NotifyModel;
use App\V1\Validators\NotifyUpsertValidator;
use App\V1\Transformers\Notify\NotifyTransformer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class NotifyController extends BaseController
{
    protected $model;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->model = new NotifyModel();
    }


    /**
     * @param Request $request
     * @param NotifyTransformer $notifyTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, NotifyTransformer $notifyTransformer)
    {
        $input  = $request->all();
        $limit  = array_get($input, 'limit', 20);
        $notify = $this->model->search($input, [], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($notify, $notifyTransformer);
    }

    public function detail($id, NotifyTransformer $notifyTransformer)
    {
        $notify = Notify::model()->where(['id' => $id, 'company_id' => TM::getCurrentCompanyId()])->first();
        if (empty($notify)) {
            return ["data" => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($notify, $notifyTransformer);
    }

    public function create(
        Request               $request,
        NotifyUpsertValidator $notifyUpsertValidator
    )
    {
        $input = $request->all();
        $notifyUpsertValidator->validate($input);
        $input['title'] = str_clean_special_characters($input['title']);
        $notifyUpsertValidator->validate($input);
        if ($input['frequency'] != 'ASAP' && empty($input['delivery_date'])) {
            return $this->response->errorBadRequest(Message::get("V001", "Delivery Date"));
        }
        try {
            DB::beginTransaction();
            $notify = $this->model->upsert($input);
            Log::create($this->model->getTable(), "#ID:" . $notify->id);

            if ($input['frequency'] == "ASAP") {
                $this->sendNotification($notify);
            }
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("notifies.create-success", $notify->title)];
    }

    /**
     * @param $id
     * @param Request $request
     * @param NotifyUpsertValidator $notifyUpsertValidator
     * @param NotifyTransformer $notifyTransformer
     * @return array|void
     */
    public function update(
        $id,
        Request $request,
        NotifyUpsertValidator $notifyUpsertValidator,
        NotifyTransformer $notifyTransformer
    )
    {
        $input       = $request->all();
        $input['id'] = $id;
        $notifyUpsertValidator->validate($input);
        if ($input['frequency'] != 'ASAP' && empty($input['delivery_date'])) {
            return $this->response->errorBadRequest(Message::get("V001", "Delivery Date"));
        }
        try {
            $notify = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $notify->id);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($notify, $notifyTransformer);
    }

    /**
     * @param $id
     * @return array|void
     */
    public function delete($id)
    {
        try {
            DB::beginTransaction();
            $notify = NotificationHistory::find($id);
            if (empty($notify)) {
                return $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
            }

            $notify->delete();
            Log::delete($this->model->getTable(), "#ID:" . $notify->id);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Message::get("notifies.delete-success", $notify->title)];
    }

    private function sendNotification(Notify $notify)
    {
        $users = User::model()->select(['users.id', 'us.device_token'])
            ->join('user_sessions as us', 'us.user_id', '=', 'users.id')
            ->where('us.device_token', '!=', '')
            ->where('company_id', TM::getCurrentCompanyId())
            ->whereNotNull('us.device_token');
        if (empty($notify->user_id)) {
            if ($notify->notify_for && $notify->notify_for != "ALL") {
                $users = $users->where('users.type', $notify->notify_for);
            }
        } else {
            $users = $users->where('users.id', $notify->user_id);
        }


        $users = $users->groupBy('device_token')->get()->pluck('device_token')->toArray();

        if ($users) {
            // Send Notification
            $body    = [
                'target_id' => $notify->target_id,
                'type'      => $notify->type,
                'title'     => $notify->title,
                'body'      => $notify->body,
            ];
            $fields  = [
                'data'             => [
                    'type'         => 'NOTIFICATION',
                    'body'         => json_encode($body),
                    "click_action" => "FLUTTER_NOTIFICATION_CLICK",
                ],
                'notification'     => ['title' => $notify->title, 'sound' => 'shame', 'body' => $notify->body],
                'registration_ids' => $users,
            ];
            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, env('FIREBASE_URL', ''));
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));

                $result = curl_exec($ch);
                if ($result === false) {
                    throw new \Exception('FCM Send Error: ' . curl_error($ch));
                }
                curl_close($ch);
            } catch (\Exception $ex) {
                throw $ex;
            }

            // Save History
            $param = [
                'title'       => $notify->title,
                'body'        => $notify->body,
                'message'     => $notify->title,
                'notify_type' => "SYSTEM",
                'type'        => $notify->type,
                'extra_data'  => json_encode((array)$notify),
                'receiver'    => $notify->notify_for,
                'action'      => 1,
                'item_id'     => $notify->id,
                'company_id'  => TM::getCurrentCompanyId(),
            ];

            // Create Notification History
            $notificationHistoryModel = new \App\V1\Models\NotificationHistoryModel();
            $notificationHistoryModel->create($param);
        }
    }
}