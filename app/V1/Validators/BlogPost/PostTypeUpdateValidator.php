<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Validation\Rule;

class PostTypeUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'id'         => 'required|exists:post_types,id,deleted_at,NULL',
            'code'       => [
                'required',
                Rule::unique('post_types')->where('code', $input['code'])
                    ->where('website_id', $input['website_id'])
                    ->whereNot('id', $input['id'])
                    ->whereNull('deleted_at')
            ],
            'website_id' => 'required|exists:websites,id,deleted_at,NULL',
            'name'       => 'required',
        ];
    }

    protected function attributes()
    {
        return [
            'id'         => Message::get("id"),
            //            'website_id' => Message::get("website_id"),
            'name'       => Message::get("alternative_name"),
            'code'       => Message::get("code"),
            'website_id' => Message::get("website_id"),
        ];
    }
}