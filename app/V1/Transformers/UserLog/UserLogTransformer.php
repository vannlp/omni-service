<?php


namespace App\V1\Transformers\UserLog;


use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\UserLog;
use League\Fractal\TransformerAbstract;

class UserLogTransformer extends TransformerAbstract
{
    public function transform(UserLog $userLog)
    {
        try {
            $name = trim(object_get($userLog, 'user.profile.full_name', null));
            return [
                'user_name'  => $name,
                'action'     => Message::get("L-" . $userLog->action),
                'full_message' => Log::message($name, $userLog->action, $userLog->target, $userLog->description),
                'updated_at' => $userLog->updated_at->format('Y-m-d H:i:s'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}