<?php
/**
 * User: kpistech2
 * Date: 2020-06-01
 * Time: 22:21
 */

namespace App\V1\Transformers\HashtagComment;

use App\HashtagComment;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class HashtagCommentTransformer extends TransformerAbstract
{
    /**
     * @param HashtagComment $item
     * @return array
     * @throws \Exception
     */
    public function transform(HashtagComment $item)
    {
        try {
            return [
                'id'               => $item->id,
                'code'             => $item->code,
                'content'          => $item->content,
                'is_choose'        => $item->is_choose,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
