<?php

namespace App\V1\Transformers\Poll;

use App\Poll;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class PollPerformTransformer extends TransformerAbstract
{
    public function transform(Poll $poll)
    {
        $totalPerformer = count($poll->performers);
        $details = $poll->performers->map(function ($item) {
            return [
                'perform_id'  => $item->id,
                'object_name' => $item->object_name,
                'object_code' => $item->object_code,
                'score'       => $item->score,
                'date'        => Carbon::parse($item->created_at)->format('d/m/Y'),
            ];
        });

        return [
            'poll_name'      => $poll->name,
            'total_perfomer' => $totalPerformer,
            'details'        => $details,
        ];
    }
}
