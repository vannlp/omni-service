<?php


namespace App\V1\Controllers;


use App\Order;
use App\Profile;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserSession;
use App\V1\Models\ProfileModel;
use App\V1\Validators\AcceptOrderValidator;
use Illuminate\Http\Request;

class PartnerController extends BaseController
{
    /**
     * @var
     */
    protected $model;

    public function __construct()
    {
        $this->model = new ProfileModel();
    }

    public function search(Request $request)
    {
        $input = $request->all();
        return $this->model->nearestPartners($input);
    }

    /**
     * Find the nearest partner
     *
     * @param $id
     */
    public function view($id)
    {
        try {
            $order = Order::find($id);
            if (empty($order)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }

            $latlong = explode(",", $order->latlong);
            $input['lat'] = $latlong[0];
            $input['long'] = $latlong[1];

            $partner = $this->model->nearestPartner($input);
            if (empty($partner)) {
                throw new \Exception(Message::get("V034"));
            }
            $this->sendMessageToPartner($partner, $order);
            $order->status = ORDER_STATUS_IN_PROGRESS;
            $order->save;
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $order;
    }

    /**
     * @param $partner
     * @param $order
     * @throws \Exception
     */
    private function sendMessageToPartner($partner, $order)
    {
        try {
            //Get Device
            $userSession = UserSession::model()->where('user_id', $partner->user_id)->first();
            $device = $userSession->device_token;
            if (empty($device)) {
                throw new \Exception(Message::get("V032", "#$device"));
            }

            $title = Message::get("V033");
            $notification = [
                'title' => $title,
                'body'  => Message::get("V021", $order->code),
                'sound' => 'shame',
            ];
            $data = [
                'message'      => $title,
                'body'         => Message::get("V021", $order->code),
                'type'         => 1,
                'extra_data'   => '', // anyType
                'receiver'     => $device,
                'action'       => 1,
                "click_action" => "FLUTTER_NOTIFICATION_CLICK",
            ];
            $fields = ['data' => $data, 'notification' => $notification, 'to' => $device];
            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];

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
    }

    /**
     * Update order status
     *
     * @param $id
     * @param Request $request
     * @param AcceptOrderValidator $acceptOrderValidator
     * @throws \Exception
     */
    public function update($id, Request $request, AcceptOrderValidator $acceptOrderValidator)
    {
        $input = $request->all();
        $acceptOrderValidator->validate($input);
        $order = Order::find($id);
        if (empty($order)) {
            throw new \Exception(Message::get("V003", "ID: #$id"));
        }
        if ($input['type'] == 0) {
            throw new \Exception(Message::get("V034"));
        } else {
            $partner = Profile::model()->where('user_id', TM::getCurrentUserId())->first();
            $this->sendMessageToCustomer($order, $partner);

            $order->status = ORDER_STATUS_ASSIGNED;
            $order->partner_id = TM::getCurrentUserId();
            $order->save();
        }
    }

    /**
     * @param $order
     * @param $partner
     * @throws \Exception
     */
    private function sendMessageToCustomer($order, $partner)
    {
        try {
            //Get Device
            $userSession = UserSession::model()->where('user_id', $order->customer_id)->first();
            $device = $userSession->device_token;
            if (empty($device)) {
                throw new \Exception(Message::get("V032", "#$device"));
            }

            $title = Message::get("V021", $order->code);
            $notification = [
                'title' => $title,
                'body'  => Message::get("V035"),
                'sound' => 'shame',
            ];
            $data = [
                'message'       => $title,
                'body'          => Message::get("V035"),
                'type'          => 1,
                'extra_data'    => '', // anyType
                'receiver'      => $device,
                'action'        => 1,
                'partner_name'  => $partner->full_name,
                'partner_lat'   => $partner->lat,
                'partner_long'  => $partner->long,
                'partner_phone' => $partner->phone,
                "click_action"  => "FLUTTER_NOTIFICATION_CLICK",
            ];
            $fields = ['data' => $data, 'notification' => $notification, 'to' => $device];
            $headers = ['Content-Type:application/json', 'Authorization:key=' . env("FIREBASE_SERVER_KEY", '')];

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
    }

    public function recent(Request $request)
    {
        $input = $request->all();
        if (empty($input['lat']) || empty($input['lat'])) {
            return $this->responseError(Message::get("V001", "Latitude, Longitude"));
        }

        if (!is_numeric($input['lat']) || !is_numeric($input['lat'])) {
            return $this->responseError(Message::get("V002", "Latitude, Longitude"));
        }
        try {
            $data = $this->getPartnerRecent($input['lat'], $input['long']);

            return response()->json(['status' => 'success', 'data' => $data]);
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function getPartnerRecent($lat, $long, $limit = 10)
    {
        if (empty($lat) || empty($long)) {
            return null;
        }
        $partners = Profile::selectRaw("us.socket_id, profiles.user_id, profiles.full_name, profiles.phone, profiles.lat, profiles.long, 
        6378137 * (
            2 * ATAN2(
                SQRT(
                    SIN(((profiles.lat - $lat)* PI() / 180) / 2) * SIN(((profiles.lat - $lat)* PI() / 180) / 2) +
                    COS(($lat)* PI() / 180) * COS((profiles.lat)* PI() / 180) * SIN(((profiles.long - $long) * PI() / 180) / 2) * SIN(((profiles.long - $long) * PI() / 180) / 2)
                ), SQRT(
                    1 - (
                        SIN(((profiles.lat - $lat)* PI() / 180) / 2) * SIN(((profiles.lat - $lat)* PI() / 180) / 2) +
                        COS(($lat)* PI() / 180) * COS((profiles.lat)* PI() / 180) * SIN(((profiles.long - $long) * PI() / 180) / 2) * SIN(((profiles.long - $long) * PI() / 180) / 2)
                    )
                )
            )
        ) as distance")
            ->join('users as u', 'u.id', '=', 'profiles.user_id')
            ->join('user_sessions as us', function ($q) {
                $q->on('us.user_id', '=', 'u.id')
                    ->where('us.deleted', '0')
                    ->whereNotNull('us.socket_id')
                    ->whereNotNull('us.device_token')
                    ->where('us.socket_id', '!=', '')
                    ->where('us.device_token', '!=', '')
                    ->whereNull('us.deleted_at');
            })
            ->whereNull('u.deleted_at')
            ->whereNotNull('profiles.lat')
            ->whereNotNull('profiles.long')
            ->where('profiles.lat', '!=', '0')
            ->where('profiles.long', '!=', '0')
            ->where('profiles.ready_work', 1)
            ->where('profiles.is_active', 1)
            ->where('u.type', USER_TYPE_PARTNER)
            ->having('distance', '<=', '50000')
            ->orderByRaw("distance ASC");

        if ($limit) {
            if ($limit === 1) {
                return $partners->first();
            }

            $partners = $partners->limit($limit);
        }

        return $partners->get();
    }
}