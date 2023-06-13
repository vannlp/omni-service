<?php


namespace App\V1\Validators\BlogPost;


use App\Http\Validators\ValidatorBase;
use App\Supports\Message;
use Illuminate\Validation\Rule;

class PostCreateValidator extends ValidatorBase
{
    protected function rules()
    {
        $input = $this->_input;
        return [
//            'website_id'   => 'required|exists:websites,id,deleted_at,NULL',
            'title'        => [
                'required',
                'string',
                Rule::unique('posts')->where(function ($query) use ($input) {
                    $query->where('title', $input['title'])
                        ->whereNull('deleted_at');
                })
            ],
            //            'thumbnail_url' => 'required',
            'content'      => 'required',
            'taxonomy_ids' => 'nullable|array',
            'status'       => 'required|in:published,draft,waiting_approval,closed,waiting_publish',
            'author'       => 'required',
            'publish_date' => 'required|date_format:Y-m-d H:i:s',
            'post_type'    => 'required|exists:post_types,code,deleted_at,NULL',
        ];
    }

    protected function attributes()
    {
        return [
            'title'        => Message::get("title"),
            'content'      => Message::get("content_post"),
            'thumbnail'    => Message::get("thumbnail"),
            'website_id'   => Message::get("website_id"),
            'taxonomy_ids' => Message::get("taxonomies"),
            'status'       => Message::get("status"),
            'author'       => Message::get("author"),
            'publish_date' => Message::get("publish_date"),
            'post_type'    => Message::get("post_type"),
        ];
    }
}