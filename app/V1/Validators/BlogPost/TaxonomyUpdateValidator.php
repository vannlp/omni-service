<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Validation\Rule;

class TaxonomyUpdateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
            'id'         => 'required|exists:taxonomies,id,deleted_at,NULL',
            'website_id' => 'required|exists:websites,id,deleted_at,NULL',
            'name'       => [
                'required',
                Rule::unique('taxonomies')->where('name', $input['name'])
                    ->where('website_id', $input['website_id'])
                    ->whereNot('id', $input['id'])
                    ->whereNull('deleted_at')
            ]
        ];
    }

    protected function attributes()
    {
        return [
            'id'         => Message::get("id"),
            'name'       => Message::get("alternative_name"),
            'website_id' => Message::get("website_id"),
            //            'blog_id' => Message::get("blog_id"),
        ];
    }
}