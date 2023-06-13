<?php

namespace App\Http\Controllers\Client\Auth;

use App\Area;
use App\Http\Validators\Client\CheckOTPRegisterValidator;
use App\RotationCondition;
use App\Company;
use App\CustomerInformation;
use App\Http\Controllers\Controller;
use App\Http\Traits\ControllerTrait;
use App\Http\Validators\Client\CheckOTPResetPasswordValidator;
use App\Http\Validators\Client\LoginValidator;
use App\Http\Validators\Client\RegisterValidator;
use App\Http\Validators\Client\ResetPasswordValidator;
use App\Profile;
use App\Role;
use App\Setting;
use App\Store;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\TM;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\UserSession;
use App\UserStore;
use App\V1\Controllers\NNDD\NNDDController;
use App\V1\Library\CDP;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\JWTAuth;

class LoginController extends Controller
{
    use ControllerTrait;

    /**
     * @var int $storeId
     */
    protected $storeId;

    /**
     * @var int $companyId
     */
    protected $companyId;

    /**
     * @var JWTAuth $jwt
     */
    protected $jwt;

    /**
     * LoginController constructor.
     * @param JWTAuth $jwt
     * @param Request $request
     * @throws \Exception
     */
    public function __construct(JWTAuth $jwt, Request $request)
    {
        $this->jwt = $jwt;
        if (!empty(TM::getCurrentUserId())) {
            $this->storeId   = TM::getCurrentStoreId();
            $this->companyId = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        throw new \Exception(Message::get("V003", "Token"));
                    }
                    $this->storeId   = $store->id;
                    $this->companyId = $store->company_id;
                }
            }
        }
    }

    /**
     * Response error
     *
     * @param $message
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    private function responseError($message, $code = 422)
    {
        return response()->json([
            'status' => 'error',
            'error'  => [
                'errors' => [
                    "msg" => $message,
                ],
            ],
        ], $code);
    }

    /**
     * Send SMS
     *
     * @param $message
     * @param $phone
     * @return \Psr\Http\Message\StreamInterface|null
     */
    private function sendSMS($message, $phone)
    {
        $param = [
            'from'  => env('VIETGUYS_SMS_BRAND'),
            'u'     => env('VIETGUYS_SMS_USERNAME'),
            'pwd'   => env('VIETGUYS_SMS_PASSWORD'),
            'phone' => $phone,
            'sms'   => $message,
            'bid'   => '123',
            'type'  => '8',
            'json'  => '1',
        ];

        $response = Http::post(env('VIETGUYS_SMS_ENDPOINT'), $param);
        return $response->json();
    }

    /**
     * Get user group
     *
     * @return UserGroup[]|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getUserGroup()
    {
        return UserGroup::with([
            'children' => function ($query) {
                $query->select(['id', 'parent_id', 'name', 'description', 'is_default'])
                    ->where('is_active', 1);
            },
        ])
            ->where('company_id', $this->companyId)
            ->where('is_view_app', 1)
            ->whereNull('parent_id')
            ->where('is_active', 1)
            ->get(['id', 'code', 'name', 'description', 'is_view_app', 'is_default']);
    }

    /**
     * Get areas
     *
     * @return mixed
     */
    public function getAreas()
    {
        return Area::where('company_id', $this->companyId)
            ->where('is_active', 1)
            ->get(['id', 'code', 'name', 'description']);
    }

    /**
     * Login
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        (new LoginValidator())->validate($request->all());

        if (!$this->userAlreadyExists($request->input('phone'))) {
            return $this->responseError(Message::get('users.login-not-exist', Message::get('users')));
        }

        $credentials = $request->only('phone', 'password');

        $credentials['store_id'] = $this->storeId;

        $credentials['deleted'] = 0;

        $credentials['is_active'] = 1;

        $token = $this->jwt->attempt($credentials);

        if (!$token) {
            return $this->responseError(Message::get("users.admin-login-invalid"), 400);
        }

        $time = time();

        UserSession::where('user_id', Auth::id())->update([
            'deleted'    => 1,
            'updated_at' => date("Y-m-d H:i:s", $time),
            'updated_by' => Auth::id(),
        ]);

        UserSession::insert([
            'user_id'      => Auth::id(),
            'token'        => $token,
            'device_token' => $request->get('device_token', null),
            'login_at'     => date("Y-m-d H:i:s", $time),
            'expired_at'   => date("Y-m-d H:i:s", ($time + 365 * 24 * 60)),
            'deleted'      => 0,
            'created_at'   => date("Y-m-d H:i:s", $time),
            'created_by'   => Auth::id(),
        ]);

        return response()->json([
            'token'          => $token,
            'user_id'        => Auth::id(),
            'user_type'      => Auth::user()->type,
            'role_id'        => Auth::user()->role_id,
            'role_code'      => Auth::user()->role->code,
            'role_name'      => Auth::user()->role->name,
            'account_status' => Auth::user()->account_status,
            'group_code'     => Arr::get(Auth::user()->group, 'code'),
        ]);
    }

    /**
     * Check user already exists by phone
     *
     * @param $phone
     * @return mixed
     */
    private function userAlreadyExists($phone)
    {
        return User::where('phone', $phone)
            ->where('store_id', $this->storeId)
            ->where('type', '!=', 'USER')
            ->exists();
    }

    /**
     * Get SMS Verify
     *
     * @param $phone
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Query\Builder|object|null
     */
    private function getSMSVerify($phone)
    {
        return DB::table('sms_verify')
            ->where('phone', $phone)
            ->first();
    }

    /**
     * Get SMS code
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSMSCode(Request $request)
    {
        $this->recaptchaScore($request);
        $phone = $request->input('phone');

        $phone = str_replace([' ', '-'], '', $phone);
        $tmp   = trim($phone, '+0');
        if (strlen($phone) < 10 || strlen($phone) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get('V002', Message::get('phone')));
        }

        if ($this->userAlreadyExists($phone)) {
            return $this->responseError(Message::get('phone_already_exists'));
        }

        $smsVerify = $this->getSMSVerify($phone);

        if (!empty($smsVerify)) {
            if (strtotime('-1 minutes') < $smsVerify->time) {
                return $this->responseError(Message::get('SMS-WAITING', 1), 500);
            }
        }

        $smsCode = mt_rand(100000, 999999);

        DB::table('sms_verify')->updateOrInsert(
            ['phone' => $phone],
            [
                'code' => $smsCode,
                'time' => time(),
            ]
        );

        $this->sendSMS(Message::get('SMS-REGISTER', $smsCode), $phone);

        return response()->json(['message' => true]);
    }

    /**
     * Register
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function register(Request $request)
    {
        (new RegisterValidator())->validate($request->all());

        $input = $request->all();

        $phone = $request->input('phone');

        //        if ((isset($input['verify_otp']) && $input['verify_otp'] != 1) || !isset($input['verify_otp'])) {
        // $smsVerify = $this->getSMSVerify($phone);
        // if (empty($smsVerify)) {
        //     return $this->responseError(Message::get('phone_not_sms_code'));
        // }

        // if ($smsVerify->code != $request->input('sms_code')) {
        //     return $this->responseError(Message::get('sms_code_incorrect'));
        // }

        // if (strtotime('-5 minutes') > $smsVerify->time) {
        //     return $this->responseError(Message::get('sms_code_effect'));
        // }
        //        }
        $user = User::model()
            ->where('phone', $input['phone'])
            ->where('store_id', $this->storeId)
            ->first();

        if (!empty($user) && $user->is_active == '1') {
            return $this->responseError(Message::get("unique", Message::get("phone")), 422);
        }

        if (!empty($input['reference_phone'])) {
            if ($input['phone'] == $input['reference_phone']) {
                return $this->responseError(Message::get("reference_phone_errors", $input['reference_phone']), 422);
            }
        }
        $date  = date('Y-m-d H:i:s', time());
        $group = UserGroup::where('is_default', 1)
            ->where('company_id', $this->companyId)
            ->first();
        if (!empty($request->input('group_code'))) {
            $check_group = UserGroup::model()
                ->where([
                    'company_id' => $this->companyId,
                    'is_view' => 1,
                    'is_view_app' => 1
                ])
                ->pluck('code')->toArray();
            if (!in_array($request->input('group_code'), $check_group)) {
                return $this->responseError(Message::get("user_group.not-exist", "#{$request->input('group_code')}"));
            }
        }
        $company  = Company::find($this->companyId);
        $store    = Store::find($this->storeId);
        $role     = Role::find(USER_ROLE_GUEST_ID);
        $rotation = RotationCondition::model()
            ->where('type', 'REGISTER')->whereHas('rotation', function ($q) use ($date) {
                $q->where('start_date', "<=", $date)->where('end_date', ">=", $date);
            })->get();
        try {
            DB::beginTransaction();
            $user                  = $user ?? new User();
            $user->code            = $phone;
            $user->name            = $request->input('name');
            $user->email           = $request->input('email', null);
            $user->phone           = $phone;
            $user->type            = USER_TYPE_CUSTOMER;
            $user->group_id        = !empty($request->input('group_id')) ? $request->input('group_id') : $group->id;
            $user->group_code      = !empty($request->input('group_code')) ? $request->input('group_code') : $group->code;
            $user->group_name      = !empty($request->input('group_name')) ? $request->input('group_name') : $group->name;
            $user->role_id         = USER_ROLE_GUEST_ID;
            $user->company_id      = $this->companyId;
            $user->store_id        = $this->storeId;
            $user->reference_phone = $request->input('reference_phone', null);
            $user->is_active       = 1;
            $user->register_at     = date("Y-m-d H:i:s", time());
            $user->password        = Hash::make($request->input('password'));
            if (!empty($rotation)) {
                $user->turn_rotation = 1;
            }
            $user->save();
            if (!empty($request->input('name'))) {
                $name    = $request->input('name');
                $now     = date("Y-m-d H:i:s", time());
                $profile = Profile::model()->where('user_id', $user->id)->first();
                if (empty($profile)) {
                    $profile = new Profile();
                }
                $full = explode(" ", $name);

                $profile->full_name  = $name;
                $profile->first_name = trim($full[count($full) - 1]);
                unset($full[count($full) - 1]);
                $profile->last_name  = trim(implode(" ", $full));
                $profile->user_id    = $user->id;
                $profile->email      = $input['email'] ?? null;
                $profile->phone      = $input['phone'] ?? null;
                $profile->gender     = !empty($input['gender']) ? $input['gender'] : 'O';
                $profile->birthday   = !empty($input['birthday']) ? date('Y-m-d', strtotime($input['birthday'])) : null;
                $profile->created_by = $user->id;
                $profile->created_at = $now;
                $profile->updated_by = $user->id;
                $profile->updated_at = $now;
                $profile->save();
            }

            UserCompany::create([
                'user_id'      => $user->id,
                'user_code'    => $user->code,
                'user_name'    => $user->name,
                'company_id'   => $this->companyId,
                'company_code' => $company->code,
                'company_name' => $company->name,
                'role_id'      => $role->id,
                'role_code'    => $role->code,
                'role_name'    => $role->name,
            ]);

            UserStore::create([
                'user_id'      => $user->id,
                'user_code'    => $user->code,
                'user_name'    => $user->name,
                'company_id'   => $this->companyId,
                'company_code' => $company->code,
                'company_name' => $company->name,
                'store_id'     => $this->storeId,
                'store_code'   => $store->code,
                'store_name'   => $store->name,
                'role_id'      => $role->id,
                'role_code'    => $role->code,
                'role_name'    => $role->name,
            ]);

            $cusInfo = CustomerInformation::where([
                'phone'    => $input['phone'],
                'store_id' => $store->id
            ])->first();

            if ($cusInfo) {
                $cusInfo->name     = $input['name'] ?? null;
                $cusInfo->phone    = $input['phone'] ?? null;
                $cusInfo->store_id = $store->id ?? null;
                $cusInfo->update();
            } else {
                CustomerInformation::insert(
                    [
                        'name'     => $input['name'] ?? null,
                        'phone'    => $input['phone'] ?? null,
                        'email'    => $input['email'] ?? null,
                        'store_id' => $store->id ?? null
                    ]
                );
            }

            #NNDD
            if(empty($input['source'])){
                try {
                    $client = new Client();
                    $url = env('URL_NNDD', 'https://nutifood-virtualstore-api.dev.altasoftware.vn');
                    $api_key = env('API_KEY_NNDD', "c869c601-589a-424d-9f89-f5d5f5eb3f24");
                    
                    $request_nndd = [
                        "FullName" => $user->name ?? null,
                        "PhoneNumber" => $user->phone,
                        "Password" => $input['password'],
                        'Address' => $user->address ?? null,
                        'EmailAddress' => $user->profile->email ?? null
                    ];
                    // dd($request_nndd);
                    $response = $client->request('POST', $url."/api/Customers/Sync", [
                        'verify' => false,
                        'headers' => [
                            'X-API-Key' => $api_key,
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode($request_nndd)
                    ]);
                    $res = $response->getBody()->getContents();
                    NNDDController::writeLogNNDD("Đồng bộ khách hàng", null, $request_nndd, $res, null, $request_nndd['PhoneNumber'], "SUCCESS", "LoginController - register - line:299", null);
                } catch (\Exception $exception) {
                    NNDDController::writeLogNNDD("Đồng bộ khách hàng", null, $request_nndd, $res ?? $exception, null, $request_nndd['PhoneNumber'], "FAILED", "LoginController - register - line:299", null);
                    TM_Error::handle($exception);
                }
            }

            #CDP
            if (in_array($user->group_code,['HUB','TTPP','DISTRIBUTOR','LEAD','GUEST'])){
                try {
                    CDP::pushCustomerCdp($user,'register - LoginController - line: 298');
                }catch (\Exception $exception){
                    TM_Error::handle($exception);
                }
            }

            // DB::table('sms_verify')->where('phone', $phone)->delete();

            DB::commit();

            $token = $this->jwt->attempt([
                'phone'    => $phone,
                'password' => $request->input('password'),
                'store_id' => $user->store_id,
            ]);

            $time = time();

            UserSession::where('user_id', $user->id)->update([
                'deleted'    => 1,
                'updated_at' => date("Y-m-d H:i:s", $time),
                'updated_by' => $user->id,
            ]);

            UserSession::insert([
                'user_id'    => $user->id,
                'token'      => $token,
                'login_at'   => date("Y-m-d H:i:s", $time),
                'expired_at' => date("Y-m-d H:i:s", ($time + 365 * 24 * 60)),
                'deleted'    => 0,
                'created_at' => date("Y-m-d H:i:s", $time),
                'created_by' => $user->id,
            ]);

            // Create User Reference
            if (!empty($input['reference_phone'])) {
                $checkReferencePhone = User::model()->where([
                    'phone'    => $input['reference_phone'],
                    'store_id' => $store->id,
                ])->first();
                if (!empty($checkReferencePhone)) {
                    $this->updateUserReference($user, $store);
                }
            }

            // $this->sendSMS("Cam on quy khach da su dung dich vu cua chung toi. Chuc quy khach mot ngay tot lanh!", $phone);

            return response()->json([
                'token'     => $token,
                'user_id'   => $user->id,
                'user_type' => $user->type,
            ]);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->responseError($exception->getLine() . ":" . $exception->getMessage());
        }
    }

    /**
     * Check phone reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPhoneResetPassword(Request $request)
    {
        $this->recaptchaScore($request);
        $phone = $request->input('phone');
        if (empty($phone)) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }

        $user = User::where('phone', $phone)
            ->where('store_id', $this->storeId)
            ->where('company_id', $this->companyId)
            ->first();

        if (empty($user)) {
            return $this->responseError(Message::get('users.not-exist', Message::get('users')));
        }

        $smsVerify = $this->getSMSVerify($phone);

        if (!empty($smsVerify)) {
            if (strtotime('-1 minutes') < $smsVerify->time) {
                return $this->responseError(Message::get('SMS-WAITING', 1), 500);
            }
        }

        $smsCode = mt_rand(100000, 999999);

        DB::table('sms_verify')->updateOrInsert(
            ['phone' => $phone],
            [
                'code' => $smsCode,
                'time' => time(),
            ]
        );

        $this->sendSMS(Message::get('vietguys-sms-reset-password', $smsCode), $phone);

        return response()->json(['message' => Message::get("users.opt-send-successfully")]);
    }

    /**
     * Check OTP reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkOTPResetPassword(Request $request)
    {
        (new CheckOTPResetPasswordValidator())->validate($request->all());

        $smsVerify = $this->getSMSVerify($request->input('phone'));

        if (empty($smsVerify)) {
            return $this->responseError(Message::get('phone_not_sms_code'));
        }

        if ($smsVerify->code != $request->input('sms_code')) {
            return $this->responseError(Message::get('sms_code_incorrect'));
        }

        if (strtotime('-15 minutes') > $smsVerify->time) {
            return $this->responseError(Message::get('sms_code_effect'));
        }

        return response()->json(['message' => true]);
    }

    /**
     * Reset password
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function resetPassword(Request $request)
    {
        (new ResetPasswordValidator())->validate($request->all());

        $smsVerify = $this->getSMSVerify($request->input('phone'));

        if (empty($smsVerify)) {
            return $this->responseError(Message::get('phone_not_sms_code'));
        }

        if ($smsVerify->code != $request->input('sms_code')) {
            return $this->responseError(Message::get('sms_code_incorrect'));
        }

        if (strtotime('-15 minutes') > $smsVerify->time) {
            return $this->responseError(Message::get('sms_code_effect'));
        }

        $phone = $request->input('phone');

        $user = User::where('phone', $phone)
            ->where('store_id', $this->storeId)
            ->where('company_id', $this->companyId)
            ->first();

        if (empty($user)) {
            return $this->responseError(Message::get('users.not-exist', Message::get('users')));
        }

        $user->password = Hash::make($request->input('password'));
        $user->save();
        DB::table('sms_verify')->where('phone', $phone)->delete();

        $message = Message::get("users.change-password", $user->name);
        return response()->json(compact('message'));
    }


    public function sendOTPRegister(Request $request)
    {
        $this->recaptchaScore($request);
        $phone = $request->input('phone');
        if (empty($phone)) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }

        $smsVerify = $this->getSMSVerify($phone);
        if (!empty($smsVerify)) {
            if (strtotime('-1 minutes') < $smsVerify->time) {
                return $this->responseError(Message::get('SMS-WAITING', 1), 500);
            }
        }

        $smsCode = mt_rand(100000, 999999);

        DB::table('sms_verify')->updateOrInsert(
            ['phone' => $phone],
            [
                'code' => $smsCode,
                'time' => time(),
            ]
        );
        $this->sendSMS(Message::get('vietguys-sms-register', $smsCode), $phone);

        return response()->json(['message' => Message::get("users.opt-send-successfully")]);
    }

    public function sendOTPForgotPassword(Request $request)
    {
        $this->recaptchaScore($request);
        $userId = TM::getCurrentUserId();
        $user = User::find($userId);
        if (empty($user)) {
            return $this->responseError(Message::get("users.phone-exits"), 422);
        }
        $smsVerify = $this->getSMSVerify($user->phone);

        if (!empty($smsVerify)) {
            if (strtotime('-1 minutes') < $smsVerify->time) {
                return $this->responseError(Message::get('SMS-WAITING', 1), 500);
            }
        }

        $smsCode = mt_rand(100000, 999999);

        DB::table('sms_verify')->updateOrInsert(
            ['phone' => $user->phone],
            [
                'code' => $smsCode,
                'time' => time(),
            ]
        );

        $this->sendSMS(Message::get('vietguys-sms-register', $smsCode), $user->phone);

        return response()->json(['message' => Message::get("users.opt-send-successfully")]);
    }

    public function checkOTPRegister(Request $request)
    {
        (new CheckOTPRegisterValidator())->validate($request->all());

        $smsVerify = $this->getSMSVerify($request->input('phone'));

        if (empty($smsVerify)) {
            return $this->responseError(Message::get('phone_not_sms_code'));
        }

        if ($smsVerify->code != $request->input('sms_code')) {
            return $this->responseError(Message::get('sms_code_incorrect'));
        }

        if (strtotime('-15 minutes') > $smsVerify->time) {
            return $this->responseError(Message::get('sms_code_effect'));
        }

        return response()->json(['message' => true]);
    }

    private function recaptchaScore($request)
    {
        $captchaResponse = null;

        $response = (new Client())->request('post', env('RECAPTCHA_ENDPOINT'), [
            'form_params' => [
                'response' => $request->get('token'),
                'secret' => env('RECAPTCHA_SECRET_KEY'),
            ],
        ]);

        $captchaResponse = json_decode($response->getBody()->getContents(), true);

        if ($captchaResponse['success'] == '1' && $captchaResponse['score'] >= 0.5) {
            return true;
        } else {
            throw new \Exception("You are not a human", 500);
        }
    }
}
