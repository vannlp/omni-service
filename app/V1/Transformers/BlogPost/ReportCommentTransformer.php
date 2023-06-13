<?php


namespace App\V1\Transformers\BlogPost;


use App\ReportComment;
use League\Fractal\TransformerAbstract;

class ReportCommentTransformer extends TransformerAbstract
{
    public function transform(ReportComment $reportComment)
    {
        return [
            'id'         => $reportComment->id,
            'content'    => $reportComment->content,
            'user_id'    => $reportComment->user_id,
            'user_name'  => !empty($reportComment->id) ? object_get($reportComment, 'user.profile.full_name') : null,
            'comment_id' => $reportComment->comment_id,
            'created_at' => date('d-m-Y', strtotime($reportComment->created_at)),
        ];
    }
}