<?php

namespace App\V1\Transformers\Poll;

use App\Poll;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;

class PollPerformDetailTransformer extends TransformerAbstract
{
    protected $performId;

    public function __construct($performId)
    {
        $this->performId = $performId;
    }

    public function transform(Poll $poll)
    {
        $perform = $poll->performers()->find($this->performId);
        $performAnswers = json_decode($perform->details_json, true);
        $details = $poll->questions->map(function ($question) use ($performAnswers) {
            $performAnswer = array_first($performAnswers, function ($item) use ($question) {
                if ($item['answer_id'] && $item['question_id'] == $question->id) {
                    return $item;
                }
            });
            $answers = $question->answers->map(function ($answer) use ($performAnswer) {
                return [
                    'text'   => $answer->text,
                    'choose' => $performAnswer['answer_id'] == $answer->id ? true : false,
                ];
            });

            return [
                "title"   => $question->title,
                "answers" => $answers,
            ];
        });

        return [
            "poll_name"    => $poll->name,
            "perform_name" => $perform->object_name,
            "score"        => $perform->score,
            "date"         => Carbon::parse($perform->created_at)->format('d/m/Y'),
            "details"      => $details
        ];
    }
}
