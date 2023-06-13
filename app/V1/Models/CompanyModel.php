<?php
/**
 * User: kpistech2
 * Date: 2020-05-09
 * Time: 21:50
 */

namespace App\V1\Models;


use App\Company;
use App\Role;
use App\Supports\Message;
use App\TM;
use App\UserCompany;

class CompanyModel extends AbstractModel
{
    public function __construct(Company $model = null)
    {
        parent::__construct($model);
    }

    /**
     * @param $input
     * @return mixed
     * @throws \Exception
     */
    public function upsert($input)
    {
        $id = !empty($input['id']) ? $input['id'] : 0;
        if ($id) {
            $company = Company::find($id);
            if (empty($company)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $company->name = $input['name'];
            $company->code = $input['code'];
            $company->email = $input['email'];
            $company->address = $input['address'];
            $company->tax_code = $input['tax_code'];
            $company->phone = $input['phone'];
            $company->firebase_token = !empty($input['firebase_token']) ? $input['firebase_token'] : $company->firebase_token;
            $company->email_notification = !empty($input['email_notification']) ? $input['email_notification'] : $company->email_notification;
            $company->company_key = empty($company->company_key) ? $this->getAutoKey() : $company->company_key;
            $company->description = array_get($input, 'description', null);
            $company->avatar_id = array_get($input, 'avatar_id', null);
            $company->avatar = array_get($input, 'avatar', null);
            $company->updated_at = date("Y-m-d H:i:s", time());
            $company->updated_by = TM::getCurrentUserId();
            $company->save();
        } else {
            $base = base64_decode(strtoupper($input['code']) . "|" . strtoupper($input['name']));
            $param = [
                'code'              => $input['code'],
                'name'              => $input['name'],
                'email'             => $input['email'],
                'address'           => $input['address'],
                'tax_code'          => $input['tax_code'],
                'phone'             => $input['phone'],
                'firebase_token'    => !empty($input['firebase_token']) ? $input['firebase_token'] : null,
                'email_notification'=> !empty($input['email_notification']) ? $input['email_notification'] : null,
                'company_key'       => $this->getAutoKey(),
                'verify_token'      => base64_encode($base . env('COMPANY_SECRET_KEY')),
                'description'       => array_get($input, 'description'),
                'avatar_id'         => $input['avatar_id'] ?? null,
                'avatar'            => $input['avatar'] ?? null,
                'is_active'         => 1,
            ];

            $company = $this->create($param);

            // Assign Company
            $role = Role::model()->where('code', USER_ROLE_ADMIN)->first();
            if ($role) {
                $user = TM::info();
                $now = date('Y-m-d H:i:s', time());
                $user_company = new UserCompany();
                $user_company->user_id = TM::getCurrentUserId();
                $user_company->user_code = $user['code'];
                $user_company->user_name = $user['full_name'] ?? $user['email'];
                $user_company->company_id = $company->id;
                $user_company->company_code = $company->code;
                $user_company->company_name = $company->name;
                $user_company->company_name = $company->name;
                $user_company->company_key = $company->company_key;
                $user_company->role_id = $role->id;
                $user_company->role_code = $role->code;
                $user_company->role_name = $role->name;
                $user_company->is_active = 1;
                $user_company->created_at = $now;
                $user_company->created_by = TM::getCurrentUserId();
                $user_company->updated_at = $now;
                $user_company->updated_by = TM::getCurrentUserId();
                $user_company->save();
            }
        }

        return $company;
    }

    private function getAutoKey()
    {
        $key = mt_rand(000000000000, 999999999999);;
        $check = Company::model()->where('company_key', $key)->first();
        if ($check) {
            $key = $this->getAutoKey();
        }
        return $key;
    }
}