<?php


namespace App\V1\Models;


use App\PostComment;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;

class PostCommentModel extends AbstractModel
{
    public function __construct(PostComment $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $postComment = PostComment::find($id);
                if (empty($postComment)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $postComment->content = array_get($input, 'content', $postComment->content);
                $postComment->post_id = array_get($input, 'post_id', $postComment->post_id);
                $postComment->parent_id = array_get($input, 'parent_id', $postComment->parent_id);
                $postComment->like = array_get($input, 'like', $postComment->like);
                $postComment->count_like = array_get($input, 'count_like', $postComment->count_like);
                $postComment->website_id = array_get($input, 'website_id', $postComment->website_id);
                $postComment->save();
            } else {
                $param = [
                    'content'    => $input['content'],
                    'parent_id'  => array_get($input, 'parent_id', null),
                    'like'       => array_get($input, 'like', null),
                    'count_like' => array_get($input, 'count_like', null),
                    'website_id' => $input['website_id'],
                    'created_by' => TM::getCurrentUserId(),
                    'post_id'    => $input['post_id']
                ];
                $postComment = $this->create($param);
            }
        } catch
        (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $postComment;
    }
}