<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:23
 */

namespace App\V1\Models;


use App\Supports\Message;
use App\TM;
use App\HashtagComment;

class HashtagCommentModel extends AbstractModel
{
    public function __construct(HashtagComment $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return HashtagComment|mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            /** @var HashtagComment $item */
            $item = HashtagComment::model()->where('id', $id)->first();
            if (empty($item)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $item->code       = $input['code'];
            $item->content    = $input['content'];
            $item->is_choose  = $input['is_choose'];
            $item->company_id = TM::getCurrentCompanyId();
            $item->save();
        } else {
            $param = [
                'code'              => $input['code'],
                'content'           => $input['content'],
                'is_choose'         => $input['is_choose'],
                'company_id'        => TM::getCurrentCompanyId()
            ];
            $item = $this->create($param);
        }
        return $item;
    }
}