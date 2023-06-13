<?php

namespace App\V1\Transformers\Poll;

use App\Poll;
use League\Fractal\TransformerAbstract;

class PollTransformer extends TransformerAbstract
{
    public function transform(Poll $poll)
    {
        $questions = $poll->questions->map(function ($item) {
            $answers = $item->answers->map(function ($answer) {
                return [
                    'answer_id' => $answer->id,
                    'text'      => $answer->text,
                    'score'     => $answer->score
                ];
            });

            return [
                'question_id' => $item->id,
                'title'       => $item->title,
                'status'      => $item->is_active,
                'answers'     => $answers
            ];
        });
        return [
            'poll_id'   => $poll->id,
            'code'      => $poll->code,
            'name'      => $poll->name,
            'questions' => $questions
        ];
    }
}
