<?php

namespace App\V1\Transformers\Poll;

use App\Poll;
use League\Fractal\TransformerAbstract;

class PollsTransformer extends TransformerAbstract
{
    public function transform(Poll $poll)
    {
        $totalQuestion = count($poll->questions);
        $totalPerformer = count($poll->performers);

        return [
            'poll_id'         => $poll->id,
            'code'            => $poll->code,
            'name'            => $poll->name,
            'total_question'  => $totalQuestion,
            'total_performer' => $totalPerformer,
            'status'          => $poll->is_active
        ];
    }
}
