<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Validation\Rule;

class PostTypeCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'website_id' => 'required|exists:websites,id,deleted_at,NULL',
            'code'       => [
                'required',
                Rule::unique('post_types')->where('code', $input['code'])
                    ->where('website_id', $input['website_id'])
                    ->whereNull('deleted_at')
            ],
            'name'       => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'name'       => Message::get("alternative_name"),
            'code'       => Message::get("code"),
            'website_id' => Message::get("website_id"),
        ];
    }
}