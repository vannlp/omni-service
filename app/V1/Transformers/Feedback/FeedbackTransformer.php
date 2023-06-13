<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 22/12/2018
 * Time: 03:55 PM
 */

namespace App\V1\Transformers\Feedback;

use App\Feedback;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class FeedbackTransformer extends TransformerAbstract
{
    public function transform(Feedback $item)
    {
        try {
            return [
                'id'            => $item->id,
                'title'         => $item->title,
                'content'       => $item->content,
                'user_id'       => $item->user_id,
                'image'         => !empty($item->image) ? env('GET_FILE_URL') . $item->image : null,
                'company_id'    => $item->company_id,
                'status'        => $item->status,
                'content_reply' => $item->content_reply,
                'created_at'    => date('d-m-Y', strtotime($item->created_at)),
                'created_by'    => object_get($item, 'createdBy.profile.full_name'),
                'updated_at'    => date('d-m-Y', strtotime($item->updated_at)),
                'updated_by'    => object_get($item, 'updatedBy.profile.full_name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
