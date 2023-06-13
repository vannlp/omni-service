<?php


namespace App\V1\Transformers\Consultant;


use App\CallHistory;
use App\Consultant;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class ConsultantTransformer extends TransformerAbstract
{
    public function transform(Consultant $consultant)
    {
        try {
            $video_call_account = object_get($consultant, 'consultantVideoCallAccount', null);
            if (empty($video_call_account)) {
                $video_call_account = null;
            } else {
                $video_call_account = [
                    'user_id'  => $video_call_account['user_id'],
                    'phone'    => $video_call_account['phone'],
                    'password' => $video_call_account['password']
                ];
            }
            return [
                'id'                 => $consultant->id,
                'user_id'            => $consultant->user_id,
                'user_code'          => object_get($consultant, 'user.code', null),
                'user_name'          => object_get($consultant, 'user.profile.full_name', null),
                'email'              => object_get($consultant, 'user.profile.email', null),
                'title'              => $consultant->title,
                'company_id'         => $consultant->company_id,
                'consultant_id'      => $consultant->consultant_id,
                'ext'                => !empty($consultant->ext) ? date('d/m/Y H:i', strtotime($consultant->ext)) : null,
                'updated_at'         => !empty($consultant->updated_at) ? date('d/m/Y H:i', strtotime($consultant->updated_at)) : null,
                'consultant_account' => $video_call_account,
                'is_online'          => $consultant->is_online,
                'total_vote'         => $this->getTotalVote($consultant->user_id)
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }

    private function getTotalVote($id)
    {
        $sumVote = CallHistory::model()
            ->where('call_histories.receiver_id', $id)
            ->sum('vote');
        $countCall = $this->getTotalCall($id);
        if ($sumVote == 0 || $countCall == 0) {
            return 0;
        }
        $result = $sumVote / $countCall;
        return round($result, 1) ?? 0;
    }

    private function getTotalCall($id)
    {
        $countCall = CallHistory::model()
            ->where('call_histories.receiver_id', $id)
            ->count('id');
        return $countCall;
    }
}