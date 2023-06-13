<?php


namespace App\V1\Transformers\BlogPost;


use App\PostComment;
use App\ReportComment;
use App\TM;
use League\Fractal\TransformerAbstract;

class PostCommentTransformer extends TransformerAbstract
{
    public function transform(PostComment $postComment)
    {
        if (!empty($postComment->like)) {
            $postCommentLike = explode(",", $postComment->like);
            $is_liked = in_array(TM::getCurrentUserId(), $postCommentLike);
        }
        $reportComments = ReportComment::model()->where('comment_id', $postComment->id)->get();
        foreach ($reportComments as $reportComment) {
            $reportCommentDetails[] = [
                'id'         => $reportComment->id,
                'content'    => $reportComment->content,
                'user_id'    => array_get($reportComment, 'user_id', null),
                'user_name'  => array_get($reportComment, 'user.profile.full_name', null),
                'created_at' => date('d-m-Y', strtotime($reportComment->created_at))
            ];
        }
        return [
            'id'         => $postComment->id,
            'content'    => $postComment->content,
            'post_id'    => $postComment->post_id,
            'website_id' => $postComment->website_id,
            'parent_id'  => object_get($postComment, 'parent_id', null),
            'like'       => object_get($postComment, 'like', null),
            'is_liked'   => !empty($is_liked) ? 1 : 0,
            'report'     => !empty($reportCommentDetails) ? $reportCommentDetails : [],
            'count_like' => object_get($postComment, 'count_like', null),
            'created_at' => date('d-m-Y', strtotime($postComment->created_at)),
            'created_by' => object_get($postComment, 'createdBy.profile.full_name', $postComment->created_by),
        ];
    }
}