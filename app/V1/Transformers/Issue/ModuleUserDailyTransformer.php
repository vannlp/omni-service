<?php


namespace App\V1\Transformers\Issue;


use App\Issue;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ModuleUserDailyTransformer extends TransformerAbstract
{
    public function transform(Issue $issue)
    {
        try {
            return [
                'user_name'  => object_get($issue, 'user.profile.full_name', null),
                'issueToday' => $issue->issueToday,
                'workTime'   => $issue->workTime,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}