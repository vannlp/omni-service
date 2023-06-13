<?php


namespace App\V1\Transformers\ReplyContactSupport;


use App\ReplyContactSupport;
use App\Supports\TM_Error;
use App\TM;
use League\Fractal\TransformerAbstract;

class ReplyContactSupportTransformer extends TransformerAbstract
{
    public function transform(ReplyContactSupport $replyContactSupport)
    {
        try {
            return [
                'id'                 => $replyContactSupport->id,
                'contact_support_id' => $replyContactSupport->contact_support_id,
                'content_reply'      => $replyContactSupport->content_reply,
                'created_at'         => date("Y-m-d H:i:s", strtotime($replyContactSupport->created_at)),
                'created_by'         => object_get($replyContactSupport, "createdBy.profile.full_name"),
                'updated_at'         => date("Y-m-d H:i:s", strtotime($replyContactSupport->updated_at)),
                'updated_by'         => array_get($replyContactSupport, "updatedBy.profile.full_name"),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}