<?php


namespace App\V1\Models;


use App\ContactSupport;
use App\ContactSupportDetail;
use App\Image;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\UserCompany;

class ContactSupportModel extends AbstractModel
{
    public function __construct(ContactSupport $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        if (!empty($input['attached_image'])) {
            $attached_image = explode(';base64,', $input['attached_image']);
            if (!empty($attached_image[1])) {
                $image = Image::uploadImage($attached_image[1]);
                $image = $image->url;
            }
        }
        try {
            $id = !empty($input['id']) ? $input['id'] : 0;
            if ($id) {
                $contact_support = ContactSupport::find($id);
                if (empty($contact_support)) {
                    throw new \Exception(Message::get("V003", "ID: #$id"));
                }
                $contact_support->subject = array_get($input, 'subject', $contact_support->subject);
                $contact_support->content = array_get($input, 'content', $contact_support->content);
                $contact_support->category = array_get($input, 'category', $contact_support->category);
                $contact_support->status = array_get($input, 'status', $contact_support->status);
                $contact_support->user_id = array_get($input, 'user_id', $contact_support->user_id);
                $contact_support->attached_image = !empty($image) ? $image : $contact_support->attached_image;
                $contact_support->updated_at = date("Y-m-d H:i:s", time());
                $contact_support->updated_by = TM::getCurrentUserId();
                $contact_support->save();
            } else {
                $param = [
                    'subject'        => $input['subject'],
                    'content'        => array_get($input, 'content'),
                    'category'       => array_get($input, 'category'),
                    'status'         => $input['status'],
                    'attached_image' => $image ?? null,
                    'user_id'        => TM::getCurrentUserId(),
                ];

                $contact_support = $this->create($param);
            }
//            // Create|Update Contact Support Detail
//            $allContactSupportDetail = ContactSupportDetail::model()->where('contact_support_id', $contact_support->id)->get()->toArray();
//            $allContactSupportDetail = array_pluck($allContactSupportDetail, 'id', 'id');
//            $allContactSupportDetailDelete = $allContactSupportDetail;
//            if (!empty($input['details'])) {
//                foreach ($input['details'] as $detail) {
//                    if (empty($detail['id']) || empty($allContactSupportDetail[$detail['id']])) {
//                        // Create Detail
//                        $contactSupportDetail = new ContactSupportDetailModel();
//                        $contactSupportDetail->create([
//                            'contact_support_id' => $contact_support->id,
//                            'user_reply'         => TM::getCurrentUserId(),
//                            'reply_at'           => date("Y-m-d H:i:s", time()),
//                            'content_reply'      => array_get($detail, 'content_reply', null),
//                        ]);
//                        continue;
//                    }
//                    // Update
//                    unset($allContactSupportDetailDelete[$detail['id']]);
//                    $contactSupportDetail = ContactSupportDetail::find($detail['id']);
//                    $contactSupportDetail->contact_support_id = array_get($detail, 'contact_support_id', $contactSupportDetail->contact_support_id);
//                    $contactSupportDetail->user_reply = array_get($detail, 'user_reply', TM::getCurrentUserId());
//                    $contactSupportDetail->content_reply = array_get($detail, 'content_reply', $contactSupportDetail->content_reply);
//                    $contactSupportDetail->reply_at = !empty($detail['reply_at']) ? date("Y-m-d H:i:s", strtotime($detail['reply_at'])) : $contactSupportDetail->reply_at;
//                    $contactSupportDetail->updated_at = date('Y-m-d H:i:s', time());
//                    $contactSupportDetail->updated_by = TM::getCurrentUserId();
//                    $contactSupportDetail->save();
//                    if (!empty($allContactSupportDetailDelete)) {
//                        // Delete Contact Support Detail
//                        ContactSupportDetail::model()->whereIn('id', array_values($allContactSupportDetailDelete))->delete();
//                    }
//                }
//            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message']);
        }
        return $contact_support;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $companyId = TM::getCurrentCompanyId();
        $userCompanies = UserCompany::model()->where('company_id', $companyId)->pluck('user_id');
        $query = $query->whereIn('user_id', $userCompanies);
        if (TM::getCurrentRole() != USER_ROLE_ADMIN) {
            $query = $query->where('user_id', TM::getCurrentUserId());
        }
        if (!empty($input['category'])) {
            $query = $query->where('category', 'like', "%{$input['category']}%");
        }
        if (!empty($input['status'])) {
            $query = $query->where('status', 'like', "%{$input['status']}%");
        }
        if (!empty($input['subject'])) {
            $query = $query->where('subject', 'like', "%{$input['subject']}%");
        }
        if (!empty($input['user_id'])) {
            $query = $query->where('user_id', 'like', "%{$input['user_id']}%");
        }
        if ($limit) {
            if ($limit === 1) {
                return $query->first();
            } else {
                return $query->paginate($limit);
            }
        } else {
            return $query->get();
        }
    }
}