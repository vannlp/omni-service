<?php


namespace App\V1\Models;


use App\ReportComment;
use App\Supports\Message;
use App\TM;

class ReportCommentModel extends AbstractModel
{
    public function __construct(ReportComment $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $reportComment = ReportComment::find($id);
            if (empty($reportComment)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $reportComment->comment_id = array_get($input, 'comment_id', $reportComment->comment_id);
            $reportComment->content = array_get($input, 'content', $reportComment->content);
            $reportComment->user_id = TM::getCurrentUserId();
            $reportComment->updated_at = date("Y-m-d H:i:s", time());
            $reportComment->updated_by = TM::getCurrentUserId();
            $reportComment->save();
        } else {
            $param = [
                'content'    => $input['content'],
                'comment_id' => $input['comment_id'],
                'user_id'    => TM::getCurrentUserId(),
            ];
            $reportComment = $this->create($param);
        }
        return $reportComment;
    }
}