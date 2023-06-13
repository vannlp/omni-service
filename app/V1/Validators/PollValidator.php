<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PollValidator extends ValidatorBase
{
    protected $pollId;

    public function __construct($pollId = null)
    {
        $this->pollId = $pollId;
    }

    protected function rules()
    {
        if ($this->pollId) {
            return [
                'code'                        => 'required|unique:polls,id,' . $this->pollId,
                'name'                        => 'required',
                'questions'                   => 'required',
                'questions.*.title'           => 'required',
                'questions.*.answers'         => 'required',
                'questions.*.answers.*.text'  => 'required',
                'questions.*.answers.*.score' => 'required',
            ];
        } else {
            return [
                'code'                        => 'required|unique:polls',
                'name'                        => 'required',
                'questions'                   => 'required',
                'questions.*.title'           => 'required',
                'questions.*.answers'         => 'required',
                'questions.*.answers.*.text'  => 'required',
                'questions.*.answers.*.score' => 'required',
            ];
        }
    }

    protected function attributes()
    {
        return [
            'code'                        => Message::get("poll_code"),
            'name'                        => Message::get("poll_name"),
            'questions'                   => Message::get("questions"),
            'questions.*.title'           => Message::get("title"),
            'questions.*.answers'         => Message::get("answers"),
            'questions.*.answers.*.text'  => Message::get("text_answer"),
            'questions.*.answers.*.score' => Message::get("score_answer"),
        ];
    }
}
