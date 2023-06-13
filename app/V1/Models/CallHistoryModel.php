<?php


namespace App\V1\Models;


use App\CallHistory;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;

class CallHistoryModel extends AbstractModel
{
    /**
     * CategoryModel constructor.
     * @param CallHistory|null $model
     */
    public function __construct(CallHistory $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $callHistory = CallHistory::find($id);

                if (empty($callHistory)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }

                $callHistory->caller_id = array_get($input, 'caller_id', $callHistory->caller_id);
                $callHistory->receiver_id = array_get($input, 'receiver_id', $callHistory->receiver_id);
                $callHistory->call_from_time = array_get($input, 'call_from_time', $callHistory->call_from_time);
                $callHistory->call_end_time = array_get($input, 'call_end_time', $callHistory->call_end_time);
                $callHistory->total_time = array_get($input, 'total_time', $callHistory->total_time);
                $callHistory->vote = array_get($input, 'vote', $callHistory->vote);
                $callHistory->updated_at = date("Y-m-d H:i:s", time());
                $callHistory->updated_by = TM::getCurrentUserId();
                $callHistory->save();
            } else {
                $param = [
                    'caller_id'      => TM::getCurrentUserId(),
                    'receiver_id'    => array_get($input, 'receiver_id', NULL),
                    'call_from_time' => date('Y-m-d H:i:s', time()),
                    'call_end_time'  => date('Y-m-d H:i:s', time()),
                    'total_time'     => array_get($input, 'total_time', NULL),
                    'vote'           => array_get($input, 'vote', NULL),
                    'is_active'      => 1,
                ];

                $callHistory = $this->create($param);
            }

        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $callHistory;
    }
}