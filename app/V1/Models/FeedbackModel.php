<?php

/**
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:34 PM
 */

namespace App\V1\Models;

use App\Company;
use App\Supports\Message;
use App\Feedback;
use App\TM;
use phpDocumentor\Reflection\Types\Nullable;

class FeedbackModel extends AbstractModel
{
    public function __construct(Feedback $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {

        $id         = !empty($input['id']) ? $input['id'] : 0;
        $user_id    = TM::getCurrentUserId();
        $company_id = TM::getCurrentCompanyId();

        if ($id) {
            $feedBack = Feedback::find($id);
            if (empty($feedBack)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $feedBack->title      = array_get($input, 'title', $feedBack->title);
            $feedBack->content    = array_get($input, 'content', $feedBack->content);
            $feedBack->image      = array_get($input, 'image', $feedBack->image);
            $feedBack->status      = array_get($input, 'status', $feedBack->status);
            $feedBack->updated_at = date("Y-m-d H:i:s", time());
            $feedBack->updated_by = $user_id;
            $feedBack->save();
        } else {
            $param    = [
                'title'      => array_get($input, 'title', null),
                'content'    => array_get($input, 'content', null),
                'image'      => array_get($input, 'image', null),
                'status'      => array_get($input, 'status', 'PENDING'),
                'user_id'    => $user_id,
                'company_id' => $company_id,
            ];
            $feedBack = $this->create($param);
        }
        return $feedBack;
    }
}
