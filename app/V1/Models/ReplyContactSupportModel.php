<?php


namespace App\V1\Models;


use App\ContactSupport;
use App\ReplyContactSupport;
use App\Supports\Message;
use App\TM;

class ReplyContactSupportModel extends AbstractModel
{
    public function __construct(ReplyContactSupport $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $contactSupport = ContactSupport::find($input['contact_support_id']);
        if (empty($contactSupport) || $contactSupport->status == CONTACT_SUPPORT_STATUS_SOLVED) {
            throw new \Exception(Message::get("V040", $contactSupport->subject, CONTACT_STATUS_NAME[CONTACT_SUPPORT_STATUS_SOLVED]));
        }
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $result = ReplyContactSupport::find($id);
            if (empty($result)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $result->contact_support_id = array_get($input, 'contact_support_id', $result->contact_support_id);
            $result->content_reply = array_get($input, 'content_reply', $result->content_reply);
            $result->updated_at = date("Y-m-d H:i:s", time());
            $result->updated_by = TM::getCurrentUserId();
            $result->save();
        } else {
            $param = [
                'contact_support_id' => $input['contact_support_id'],
                'content_reply'      => $input['content_reply'],
            ];
            $result = $this->create($param);
        }

        return $result;
    }
}