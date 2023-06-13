<?php

namespace App\V1\Validators;

use App\Http\Validators\ValidatorBase;
use App\Supports\Message;

class PollPerformValidator extends ValidatorBase
{
    protected function rules()
    {
        return [
            'details' => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'details' => Message::get("details"),
        ];
    }
}
