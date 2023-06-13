<?php


namespace App\V1\Transformers\ContactSupport;


use App\ContactSupport;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;
use function GuzzleHttp\Psr7\str;

class ContactSupportTransformer extends TransformerAbstract
{
    public function transform(ContactSupport $contactSupport)
    {
        try {
            return [
                'id'                 => $contactSupport->id,
                'subject'            => $contactSupport->subject,
                'content'            => $contactSupport->content,
                'category'           => $contactSupport->category,
                'category_name'      => CONTACT_CATEGORY_NAME[$contactSupport->category],
                'status'             => $contactSupport->status,
                'status_name'        => CONTACT_STATUS_NAME[$contactSupport->status],
                'user_id'            => $contactSupport->user_id,
                'attached_image'     => $contactSupport->attached_image,
                'attached_image_url' => !empty($contactSupport->attached_image) ? url('/v0') . "/images/" . $contactSupport->attached_image : null,
                'user_name'          => object_get($contactSupport, "user.profile.full_name"),
                'phone'              => object_get($contactSupport, "user.phone"),
                //'details'            => $dataDetail,
                'created_at'         => date('d-m-Y H:i', strtotime($contactSupport->created_at)),
                'updated_at'         => date('d-m-Y H:i', strtotime($contactSupport->updated_at)),
                'created_by'         => object_get($contactSupport, "createdBy.profile.full_name"),
                'updated_by'         => object_get($contactSupport, "updatedBy.profile.full_name"),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
