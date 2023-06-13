<?php

/**
 * User: Sy Dai
 * Date: 24-Mar-17
 * Time: 23:49
 */

namespace App\Http\Controllers\Auth;

use App\ApiIp;
use App\City;
use App\Company;
use App\Country;
use App\CustomerInformation;
use App\Http\Traits\ControllerTrait;
use App\Http\Validators\CustomerLoginValidator;
use App\Http\Validators\CustomerRegisterValidator;
use App\Http\Validators\FacebookLoginValidator;
use App\Http\Validators\FacebookMappingValidator;
use App\Http\Validators\LoginSMSValidator;
use App\Http\Validators\LoginSmsVerifyAgentValidator;
use App\Http\Validators\LoginValidator;
use App\Http\Validators\RegisterAndLoginValidator;
use App\Http\Validators\RegisterCustomerSMSValidator;
use App\Http\Validators\RegisterPartnerEnterpriseGetSMSValidator;
use App\Http\Validators\RegisterPartnerEnterpriseSMSValidator;
use App\Http\Validators\RegisterPartnerPersonalGetSMSValidator;
use App\Http\Validators\RegisterPartnerPersonalSMSValidator;
use App\Http\Validators\RegisterPartnerSMSValidator;
use App\Http\Validators\RegisterPartnerValidator;
use App\Http\Validators\ForgetPasswordValidator;
use App\Jobs\SendMailRegisterPartnerJob;
use App\Jobs\SendMailToCustomerRegisterPartnerJob;
use App\Jobs\SendMailResetPassword;
use App\Profile;
use App\Role;
use App\Setting;
use App\Store;
use App\TM;
use App\District;
use App\Http\Controllers\Controller;
use App\Http\Validators\RegisterValidator;
use App\Http\Validators\UserLoginValidator;
use App\Supports\TM_Error;
use App\Supports\Message;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\UserReference;
use App\UserSession;
use App\UserStore;
use App\V1\Library\CDP;
use App\V1\Models\ProfileModel;
use App\V1\Models\UserModel;
use App\V1\Models\UserReferenceModel;
use App\Ward;
use function foo\func;
use GuzzleHttp\Client;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Support\Str;
use App\Supports\TM_Email;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


class AuthController extends BaseController
{
    use ControllerTrait;

    protected static $_user_type_customer;
    protected static $_user_type_partner;
    protected static $_user_type_enterprise;
    protected static $_user_type_user;
    protected static $_user_type_distributor;
    protected static $_user_expired_day;
    /**
     *
     */
    protected $jwt;

    protected $model;

    /**
     * AuthController constructor.
     */
    public function __construct(JWTAuth $jwt)
    {
        $this->jwt                    = $jwt;
        self::$_user_type_customer    = "CUSTOMER";
        self::$_user_type_partner     = "PARTNER";
        self::$_user_type_user        = "USER";
        self::$_user_type_distributor = "HUB";
        self::$_user_expired_day      = 365;
        $this->model                  = new UserModel();
    }


    /**
     * @param Request $request
     * @param LoginValidator $loginValidator
     *
     * @return mixed
     * @throws \Exception
     */
    public function authenticate(Request $request, LoginValidator $loginValidator)
    {
        $input = $request->all();

        $loginValidator->validate($input);

        try {
            $credentials = $request->only('code', 'password');

            $credentials['store_id'] = 58;

            $credentials['company_id'] = 34;

            $credentials['deleted'] = 0;

            $credentials['is_active'] = 1;

            $token = $this->jwt->attempt($credentials);

            if (!$token) {
                $result = ApiIp::where('ip',$request->ip())->first();
                if(!$result){
                     ApiIp::insert([
                        'ip'=>$request->ip(),
                        'count_failed'=> 1,
                        'created_at'=>date("Y-m-d H:i:s",time())
                    ]);
                }
    
                if($result && $result->count_failed >= 5 && strtotime("-15 minutes") > strtotime($result->created_at))
                {
                    $result->delete();
                    return $this->responseError(Message::get("users.admin-login-invalid"), 400);
                }
                if($result && $result->count_failed < 5){
                    $result->count_failed +=1;
                    $result->save();
                }
                if($result && $result->count_failed >= 5){
                    return $this->responseError("Tài khoản của bạn bị tạm khóa do đăng nhập sai nhiều lần.", 400);
                }
                return $this->responseError(Message::get("users.admin-login-invalid"), 400);
            }

            // Write User Session
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
        } catch (JWTException $e) {

            return response()->json(['errors' => [[$e->getMessage()]]], 500);
        } catch (\Exception $ex) {
            return $this->responseError($ex->getMessage(), 500);
        }
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

    public function customerLogin(Request $request, CustomerLoginValidator $loginValidator)
    {
        $input = $request->all();

        $loginValidator->validate($input);

        $credentials = $request->only('phone', 'password');

        try {
            $token = $this->jwt->attempt($credentials);

            if (!$token) {
                return $this->responseError(Message::get("users.admin-login-invalid"), 401);
            }

            $user = User::where(['phone' => $input['phone'], 'type' => self::$_user_type_customer])->first();

            if (empty($user)) {
                return $this->responseError(Message::get("users.admin-login-invalid"), 401);
            }

            if ($user->is_active == "0") {
                return $this->responseError(Message::get("users.user-inactive"), 401);
            }

            // Write User Session
            $now = time();
            UserSession::where('user_id', $user->id)->update([
                'deleted'    => 1,
                'updated_at' => date("Y-m-d H:i:s", $now),
                'updated_by' => $user->user_id,
            ]);

            UserSession::insert([
                'user_id'      => $user->id,
                'token'        => $token,
                'device_token' => $input['device_token'] ?? null,
                'login_at'     => date("Y-m-d H:i:s", $now),
                'expired_at'   => date("Y-m-d H:i:s", ($now + config('jwt.ttl') * 60)),
                'device_type'  => get_device(),
                'deleted'      => 0,
                'created_at'   => date("Y-m-d H:i:s", $now),
                'created_by'   => $user->id,
            ]);
            $user      = User::where('id', $user->id)->first();
            $user_id   = $user->id;
            $user_type = $user->type;
            if (!empty($input['store_token'])) {
                $data             = $this->getDataFromToken($input['store_token']);
                $user->store_id   = $data['store']->id;
                $user->company_id = $data['store']->company_id;
                $user->group_id   = $data['group']->id;
                $user->save();
            }
        } catch (\Exception $ex) {
            return $this->responseError($ex->getMessage(), 500);
        }

        // all good so return the token
        return response()->json(compact('token', 'user_id', 'user_type'));
    }

    public function customerRegister(Request $request, CustomerRegisterValidator $registerValidator)
    {
        $input = $request->all();
        $registerValidator->validate($input);

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();

            $data = $this->getDataFromToken($input['store_token']);

            $user = User::model()->where('phone', $input['phone'])->first();

            if (!empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("phone")), 422);
            }

            if (empty($user)) {
                $user = new User();
            }

            $now                     = date("Y-m-d H:i:s", time());
            $user->phone             = $input['phone'];
            $user->password          = password_hash($input['password'], PASSWORD_BCRYPT);
            $user->code              = $input['phone'];
            $user->name              = $input['name'];
            $user->role_id           = USER_ROLE_GUEST_ID;
            $user->type              = USER_TYPE_CUSTOMER;
            $user->register_at       = $now;
            $user->is_active         = 1;
            $user->ref_code          = !empty($input['ref_code']) ? $input['ref_code'] : null;
            $user->register_city     = !empty($input['register_city']) ? $input['register_city'] : null;
            $user->register_district = !empty($input['register_district']) ? $input['register_district'] : null;
            $user->created_by        = $user->id;
            $user->created_at        = $now;
            $user->store_id          = $data['store']->id;
            $user->company_id        = $data['store']->company_id;
            $user->group_id          = $data['group']->id;
            $user->save();

            // Assign to Company|Store
            /** @var Company $company */
            $company = Company::model()->where('id', $data['store']->company_id)->first();
            /** @var Role $role */
            $role = Role::model()->where('code', USER_ROLE_GUEST)->first();
            $this->assignUserToCompanyStore($user, $data['store'], $company, $role);

            $user_id = $user->id;

            $profile = Profile::model()->where('user_id', $user->id)->first();
            if (empty($profile)) {
                $profile = new Profile();
            }
            $full = explode(" ", $input['name']);

            $profile->full_name  = $input['name'];
            $profile->first_name = trim($full[count($full) - 1]);
            unset($full[count($full) - 1]);
            $profile->last_name          = trim(implode(" ", $full));
            $profile->user_id            = $user_id;
            $profile->phone              = $input['phone'];
            $profile->customer_introduce = !empty($input['customer_introduce']) ? $input['customer_introduce'] : null;
            $profile->created_by         = $user->id;
            $profile->created_at         = $now;
            $profile->updated_by         = $user->id;
            $profile->updated_at         = $now;
            $profile->save();

            #CDP
            if (in_array($user->group_code,['HUB','TTPP','DISTRIBUTOR','LEAD','GUEST'])){
                try {
                    CDP::pushCustomerCdp($user,'register - LoginController - line: 298');
                }catch (\Exception $exception){
                    TM_Error::handle($exception);
                }
            }
            DB::commit();

            $message = Message::get("users.register-success", $input['name']);
            return response()->json(compact('message'));
        } catch (\Exception $ex) {
            DB::rollBack();
            $errCode = $ex->getCode() == 400 ? 400 : 500;
            return $this->responseError($ex->getMessage(), $errCode);
        }
    }

    /**
     * @param Request $request
     * @param UserLoginValidator $userLoginValidator
     * @return \Illuminate\Http\JsonResponse
     */
    public function userLogin(Request $request, UserLoginValidator $userLoginValidator)
    {
        $input = $request->all();

        $userLoginValidator->validate($input);

        $credentials = $request->only('phone', 'password');

        try {
            $token = $this->jwt->attempt($credentials);

            if (!$token) {
                return $this->responseError(Message::get("users.login-invalid"), 401);
            }

            $user = User::where(['phone' => $input['phone'], 'is_active' => 1])->first();

            if (empty($user)) {
                return $this->responseError(Message::get("users.login-not-exist", $input['phone']), 401);
            }

            $user = User::where('phone', $input['phone'])->whereIn('type', ['CUSTOMER', 'PARTNER'])->first();

            if (empty($user)) {
                return $this->responseError(Message::get("users.not-allow-access", $input['phone']), 401);
            }

            // Write User Session
            $now = time();
            UserSession::where('user_id', $user->id)->update([
                'deleted'    => 1,
                'updated_at' => date("Y-m-d H:i:s", $now),
                'updated_by' => $user->user_id,
            ]);
            UserSession::insert([
                'user_id'      => $user->id,
                'token'        => $token,
                'device_token' => $input['device_token'] ?? null,
                'login_at'     => date("Y-m-d H:i:s", $now),
                'expired_at'   => date("Y-m-d H:i:s", ($now + self::$_user_expired_day * 24 * 60)),
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_id'],
                'deleted'      => 0,
                'created_at'   => date("Y-m-d H:i:s", $now),
                'created_by'   => $user->id,
            ]);
            $user      = User::where('id', $user->id)->first();
            $user_id   = $user->id;
            $user_type = $user->type;

            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                    $user->save();
                }
            }
        } catch (JWTException $e) {

            return response()->json(['errors' => [[$e->getMessage()]]], 500);
        } catch (\Exception $ex) {
            return $this->responseError($ex->getMessage(), 500);
        }
        // all good so return the token
        return response()->json(compact('token', 'user_id', 'user_type'));
    }

    public function socialLogin(Request $request, FacebookLoginValidator $facebookLoginValidator)
    {
        $input = $request->all();

        $facebookLoginValidator->validate($input);
        if ($input['social_type'] == "FACEBOOK") {
            $typeId = 'fb_id';
        } else {
            $typeId = 'gg_id';
        }
        $request->merge([$typeId => $input['id']]);
        try {
            $user = User::model()->where($typeId, $input['id'])->first();
            if (empty($user)) {
                return $this->responseError(Message::get("V002", "Login ID"), 401);
            }

            if (!$token = $this->jwt->fromUser($user)) {
                return $this->responseError(Message::get("bad_request"), 401);
            }

            // Write User Session
            $now = time();
            UserSession::where('user_id', $user->id)->update([
                'deleted'    => 1,
                'updated_at' => date("Y-m-d H:i:s", $now),
                'updated_by' => $user->user_id,
            ]);
            UserSession::insert([
                'user_id'      => $user->id,
                'token'        => $token,
                'device_token' => $input['device_token'] ?? null,
                'login_at'     => date("Y-m-d H:i:s", $now),
                'expired_at'   => date("Y-m-d H:i:s", ($now + self::$_user_expired_day * 24 * 60)),
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_id'],
                'deleted'      => 0,
                'created_at'   => date("Y-m-d H:i:s", $now),
                'created_by'   => $user->id,
            ]);
            $user      = User::where('id', $user->id)->first();
            $user_id   = $user->id;
            $user_type = $user->type;
            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                    $user->save();
                }
            }
        } catch (JWTException $e) {

            return response()->json(['errors' => [[$e->getMessage()]]], 500);
        } catch (\Exception $ex) {
            return $this->responseError($ex->getMessage(), 500);
        }
        // all good so return the token
        return response()->json(compact('token', 'user_id', 'user_type'));
    }

    public function socialMapping(Request $request, FacebookMappingValidator $facebookMappingValidator)
    {
        $input = $request->all();
        $facebookMappingValidator->validate($input);
        $credentials = $request->only('phone', 'password');

        try {
            $token = $this->jwt->attempt($credentials);

            if (!$token) {
                return $this->responseError(Message::get("users.login-invalid"), 401);
            }
            DB::beginTransaction();
            $time = time();

            if ($input['social_type'] == "FACEBOOK") {
                $typeId = 'fb_id';
            } else {
                $typeId = 'gg_id';
            }

            $user             = User::model()->where('phone', $input['phone'])->first();
            $user->{$typeId}  = $input['id'];
            $user->updated_at = date("Y-m-d H:i:s", $time);
            $user->save();

            UserSession::where('user_id', $user->id)->update([
                'deleted'    => 1,
                'updated_at' => date("Y-m-d H:i:s", $time),
                'updated_by' => $user->id,
            ]);
            UserSession::insert([
                'user_id'      => $user->id,
                'token'        => $token,
                'device_token' => $input['device_token'] ?? null,
                'login_at'     => date("Y-m-d H:i:s", $time),
                'expired_at'   => date("Y-m-d H:i:s", ($time + self::$_user_expired_day * 24 * 60)),
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_id'],
                'deleted'      => 0,
                'created_at'   => date("Y-m-d H:i:s", $time),
                'created_by'   => $user->id,
            ]);

            $user_id   = $user->id;
            $user_type = $user->type;
            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                    $user->save();
                }
            }
            DB::commit();

            return response()->json(compact('token', 'user_id', 'user_type'));
        } catch (JWTException $e) {
            DB::rollBack();
            return response()->json(['errors' => [[$e->getMessage()]]], 500);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    public function registerAndLogin(Request $request, RegisterAndLoginValidator $registerAndLoginValidator)
    {
        $input = $request->all();
        $registerAndLoginValidator->validate($input);
        try {
            DB::beginTransaction();

            $data = $this->getDataFromToken($input['store_token']);

            if ($input['social_type'] == "FACEBOOK") {
                $typeId = 'fb_id';
            } elseif ($input['social_type'] == "GOOGLE") {
                $typeId = 'gg_id';
            } else {
                $typeId = 'zl_id';
            }
            $user = User::model()->where([
                $typeId      => $input['id'],
                'company_id' => $data['store']->company_id,
                'store_id'   => $data['store']->id,
                'is_active'  => 1
            ])->first();
            if (!empty($user)) {
                $check_user = 1;
            } else {
                $user           = new User();
                $password       = strtoupper(uniqid());
                $now            = date("Y-m-d H:i:s", time());
                $user->phone    = $input['phone'] ?? null;
                $user->password = password_hash($password, PASSWORD_BCRYPT);
                $user->email    = $input['email'] ?? null;
                $user->code     = $input['phone'] ?? null;
                $user->name     = $input['name'] ?? null;;
                $user->role_id     = USER_ROLE_GUEST_ID;
                $user->type        = $input['type'];
                $user->register_at = $now;
                $user->is_active   = 1;
                $user->{$typeId}   = $input['id'];
                $user->store_id    = $data['store']->id;
                $user->company_id  = $data['store']->company_id;
                $user->group_id    = $data['group']->id;
                $user->group_code  = $data['group']->code;
                $user->group_name  = $data['group']->name;
                $user->save();

                // Assign to Company|Store
                /** @var Company $company */
                //                $company = Company::model()->where('id', $data['store']->company_id)->first();
                /** @var Role $role */
                //                $role = Role::model()->where('code', USER_ROLE_GUEST)->first();
                //                $this->assignUserToCompanyStore($user, $data['store'], $company, $role);
            }
            $token = $this->jwt->fromUser($user);

            $time = time();
            UserSession::where('user_id', $user->id)->update([
                'deleted'    => 1,
                'updated_at' => date("Y-m-d H:i:s", $time),
                'updated_by' => $user->id,
            ]);
            UserSession::insert([
                'user_id'      => $user->id,
                'token'        => $token,
                'device_token' => $input['device_token'] ?? null,
                'login_at'     => date("Y-m-d H:i:s", $time),
                'expired_at'   => date("Y-m-d H:i:s", ($time + self::$_user_expired_day * 24 * 60)),
                'device_type'  => $input['device_type'] ?? null,
                'deleted'      => 0,
                'created_at'   => date("Y-m-d H:i:s", $time),
                'created_by'   => $user->id,
            ]);

            $user_id   = $user->id;
            $user_type = $user->type;
            $user_phone = $user->phone;
            if (empty($check_user)) {
                $profile = new Profile();
                $full    = explode(" ", $input['name']) ?? null;
                $profile->full_name  = $input['name'] ?? null;
                $profile->first_name = trim($full[count($full) - 1]) ?? null;
                unset($full[count($full) - 1]);
                $profile->last_name  = trim(implode(" ", $full)) ?? null;
                $profile->user_id    = $user_id;
                $profile->email      = $input['email'] ?? null;
                $profile->phone      = $input['phone'] ?? null;
                $profile->created_by = $user->id;
                $profile->created_at = date("Y-m-d H:i:s", $time);
                $profile->avatar_url = $input['avatar_url'] ?? null;
                $profile->save();
            }

            //            $cusInfo = CustomerInformation::where([
            //                'phone'    => $input['phone'],
            //                'store_id' => $data['store']->id
            //            ])->first();

            //            if ($cusInfo) {
            //                $cusInfo->name           = $input['name'] ?? null;
            //                $cusInfo->phone          = $input['phone'] ?? null;
            //                $cusInfo->email          = $input['email'] ?? null;
            //                $cusInfo->address        = $input['address'] ?? null;
            //                $cusInfo->city_code      = $input['city_code'] ?? null;
            //                $cusInfo->store_id       = $data['store']->id;
            //                $cusInfo->district_code  = $input['district_code'] ?? null;
            //                $cusInfo->ward_code      = $input['ward_code'] ?? null;
            //                $cusInfo->full_address   = $input['address'] ?? null;
            //                $cusInfo->street_address = $input['address'] ?? null;
            //                $cusInfo->note           = $input['note'] ?? null;
            //                $cusInfo->gender         = $input['gender'] ?? null;
            //                $cusInfo->update();
            //            } else {
            //                CustomerInformation::insert(
            //                    [
            //                        'name'           => $input['name'] ?? null,
            //                        'phone'          => $input['phone'] ?? null,
            //                        'email'          => $input['email'] ?? null,
            //                        'address'        => $input['address'] ?? null,
            //                        'city_code'      => $input['city_code'] ?? null,
            //                        'store_id'       => $data['store']->id,
            //                        'district_code'  => $input['district_code'] ?? null,
            //                        'ward_code'      => $input['ward_code'] ?? null,
            //                        'full_address'   => $input['address'] ?? null,
            //                        'street_address' => $input['address'] ?? null,
            //                        'note'           => $input['note'] ?? null,
            //                        'gender'         => $input['gender'] ?? null,
            //                    ]
            //                );
            //            }
            DB::commit();
            return response()->json(compact('token', 'user_id', 'user_type', 'user_phone'));
        } catch (JWTException $e) {
            DB::rollBack();
            return response()->json(['errors' => [[$e->getMessage()]]], 500);
        } catch (\Exception $ex) {
            DB::rollBack();
            $errCode = $ex->getCode() == 400 ? 400 : 500;
            return $this->responseError($ex->getMessage(), $errCode);
        }
    }

    public function loginSMS(Request $request)
    {
        $input = $request->all();

        (new LoginSMSValidator())->validate($input);

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 10 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        $user   = User::model()->where('phone', $input['phone'])->first();
        $is_new = 0;
        if (empty($user) || empty($user->profile)) {
            $is_new = 1;
        } else {
            if (!empty($user->verify_sms_code) && strtotime("-2 minutes") < strtotime($user->sms_at)) {
                return $this->responseError(Message::get("SMS-WAITING", 2), 500);
            }

            if ($user->type != USER_TYPE_PARTNER && $user->type != USER_TYPE_CUSTOMER) {
                return $this->responseError(Message::get(
                    "users.not-allow-access",
                    Message::get("phone") . " " . $input['phone']
                ), 500);
            }

            if (!empty($input['email'])) {
                if ($user->email == $input['email']) {
                    return $this->responseError(Message::get("V007", Message::get("email")), 500);
                }
            }
        }

        try {
            DB::beginTransaction();

            $now      = date("Y-m-d H:i:s", time());
            $userType = !empty($input['type']) ? $input['type'] : USER_TYPE_CUSTOMER;
            if (empty($user)) {
                $user              = new User();
                $user->password    = !empty($input['password']) ? password_hash(
                    $input['password'],
                    PASSWORD_BCRYPT
                ) : 'NOT-VERIFY-ACCOUNT';
                $user->phone       = $input['phone'];
                $user->email       = $input['email'] ?? null;
                $user->code        = $input['phone'];
                $user->role_id     = USER_ROLE_GUEST_ID;
                $user->type        = $userType;
                $user->register_at = $now;
                $user->is_active   = 1;
            }

            $smsCode               = mt_rand(100000, 999999);
            $user->verify_sms_code = $smsCode;
            $user->sms_at          = date("Y-m-d H:i:s", time());

            $user->note       = json_encode([
                'device_token' => $input['device_token'],
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_id'],
            ]);
            $user->updated_by = $user->id;
            $user->updated_at = $now;

            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                    $user->code       = $user->code . '_' . $store->company_id . '_' . $store->id;
                }
            }

            $user->save();

            // Send SMS to verify
            $message = Message::get("SMS-REGISTER", $smsCode);
            $res     = $this->sendSMSCode($message, $user->phone);

            DB::commit();

            return response()->json(compact('is_new'));
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    public function registerCustomerSMS(Request $request, RegisterCustomerSMSValidator $registerSMSValidator)
    {
        $input = $request->all();
        $registerSMSValidator->validate($input);

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();

            $data = $this->getDataFromToken($input['store_token']);

            $user = User::model()->where('phone', $input['phone'])->first();

            if (!empty($user) && empty($user->verify_sms_code)) {
                return $this->responseError(Message::get("V003", Message::get("phone")), 422);
            }

            if (empty($user)) {
                $user = new User();
            }

            $smsCode = mt_rand(100000, 999999);

            $now                     = date("Y-m-d H:i:s", time());
            $user->phone             = $input['phone'];
            $user->password          = "NOT-VERIFY-ACCOUNT";
            $user->code              = $input['phone'];
            $user->name              = $input['name'];
            $user->role_id           = USER_ROLE_GUEST_ID;
            $user->type              = USER_TYPE_CUSTOMER;
            $user->register_at       = $now;
            $user->is_active         = 1;
            $user->verify_sms_code   = $smsCode;
            $user->sms_at            = date("Y-m-d H:i:s", time());
            $user->note              = json_encode([
                'device_token' => $input['device_token'],
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_token'],
            ]);
            $user->ref_code          = !empty($input['ref_code']) ? $input['ref_code'] : null;
            $user->register_city     = !empty($input['register_city']) ? $input['register_city'] : null;
            $user->register_district = !empty($input['register_district']) ? $input['register_district'] : null;
            $user->created_by        = $user->id;
            $user->created_at        = $now;
            $user->store_id          = $data['store']->id;
            $user->company_id        = $data['store']->company_id;
            $user->group_id          = $data['group']->id;
            $user->save();

            // Assign to Company|Store
            /** @var Company $company */
            $company = Company::model()->where('id', $data['store']->company_id)->first();
            /** @var Role $role */
            $role = Role::model()->where('code', USER_ROLE_GUEST)->first();
            $this->assignUserToCompanyStore($user, $data['store'], $company, $role);

            $user_id = $user->id;

            $profile = Profile::model()->where('user_id', $user->id)->first();
            if (empty($profile)) {
                $profile = new Profile();
            }
            $full = explode(" ", $input['name']);

            $profile->full_name  = $input['name'];
            $profile->first_name = trim($full[count($full) - 1]);
            unset($full[count($full) - 1]);
            $profile->last_name          = trim(implode(" ", $full));
            $profile->user_id            = $user_id;
            $profile->phone              = $input['phone'];
            $profile->customer_introduce = !empty($input['customer_introduce']) ? $input['customer_introduce'] : null;
            $profile->created_by         = $user->id;
            $profile->created_at         = $now;
            $profile->updated_by         = $user->id;
            $profile->updated_at         = $now;
            $profile->save();

            DB::commit();

            // Send SMS to verify
            $message = Message::get("SMS-REGISTER", $smsCode);
            $this->sendSMSCode($message, $user->phone);

            $message = Message::get("users.register-success", $input['name']);
            return response()->json(compact('message'));
        } catch (\Exception $ex) {
            DB::rollBack();
            $errCode = $ex->getCode() == 400 ? 400 : 500;
            return $this->responseError($ex->getMessage(), $errCode);
        }
    }

    public function getSMSRegisterPartner(
        Request                                  $request,
        RegisterPartnerPersonalGetSMSValidator   $partnerPersonalGetSMSValidator,
        RegisterPartnerEnterpriseGetSMSValidator $enterpriseGetSMSValidator
    ) {
        $input = $request->all();
        if (empty($input['partner_type'])) {
            return $this->responseError(Message::get("V001", Message::get("partner_type")), 422);
        }
        if (!in_array($input['partner_type'], ['PERSONAL', 'ENTERPRISE'])) {
            return $this->responseError(Message::get("V002", Message::get("partner_type")), 422);
        }

        if ($input['partner_type'] == 'PERSONAL') {
            $partnerPersonalGetSMSValidator->validate($input);
        } else {
            $enterpriseGetSMSValidator->validate($input);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone_incorrect")), 422);
        }

        try {
            DB::beginTransaction();
            $user = User::model()->where('phone', $input['phone'])->first();

            if (!empty($user)) {
                return $this->responseError(Message::get("V007", Message::get("phone")), 422);
            }
            if (!empty($input['register_district'])) {
                if (is_array($input['register_district'])) {
                    $registerDistrict = implode(",", $input['register_district']);
                } else {
                    $registerDistrict = str_replace(['[', ']'], ['', ''], $input['register_district']);
                }
            }

            $user                    = new User();
            $smsCode                 = mt_rand(100000, 999999);
            $now                     = date("Y-m-d H:i:s", time());
            $user->phone             = $input['phone'];
            $user->password          = "NOT-VERIFY-ACCOUNT";
            $user->code              = $input['phone'];
            $user->role_id           = USER_ROLE_GUEST_ID;
            $user->type              = USER_TYPE_PARTNER;
            $user->register_at       = $now;
            $user->is_active         = 0;
            $user->verify_sms_code   = $smsCode;
            $user->sms_at            = date("Y-m-d H:i:s", time());
            $user->note              = json_encode([
                'device_token' => $input['device_token'],
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_token'],
                'lat'          => $input['lat'] ?? null,
                'long'         => $input['long'] ?? null,
                'area_id'      => $input['area_id'] ?? null,
            ]);
            $user->email             = !empty($input['email']) ? $input['email'] : null;
            $user->ref_code          = !empty($input['ref_code']) ? $input['ref_code'] : null;
            $user->register_city     = !empty($input['register_city']) ? $input['register_city'] : null;
            $user->register_district = $registerDistrict ?? null;
            $user->partner_type      = $input['partner_type'];
            $user->created_by        = $user->id;
            $user->created_at        = $now;
            $user->save();

            // Send SMS to verify
            $message = Message::get("SMS-REGISTER", $smsCode);
            $res     = $this->sendSMSCode($message, $user->phone);

            DB::commit();
            return response()->json(["message" => "Success"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    public function registerPartnerSMS(
        Request                               $request,
        RegisterPartnerPersonalSMSValidator   $partnerPersonalSMSValidator,
        RegisterPartnerEnterpriseSMSValidator $enterpriseSMSValidator
    ) {
        $input = $request->all();
        if (empty($input['sms_code'])) {
            return $this->responseError(Message::get("V001", Message::get("sms_code")), 422);
        }

        if (empty($input['partner_type'])) {
            return $this->responseError(Message::get("V001", Message::get("partner_type")), 422);
        }
        if (!in_array($input['partner_type'], ['PERSONAL', 'ENTERPRISE'])) {
            return $this->responseError(Message::get("V002", Message::get("partner_type")), 422);
        }

        if ($input['partner_type'] == 'PERSONAL') {
            $partnerPersonalSMSValidator->validate($input);
        } else {
            $enterpriseSMSValidator->validate($input);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();
            $data = $this->getDataFromToken($input['store_token'], $input['partner_type']);
            $user = User::model()->where('phone', $input['phone'])->first();

            $phonesException = Setting::model()->select('data')->where('code', 'PHONE-TEST')->first();
            $phonesException = explode(",", $phonesException->data ?? "");
            if (!in_array($user->phone, $phonesException)) {
                if ($user->verify_sms_code != $input['sms_code']) {
                    return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
                }
            } elseif ($input['sms_code'] != '000000') {
                return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
            }

            if (!empty($user) && empty($user->verify_sms_code)) {
                return $this->responseError(Message::get("V003", Message::get("phone")), 422);
            }

            if (!empty($input['register_district'])) {
                if (is_array($input['register_district'])) {
                    $registerDistrict = implode(",", $input['register_district']);
                } else {
                    $registerDistrict = str_replace(['[', ']'], ['', ''], $input['register_district']);
                }
            }

            $now                     = date("Y-m-d H:i:s", time());
            $user->phone             = $input['phone'];
            $user->password          = "NOT-VERIFY-ACCOUNT";
            $user->code              = $input['phone'];
            $user->name              = $input['name'];
            $user->role_id           = USER_ROLE_GUEST_ID;
            $user->type              = USER_TYPE_PARTNER;
            $user->register_at       = $now;
            $user->is_active         = 0;
            $user->verify_sms_code   = null;
            $user->sms_at            = date("Y-m-d H:i:s", time());
            $user->note              = json_encode([
                'device_token' => $input['device_token'],
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_token'],
            ]);
            $user->email             = !empty($input['email']) ? $input['email'] : null;
            $user->ref_code          = !empty($input['ref_code']) ? $input['ref_code'] : null;
            $user->register_city     = !empty($input['register_city']) ? $input['register_city'] : null;
            $user->register_district = $registerDistrict ?? null;
            $user->partner_type      = $input['partner_type'];
            $user->created_by        = $user->id;
            $user->created_at        = $now;
            $user->store_id          = $data['store']->id;
            $user->company_id        = $data['store']->company_id;
            $user->group_id          = $data['group']->id;
            $user->save();

            // Assign to Company|Store
            /** @var Company $company */
            $company = Company::model()->where('id', $data['store']->company_id)->first();
            /** @var Role $role */
            $role = Role::model()->where('code', USER_ROLE_GUEST)->first();
            $this->assignUserToCompanyStore($user, $data['store'], $company, $role);

            $user_id = $user->id;

            $profile = Profile::model()->where('user_id', $user->id)->first();
            if (empty($profile)) {
                $profile = new Profile();
            }
            $full = explode(" ", $input['name']);

            $profile->full_name  = $input['name'];
            $profile->first_name = trim($full[count($full) - 1]);
            unset($full[count($full) - 1]);
            $profile->last_name          = trim(implode(" ", $full));
            $profile->user_id            = $user_id;
            $profile->phone              = $input['phone'];
            $profile->gender             = !empty($input['gender']) ? $input['gender'] : null;
            $profile->birthday           = !empty($input['birthday']) ? $input['birthday'] : null;
            $profile->email              = !empty($input['email']) ? $input['email'] : null;
            $profile->operation_field    = !empty($input['operation_field']) ? $input['operation_field'] : null;
            $profile->representative     = !empty($input['representative']) ? $input['representative'] : null;
            $profile->work_experience    = !empty($input['work_experience']) ? $input['work_experience'] : null;
            $profile->temp_address       = !empty($input['temp_address']) ? $input['temp_address'] : null;
            $profile->introduce_from     = $input['introduce_from'];
            $profile->customer_introduce = !empty($input['customer_introduce']) ? $input['customer_introduce'] : null;
            $profile->lat                = !empty($input['lat']) ? $input['lat'] : null;
            $profile->long               = !empty($input['long']) ? $input['long'] : null;
            $profile->area_id            = !empty($input['area_id']) ? $input['area_id'] : null;
            $profile->created_by         = $user->id;
            $profile->created_at         = $now;
            $profile->updated_by         = $user->id;
            $profile->updated_at         = $now;
            $profile->save();

            DB::commit();
            $message = Message::get("users.register-success", $input['name']);

            return response()->json(compact('message'));
        } catch (\Exception $ex) {
            DB::rollBack();
            $errCode = $ex->getCode() == 400 ? 400 : 500;
            return $this->responseError($ex->getMessage(), $errCode);
        }
    }

    public function registerSMSResent(Request $request)
    {
        $input = $request->all();
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();
            $user = User::model()->where('phone', $input['phone'])->whereNotNull('verify_sms_code')->first();
            if (empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("info")), 422);
            } else {
                if (strtotime("-2 minutes") < strtotime($user->sms_at)) {
                    // Don't Send Message
                    return $this->responseError(Message::get("SMS-WAITING", "2"));
                }
            }

            $smsCode = mt_rand(100000, 999999);

            $now                   = date("Y-m-d H:i:s", time());
            $user->verify_sms_code = $smsCode;
            $user->sms_at          = date("Y-m-d H:i:s", time());
            $user->updated_by      = $user->id;
            $user->updated_at      = $now;
            $user->save();

            // Send SMS to verify
            $message = Message::get("SMS-REGISTER", $smsCode);
            $this->sendSMSCode($message, $user->phone);

            DB::commit();

            //return response()->json(compact('message'));
            return response()->json(['message' => "Vui lòng kiểm tra tin nhắn để lấy mã"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    public function loginSMSVerify(Request $request, LoginSmsVerifyAgentValidator $validator)
    {
        $input = $request->all();
        if (!empty($input['account_type']) && $input['account_type'] == "AGENT") {
            $validator->validate($input);
        }
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }
        if (empty($input['sms_code'])) {
            return $this->responseError(Message::get("V001", Message::get("sms_code")), 422);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();
            $user = User::model()->where('phone', $input['phone'])->first();

            if (empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("users")), 422);
            }

            $phonesException = Setting::model()->select('data')->where('code', 'PHONE-TEST')->first();
            $phonesException = explode(",", $phonesException->data ?? "");
            if (!in_array($user->phone, $phonesException)) {
                if ($user->verify_sms_code != $input['sms_code']) {
                    return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
                }
            } elseif ($input['sms_code'] != '000000') {
                return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
            }

            if (!$user->is_active) {
                return $this->responseError(Message::get("users.user-inactive"), 422);
            }

            $now = date("Y-m-d H:i:s", time());
            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                }
            }

            if (!empty($input['email'])) {
                $email = User::model()->where('email', $input['email'])->first();
                if (!empty($email)) {
                    return $this->responseError(Message::get("unique", Message::get("email")), 422);
                }
                $user->email = $input['email'];
            }

            if (!empty($input['name'])) {
                $user->name = $input['name'];
            }

            if (!empty($input['account_type']) && $input['account_type'] == 'AGENT') {
                $user->password = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            if (empty($input['group_id'])) {
                $userGroup = UserGroup::model()->where('code', USER_GROUP_AGENT)->first();
                if (empty($userGroup)) {
                    throw new \Exception(
                        Message::get("V043", Message::get("stores"), Message::get('user_groups')),
                        400
                    );
                }
                $user->group_id = $userGroup->id;
            } else {
                $user->group_id = $input['group_id'];
            }

            $user->area_id = $input['area_id'];

            $user->bank_id             = $input['bank_id'] ?? null;
            $user->bank_account_name   = $input['bank_account_name'] ?? null;
            $user->bank_account_number = $input['bank_account_number'] ?? null;
            $user->verify_sms_code     = null;
            $user->sms_at              = null;
            $user->updated_by          = $user->id;
            $user->updated_at          = $now;
            $user->save();

            $user_id   = $user->id;
            $user_type = $user->type;

            $profile = Profile::model()->where('user_id', $user->id)->first();
            if (empty($profile)) {
                if (empty($input['name'])) {
                    return $this->responseError(Message::get("V001", Message::get("alternative_name")), 422);
                }

                if (empty($input['email'])) {
                    return $this->responseError(Message::get("V001", Message::get("email")), 422);
                }
            }

            // Assign Company
            if (!empty($user->company_id)) {
                $this->updateCompanyStore($user);
            }

            $token = $this->accessLogin($user, $input);

            DB::commit();

            return response()->json(compact('token', 'user_id', 'user_type'));
        } catch (JWTException $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage(), 500);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getLine() . "|" . $ex->getMessage(), 500);
        }
    }

    public function loginSMSResent(Request $request)
    {
        $input = $request->all();
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();
            $user = User::model()->where('phone', $input['phone'])->whereNotNull('verify_sms_code')->first();
            if (empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("info")), 422);
            } else {
                if (strtotime("-2 minutes") < strtotime($user->sms_at)) {
                    // Don't Send Message
                    return $this->responseError(Message::get("SMS-WAITING", "2"));
                }
            }

            $smsCode = mt_rand(100000, 999999);

            $now                   = date("Y-m-d H:i:s", time());
            $user->verify_sms_code = $smsCode;
            $user->sms_at          = date("Y-m-d H:i:s", time());
            $user->updated_by      = $user->id;
            $user->updated_at      = $now;
            if (!empty($input['password'])) {
                $param['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }
            $user->save();

            // Send SMS to verify
            $message = Message::get("SMS-REGISTER", $smsCode);
            $this->sendSMSCode($message, $user->phone);

            DB::commit();

            //return response()->json(compact('message'));
            return response()->json(['message' => "Vui lòng kiểm tra tin nhắn để lấy mã"]);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    /**
     * @param Request $request
     * @param RegisterValidator $registerValidator
     * @return \Illuminate\Http\JsonResponse
     */
    public function userRegister(Request $request, RegisterValidator $registerValidator)
    {
        $input = $request->all();
        $registerValidator->validate($input);

        if ($input['phone'] < 0 || strlen($input['phone']) < 9 || strlen($input['phone']) > 14) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            $data  = $this->getDataFromToken($input['store_token']);
            $phone = str_replace(" ", "", $input['phone']);
            $phone = preg_replace('/\D/', '', $phone);
            $param = [
                'phone'         => $phone,
                'type'          => $input['type'],
                'code'          => $phone,
                'name'          => $input['name'],
                'ref_code'      => $input['ref_code'] ?? null,
                'register_city' => $input['register_city'] ?? null,
                'is_active'     => $input['type'] == USER_TYPE_PARTNER ? 0 : 1,
            ];

            if (!empty($input['password'])) {
                $param['password'] = password_hash($input['password'], PASSWORD_BCRYPT);
            }

            DB::beginTransaction();

            $param['store_id']   = $data['store']->id;
            $param['company_id'] = $data['store']->company_id;
            $param['group_id']   = $data['group']->id;

            // Create User
            $user = $this->model->create($param);

            // Assign to Company|Store
            /** @var Company $company */
            $company = Company::model()->where('id', $data['store']->company_id)->first();
            /** @var Role $role */
            $role = Role::model()->where('code', USER_ROLE_GUEST)->first();
            $this->assignUserToCompanyStore($user, $data['store'], $company, $role);

            $names = explode(" ", trim($input['name']));
            $first = $names[0];
            unset($names[0]);
            $last = !empty($names) ? implode(" ", $names) : null;

            $prProfile = [
                'is_active'  => 1,
                'first_name' => $first,
                'last_name'  => $last,
                'short_name' => $input['name'],
                'full_name'  => $input['name'],
                'gender'     => 'O',
                //'address'     => trim($address, ", "),
                'phone'      => $input['phone'],
                'user_id'    => $user->id,
                //'city_id'     => $cityId,
                //                'district_id' => $districtId,
                //                'ward_id'     => $wardId
            ];

            // Create Profile
            $profileModel = new ProfileModel();
            $profileModel->create($prProfile);

            DB::commit();
            if ($input['type'] == USER_TYPE_PARTNER) {
                return response()->json([
                    'status' => Message::get("users.register-success-wait-active", $input['phone']),
                ], 200);
            } else {
                return response()->json(['status' => Message::get("users.register-success", $input['phone'])], 200);
            }
        } catch (QueryException $ex) {
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message'], 401);
        } catch (\Exception $ex) {
            $errCode  = $ex->getCode() == 400 ? 400 : 401;
            $response = TM_Error::handle($ex);
            return $this->responseError($response['message'], $errCode);
        }
    }

    //Partner Register SMS
    public function registerPartnerSMSV1(Request $request)
    {
        $input = $request->all();
        (new RegisterPartnerSMSValidator())->validate($input);

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 10 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }
        if (!empty($input["email"])) {
            $email = $input["email"];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->responseError(Message::get("regex", Message::get("email")), 422);
            }
        }

        $store = Store::model()->where('token', $input['store_token'])->first();
        if (empty($store)) {
            return $this->responseError(Message::get("V002", Message::get("stores")), 422);
        }


        $user = User::model()
            ->where('phone', $input['phone'])
            ->where('store_id', $store->id)
            ->first();

        if (!empty($user) && $user->verify_sms_code == null) {
            return $this->responseError(Message::get("unique", Message::get("phone")), 422);
        }
        //        if (!empty($input['reference_phone'])) {
        //            if ($input['phone'] == $input['reference_phone']) {
        //                return $this->responseError(Message::get("reference_phone_errors", $input['reference_phone']), 422);
        //            }
        //        }
        $is_new = 0;
        if (empty($user) || empty($user->profile)) {
            $is_new = 1;
        } else {
            if (!empty($user->verify_sms_code) && strtotime("-2 minutes") < strtotime($user->sms_at)) {
                return $this->responseError(Message::get("SMS-WAITING", 2), 500);
            }

            if ($user->type != USER_TYPE_PARTNER && $user->type != USER_TYPE_CUSTOMER) {
                return $this->responseError(Message::get(
                    "users.not-allow-access",
                    Message::get("phone") . " " . $input['phone']
                ), 500);
            }

            if (!empty($input['email'])) {
                if ($user->email == $input['email']) {
                    return $this->responseError(Message::get("unique", Message::get("email")), 500);
                }
            }
        }

        try {
            DB::beginTransaction();

            $now = date("Y-m-d H:i:s", time());
            if (empty($user)) {
                $user                  = new User();
                $user->password        = password_hash($input['password'], PASSWORD_BCRYPT);
                $user->phone           = $input['phone'];
                $user->email           = $input['email'] ?? null;
                $user->code            = $input['phone'];
                $user->est_revenues    = $input['est_revenues'] ?? null;
                $user->role_id         = USER_ROLE_GUEST_ID;
                $user->type            = USER_TYPE_CUSTOMER;
                $user->store_id        = $store->id;
                $user->account_status  = ACCOUNT_STATUS_PENDING;
                $user->customer_type   = CUSTOMER_TYPE_PARTNER;
                $user->reference_phone = $input['reference_phone'] ?? null;
                $user->register_at     = $now;
                $user->is_active       = 1;
            } else {
                $user->password        = password_hash($input['password'], PASSWORD_BCRYPT);
                $user->email           = $input['email'] ?? null;
                $user->est_revenues    = $input['est_revenues'] ?? null;
                $user->reference_phone = $input['reference_phone'] ?? null;
                $user->register_at     = $now;
            }

            $smsCode               = mt_rand(100000, 999999);
            $user->verify_sms_code = $smsCode;
            $user->sms_at          = date("Y-m-d H:i:s", time());

            $user->note       = json_encode([
                'device_token' => $input['device_token'],
                'device_type'  => $input['device_type'],
                'device_id'    => $input['device_id'],
            ]);
            $user->updated_by = $user->id;
            $user->updated_at = $now;

            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                    $user->code       = time();
                }
            }
            $user->save();

            // Send SMS to verify
            $message = Message::get("SMS-REGISTER", $smsCode);
            $res     = $this->sendSMSCode($message, $user->phone);
            DB::commit();

            return response()->json(compact('is_new'));
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    public function registerPartnerSMSVerifyV1(Request $request, LoginSmsVerifyAgentValidator $validator)
    {
        $input = $request->all();
        $validator->validate($input);

        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }
        if (empty($input['sms_code'])) {
            return $this->responseError(Message::get("V001", Message::get("sms_code")), 422);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();

            $store      = Store::model()->where('token', $input['store_token'])->first();
            $company_id = Arr::get($store, 'company_id', null);
            if (empty($store) || empty($company_id)) {
                return $this->responseError(Message::get("V002", Message::get("stores")), 422);
            }


            $user = User::model()->where('phone', $input['phone'])->where('company_id', $company_id)->first();

            if (empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("users")), 422);
            }

            $phonesException = Setting::model()->select('data')->where('code', 'PHONE-TEST')->first();
            $phonesException = explode(",", $phonesException->data ?? "");
            if (!in_array($user->phone, $phonesException)) {
                if ($user->verify_sms_code != $input['sms_code']) {
                    return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
                }
            } elseif ($input['sms_code'] != '000000') {
                return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
            }

            if (!$user->is_active) {
                return $this->responseError(Message::get("users.user-inactive"), 422);
            }

            $now = date("Y-m-d H:i:s", time());
            if (!empty($input['store_token'])) {
                $store = Store::model()->where('token', $input['store_token'])->first();
                if ($store) {
                    $user->store_id   = $store->id;
                    $user->company_id = $store->company_id;
                }
            }

            //            if (!empty($input['email'])) {
            //                $email = User::model()->where('email', $input['email'])->where('company_id', $company_id)->first();
            //                if (!empty($email)) {
            //                    return $this->responseError(Message::get("unique", Message::get("email")), 422);
            //                }
            //                $user->email = $input['email'];
            //            }

            if (!empty($input['name'])) {
                $user->name = $input['name'];
            }
            $user->password = password_hash($input['password'], PASSWORD_BCRYPT);

            if (empty($input['group_id'])) {
                $userGroup = UserGroup::model()->where('code', USER_GROUP_AGENT)->first();
                if (empty($userGroup)) {
                    throw new \Exception(
                        Message::get("V043", Message::get("stores"), Message::get('user_groups')),
                        400
                    );
                }
                $user->group_id = $userGroup->id;
            } else {
                $user->group_id = $input['group_id'];
            }
            if (!empty($input['area_id'])) {
                $user->area_id = implode(",", $input['area_id']);
                $user->userArea()->sync($input['area_id']);
            }

            $user->bank_id             = $input['bank_id'] ?? null;
            $user->bank_account_name   = $input['bank_account_name'] ?? null;
            $user->bank_account_number = $input['bank_account_number'] ?? null;
            $user->verify_sms_code     = null;
            $user->sms_at              = null;
            $user->updated_by          = $user->id;
            $user->updated_at          = $now;
            $user->save();

            $user_id   = $user->id;
            $user_type = $user->type;

            $profile = Profile::model()->where('user_id', $user->id)->first();
            if (empty($profile)) {
                if (empty($input['name'])) {
                    return $this->responseError(Message::get("V001", Message::get("alternative_name")), 422);
                }

                if (empty($input['email'])) {
                    return $this->responseError(Message::get("V001", Message::get("email")), 422);
                }
            }

            // Assign Company
            if (!empty($user->company_id)) {
                $this->updateCompanyStore($user);
            }

            $token = $this->accessLogin($user, $input);

            // Create User Reference
            if (!empty($input['reference_phone'])) {
                $checkReferencePhone = User::model()->where([
                    'phone'    => $input['reference_phone'],
                    'store_id' => $store->id
                ])->first();
                if (!empty($checkReferencePhone)) {
                    $this->updateUserReference($user, $store);
                }
            }
            if (env('SEND_EMAIL', 0) == 1) {
                //SendMail
                if (!empty($input['email']) && !empty($store->email_notify)) {
                    $allGroup = UserGroup::model()->pluck('name', 'id')->toArray();
                    $data     = [
                        'to'         => $store->email_notify,
                        'group_name' => $allGroup[$user->group_id],
                        'name'       => $user->name,
                        'phone'      => $user->phone,
                        'email'      => $user->email,
                    ];

                    $dataSendCustomer = [
                        'to'   => $input['email'],
                        'name' => $user->name,
                    ];

                    dispatch(new SendMailRegisterPartnerJob($data));
                    dispatch(new SendMailToCustomerRegisterPartnerJob($dataSendCustomer));
                }
            }
            DB::commit();

            return response()->json(compact('token', 'user_id', 'user_type'));
        } catch (JWTException $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage(), 500);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getLine() . "|" . $ex->getMessage(), 500);
        }
    }

    public function logout()
    {
        try {
            //Delete All Session
            $userId = TM::getCurrentUserId();
            if (!empty($userId)) {
                UserSession::where('user_id', $userId)->where('deleted', '0')->update([
                    'deleted'    => 1,
                    'updated_at' => date('Y-m-d H:i:s', time()),
                    'updated_by' => $userId,
                ]);
            }
                
            $token = $this->jwt->getToken();
            $this->jwt->invalidate($token);

        } catch (TokenInvalidException $exInvalid) {
            return response()->json([
                'message'     => 'A token is invalid',
                'status_code' => Response::HTTP_BAD_REQUEST,
            ], Response::HTTP_BAD_REQUEST);
        } catch (TokenExpiredException $exExpire) {
            return response()->json([
                'message'     => 'A token is expired',
                'status_code' => Response::HTTP_BAD_REQUEST,
            ], Response::HTTP_BAD_REQUEST);
        } catch (JWTException $jwtEx) {
            return response()->json([
                'message'     => "Thank for using NTF. See you later!",
                'status_code' => Response::HTTP_OK,
            ], Response::HTTP_OK);
        }
        //return ["status" => Response::HTTP_OK, "message" => "Logout Successful!", "data" => []];

        return response()->json([
            'message'     => 'Thank for using NTF. See you later!',
            'status_code' => Response::HTTP_OK,
        ], Response::HTTP_OK);
    }

    public function getValidAddress($countryCode, $cityCode, $districtCode, $wardCode)
    {
        $validCountryCode = $validCityCode = $validDistrictCode = $validWardCode = 0;
        if (empty($wardCode)) {
            if (empty($districtCode)) {
                if (empty($cityCode)) {
                    if (empty($countryCode)) {
                        return [];
                    } else {
                        $country = Country::where('code', $countryCode)->first();
                        if (empty($country)) {
                            return [];
                        }
                        $validCountryCode = $countryCode;
                    }
                } else {
                    $city = City::where('code', $cityCode)->first();
                    if (empty($city)) {
                        return [];
                    }

                    $validCityCode    = $cityCode;
                    $validCountryCode = $this->getParentLocationId('country_id', $cityCode, City::class);
                }
            } else {
                $district = District::where('code', $districtCode)->first();
                if (empty($district)) {
                    return [];
                }
                $validDistrictCode = $districtCode;
                $validCityCode     = $this->getParentLocationId('city_code', $districtCode, District::class);
                $validCountryCode  = $this->getParentLocationId('country_id', $validCityCode, City::class);
            }
        } else {
            $validWardCode     = $wardCode;
            $validDistrictCode = $this->getParentLocationId('district_code', $wardCode, Ward::class);
            $validCityCode     = $this->getParentLocationId('city_code', $validDistrictCode, District::class);
            $validCountryCode  = $this->getParentLocationId('country_id', $validCityCode, City::class);
        }

        if (empty($validCountryCode)) {
            return [];
        }

        return array_filter([
            'country_id'    => $validCountryCode,
            'city_code'     => $validCityCode,
            'district_code' => $validDistrictCode,
            'ward_code'     => $validWardCode,
        ]);
    }

    private function getParentLocationId($parentColumn, $childLocationId, $childModel)
    {
        $child = $childModel::find($childLocationId);
        if (empty($child)) {
            return 0;
        }
        return $child->{$parentColumn};
    }

    private function sendSMSCode($msg, $phone)
    {
        $param           = [
            "Phone"     => $phone,
            "Content"   => $msg,
            "ApiKey"    => env("SMS_API_KEY"),
            "SecretKey" => env("SMS_SECRET_KEY"),
            "SmsType"   => 2,
            "Brandname" => env("SMS_BRAND_NAME"),
        ];
        $client          = new Client();
        $phonesException = Setting::model()->select('data')->where('code', 'PHONE-TEST')->first();
        $phonesException = explode(",", $phonesException->data ?? "");
        if (env('SMS_ENABLE_SEND', null) == 1 && !in_array($phone, $phonesException)) {
            $smsResponse = $client->get(env("SMS_URL"), ['query' => $param])->getBody();
        }
        return $smsResponse ?? null;

        // dispatch(new SendMailRegisterPartnerJob($input));
    }

    private function accessLogin(User $user, $input = null)
    {
        $token = $this->jwt->fromUser($user);

        if (!empty($input['name'])) {
            $user_id = $user->id;
            $now     = date("Y-m-d H:i:s", time());
            $profile = Profile::model()->where('user_id', $user->id)->first();
            if (empty($profile)) {
                $profile = new Profile();
            }
            $full = explode(" ", $input['name']);

            $profile->full_name  = $input['name'];
            $profile->first_name = trim($full[count($full) - 1]);
            unset($full[count($full) - 1]);
            $profile->last_name      = trim(implode(" ", $full));
            $profile->user_id        = $user_id;
            $profile->email          = $input['email'] ?? null;
            $profile->phone          = $input['phone'] ?? null;
            $profile->home_phone     = $input['phone'] ?? null;
            $profile->landline_phone = $input['phone'] ?? null;
            $profile->gender         = !empty($input['gender']) ? $input['gender'] : 'O';
            $profile->id_number      = $input['id_number'] ?? null;
            $profile->id_images      = $input['id_images'] ?? null;
            $profile->marital_status = $input['marital_status'] ?? null;
            $profile->occupation     = $input['occupation'] ?? null;
            $profile->city_code      = $input['city_code'] ?? null;
            $profile->district_code  = $input['district_code'] ?? null;
            $profile->ward_code      = $input['ward_code'] ?? null;
            $profile->address        = $input['address'] ?? null;
            $profile->education      = $input['education'] ?? null;
            $profile->education      = $input['education'] ?? null;
            $profile->birthday       = !empty($input['birthday']) ? date('Y-m-d', strtotime($input['birthday'])) : null;
            $profile->created_by     = $user->id;
            $profile->created_at     = $now;
            $profile->updated_by     = $user->id;
            $profile->updated_at     = $now;
            $profile->save();
        }

        $data = json_decode($user->note, true);
        $time = time();
        UserSession::where('user_id', $user->id)->update([
            'deleted'    => 1,
            'updated_at' => date("Y-m-d H:i:s", $time),
            'updated_by' => $user->id,
        ]);
        UserSession::insert([
            'user_id'      => $user->id,
            'token'        => $token,
            'device_token' => $data['device_token'] ?? null,
            'login_at'     => date("Y-m-d H:i:s", $time),
            'expired_at'   => date("Y-m-d H:i:s", ($time + self::$_user_expired_day * 24 * 60)),
            'device_type'  => !empty($data['device_type']) ? $data['device_type'] : null,
            'device_id'    => $data['device_id'] ?? null,
            'deleted'      => 0,
            'created_at'   => date("Y-m-d H:i:s", $time),
            'created_by'   => $user->id,
        ]);

        return $token;
    }

    /**
     * @param $store_token
     * @return array|Store|UserGroup
     * @throws \Exception
     */
    private function getDataFromToken($store_token, $group_code = null)
    {

        $store = Store::model()->where('token', $store_token)->first();
        if (!$store) {
            throw new \Exception(Message::get("V003", Message::get("stores")), 400);
        }
        if (empty($store->company_id)) {
            throw new \Exception(Message::get("V003", Message::get("companies")), 400);
        }
        //        if ($partner_type == USER_PARTNER_TYPE_PERSONAL || $partner_type == USER_PARTNER_TYPE_ENTERPRISE) {
        //            $code = $partner_type == USER_PARTNER_TYPE_PERSONAL ? "DTCN" : "DTDN";
        //            $group = UserGroup::model()->where(['company_id' => $store->company_id, 'code' => $code])->first();
        //        } else {
        //            $group = UserGroup::model()->where(['company_id' => $store->company_id, 'is_default' => '1'])->first();
        //        }
        if ($group_code) {
            $group = UserGroup::model()->where(['company_id' => $store->company_id, 'code' => $group_code])->first();
        } else {
            $group = UserGroup::model()->where(['company_id' => $store->company_id, 'is_default' => '1'])->first();
        }
        if (empty($group)) {
            throw new \Exception(Message::get("V003", Message::get("user_group")), 400);
        }
        //        if (empty($partner_type)) {
        //            $group = UserGroup::model()->where(['company_id' => $store->company_id, 'is_default' => '1'])->first();
        //        }

        return ['store' => $store, 'group' => $group];
    }

    /**
     * @param User $user
     * @param Store $store
     * @param Company $company
     * @param Role $role
     * @return bool
     */
    private function assignUserToCompanyStore(User $user, Store $store, Company $company, Role $role)
    {
        if (empty($user->id)) {
            return false;
        }

        $now = date('Y-m-d H:i:s', time());

        $user_company               = new UserCompany();
        $user_company->user_id      = $user->id;
        $user_company->role_id      = $role->id;
        $user_company->company_id   = $company->id;
        $user_company->user_code    = $user->code;
        $user_company->user_name    = $user->name;
        $user_company->role_code    = $role->code;
        $user_company->role_name    = $role->name;
        $user_company->company_code = $company->code;
        $user_company->company_name = $company->name;
        $user_company->created_at   = $now;
        $user_company->created_by   = $user->id;
        $user_company->save();

        $user_store               = new UserStore();
        $user_store->user_id      = $user->id;
        $user_store->role_id      = $role->id;
        $user_store->store_id     = $store->id;
        $user_store->company_id   = $company->id;
        $user_store->user_code    = $user->code;
        $user_store->user_name    = $user->name;
        $user_store->role_code    = $role->code;
        $user_store->role_name    = $role->name;
        $user_store->company_code = $company->code;
        $user_store->company_name = $company->name;
        $user_store->store_code   = $store->code;
        $user_store->store_name   = $store->name;
        $user_store->created_at   = $now;
        $user_store->created_by   = $user->id;
        $user_store->save();

        return true;
    }

    private function updateCompanyStore(User $user)
    {
        // Delete Old Company
        // UserCompany::model()->where('user_id', $user->id)->delete();
        $role    = Role::model()->where('id', $user->role_id)->first();
        $company = Company::model()->where('id', $user->company_id)->first();
        $store   = Store::model()->where('id', $user->store_id)->first();

        $userCompany = UserCompany::model()->where(['user_id' => $user->id, 'company_id' => $company->id]);

        // Create New Company
        $time         = date('Y-m-d H:i:s', time());
        $paramCompany = [
            'user_id'      => $user->id,
            'user_code'    => $user->code,
            'user_name'    => $user->name,
            'company_id'   => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name,
            'role_id'      => $role->id,
            'role_code'    => $role->code,
            'role_name'    => $role->name,
            'created_at'   => $time,
            'created_by'   => TM::getCurrentUserId(),
            'updated_at'   => $time,
            'updated_by'   => TM::getCurrentUserId(),
        ];
        UserCompany::insert($paramCompany);


        //Update User Store
        $paramUserStore = [
            'user_id'      => $user->id,
            'user_code'    => $user->code,
            'user_name'    => $user->name,
            'company_id'   => $company->id,
            'company_code' => $company->code,
            'company_name' => $company->name,
            'role_id'      => $role->id,
            'role_code'    => $role->code,
            'role_name'    => $role->name,
            'store_id'     => $store->id,
            'store_code'   => $store->code,
            'store_name'   => $store->name,
            'created_at'   => $time,
            'created_by'   => TM::getCurrentUserId(),
            'updated_at'   => $time,
            'updated_by'   => TM::getCurrentUserId(),
        ];

        UserStore::insert($paramUserStore);
    }

    public function registerPartner(Request $request)
    {
        $input = $request->all();
        (new RegisterPartnerValidator())->validate($input);

        $data = $this->getDataFromToken($input['store_token'], $input['group_code'] ?? null);

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 10 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }
        //Check outlet -> agent
        $user = User::model()->where('phone', $input['phone'])
            ->where('password', '!=', 'NOT-VERIFY-ACCOUNT')
            ->whereIn('group_code', [USER_GROUP_OUTLET, USER_GROUP_GUEST])
            ->where('account_status', ACCOUNT_STATUS_APPROVED)
            ->first();
        if ($user && $input['agent_register'] == 1) {
            $user->account_status      = ACCOUNT_STATUS_PENDING;
            $user->bank_id             = $input['bank_id'] ?? null;
            $user->bank_account_name   = $input['bank_account_name'] ?? null;
            $user->bank_account_number = $input['bank_account_number'] ?? null;
            $user->bank_branch         = $input['bank_branch'] ?? null;
            $user->agent_register      = $input['agent_register'] ?? null;
            $user->save();

            return ['status' => "Tài khoản [$user->phone] đang được xét duyệt."];
        }

        // Validate Reference Phone
        if ($input['reference_phone'] != 1000) {
            $userRefer = User::model()->where('phone', $input['reference_phone'])
                ->where('company_id', $data['store']->company_id)
                ->first();
            if (empty($userRefer)) {
                return $this->responseError(Message::get("V003", Message::get("phone_introduce")), 422);
            }

            if ($userRefer->account_status != 'approved') {
                return $this->responseError(
                    Message::get("users.login-not-exist", Message::get('phone_introduce')),
                    422
                );
            }
        }
        $param['store_id']   = $data['store']->id ?? null;
        $param['company_id'] = $data['store']->company_id ?? null;
        $param['group_id']   = $data['group']->id ?? null;
        if (!isset($param['group_id'])) {
            return $this->responseError(Message::get("V043", Message::get("stores"), Message::get('user_groups')), 400);
        }

        if (!empty($input["email"])) {
            $email = $input["email"];
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $this->responseError(Message::get("regex", Message::get("email")), 422);
            }

            // Check duplicate Email
            //            $user = User::model()->where('email', $input['email'])->first();
            //            if ($user) {
            //                return $this->responseError(Message::get("unique", Message::get("email")), 422);
            //            }
            $user = User::model()->where('email', $input['email'])
                ->whereHas('userStores', function ($q) use ($param) {
                    if (!empty($param['store_id'])) {
                        $q->where('store_id', $param['store_id']);
                    }
                })->first();

            $userGuest = User::model()
                ->where('email', $input['email'])
                ->whereHas('userStores', function ($q) use ($param) {
                    if (!empty($param['store_id'])) {
                        $q->where('store_id', $param['store_id']);
                    }
                })->whereHas('group', function ($q) {
                    $q->where('code', ORDER_TYPE_GUEST);
                })->first();

            if (empty($userGuest)) {
                if (!empty($user)) {
                    return $this->responseError(Message::get("unique", Message::get("email")), 422);
                }
            }
        }

        $user           = User::model()
            ->where('phone', $input['phone'])
            ->whereHas('userStores', function ($q) use ($param) {
                if (!empty($param['store_id'])) {
                    $q->where('store_id', $param['store_id']);
                }
            })->first();
        $checkUserGuest = User::model()
            ->where('phone', $input['phone'])
            ->whereHas('userStores', function ($q) use ($param) {
                if (!empty($param['store_id'])) {
                    $q->where('store_id', $param['store_id']);
                }
            })->whereHas('group', function ($q) {
                $q->where('code', ORDER_TYPE_GUEST);
            })
            ->first();

        if (empty($checkUserGuest)) {
            if (!empty($user) && $user->is_active == '1') {
                return $this->responseError(Message::get("unique", Message::get("phone")), 422);
            }
        }

        if (!empty($input['reference_phone'])) {
            if ($input['phone'] == $input['reference_phone']) {
                return $this->responseError(Message::get("reference_phone_errors", $input['reference_phone']), 422);
            }
        }
        try {
            DB::beginTransaction();
            $now                   = date("Y-m-d H:i:s", time());
            $user                  = $user ?? new User();
            $user->password        = password_hash($input['password'], PASSWORD_BCRYPT);
            $user->name            = $input['name'];
            $user->phone           = $input['phone'];
            $user->email           = $input['email'] ?? null;
            $user->code            = $user->code ?? $input['phone'];
            $user->est_revenues    = $input['est_revenues'] ?? null;
            $user->role_id         = USER_ROLE_GUEST_ID;
            $user->type            = USER_TYPE_CUSTOMER;
            $user->store_id        = $param['store_id'];
            $user->account_status  = ACCOUNT_STATUS_PENDING;
            $user->customer_type   = CUSTOMER_TYPE_PARTNER;
            $user->tax             = $input['tax'] ?? null;
            $user->agent_register  = $input['agent_register'] ?? null;
            $user->reference_phone = $input['reference_phone'] == 1000 ? null : $input['reference_phone'];
            $user->register_at     = $now;
            $user->is_active       = 1;
            $user->note            = json_encode([
                'device_type' => get_device()
            ]);

            $user->company_id = $param['company_id'];

            //            if (empty($input['group_id'])) {
            //                $userGroup = UserGroup::model()->where('code', USER_GROUP_OUTLET)->first();
            //                if (empty($userGroup)) {
            //                    throw new \Exception(Message::get("V043", Message::get("stores"), Message::get('user_groups')),
            //                        400);
            //                }
            //                $user->group_id = $userGroup->id;
            //                $user->group_code = $userGroup->code;
            //                $user->group_name = $userGroup->name;
            //            } else {
            //                $group = UserGroup::model()->where('id', $input['group_id'])->first();
            //                $user->group_id = $input['group_id'];
            //                $user->group_code = $group->code;
            //                $user->group_name = $group->name;
            //            }
            $user->group_id   = $data['group']->id;
            $user->group_code = $data['group']->code;
            $user->group_name = $data['group']->name;

            if (!empty($input['area_id'])) {
                $user->area_id = implode(",", $input['area_id']);
                $user->userArea()->sync($input['area_id']);
            }

            $user->bank_id             = $input['bank_id'] ?? null;
            $user->bank_account_name   = $input['bank_account_name'] ?? null;
            $user->bank_account_number = $input['bank_account_number'] ?? null;
            $user->bank_branch         = $input['bank_branch'] ?? null;
            $user->verify_sms_code     = null;
            $user->sms_at              = null;
            $user->save();
            // Assign Company

            if (!empty($user->company_id)) {
                $this->updateCompanyStore($user);
            }

            // Update Level
            if ($input['reference_phone'] != 1000) {
                $this->updateLevelNewPartner($user, $userRefer);
            }

            $token = $this->accessLogin($user, $input);

            // Assign Distributor
            if ($user->group_code == USER_GROUP_AGENT) {
                $this->assignDistributor($user);
            }
            //SendMail
            if (!empty($input['email']) && !empty($param['store_id']->email_notify)) {
                $allGroup = UserGroup::model()->pluck('name', 'id')->toArray();
                $data     = [
                    'to'         => $param['store_id']->email_notify,
                    'group_name' => $allGroup[$user->group_id],
                    'name'       => $user->name,
                    'phone'      => $user->phone,
                    'email'      => $user->email,
                ];

                $dataSendCustomer = [
                    'to'   => $input['email'],
                    'name' => $user->name,
                ];

                //                dispatch(new SendMailRegisterPartnerJob($data));
                //                dispatch(new SendMailToCustomerRegisterPartnerJob($dataSendCustomer));
            }

            $cusInfo = CustomerInformation::where([
                'phone'    => $input['phone'],
                'store_id' => $param['store_id']
            ])->first();

            if ($cusInfo) {
                $cusInfo->name           = $input['name'] ?? null;
                $cusInfo->phone          = $input['phone'] ?? null;
                $cusInfo->email          = $input['email'] ?? null;
                $cusInfo->address        = $input['address'] ?? null;
                $cusInfo->city_code      = $input['city_code'] ?? null;
                $cusInfo->store_id       = $param['store_id'];
                $cusInfo->district_code  = $input['district_code'] ?? null;
                $cusInfo->ward_code      = $input['ward_code'] ?? null;
                $cusInfo->full_address   = $input['address'] ?? null;
                $cusInfo->street_address = $input['address'] ?? null;
                $cusInfo->note           = $input['note'] ?? null;
                $cusInfo->gender         = $input['gender'] ?? null;
                $cusInfo->update();
            } else {
                CustomerInformation::insert(
                    [
                        'name'           => $input['name'] ?? null,
                        'phone'          => $input['phone'] ?? null,
                        'email'          => $input['email'] ?? null,
                        'address'        => $input['address'] ?? null,
                        'city_code'      => $input['city_code'] ?? null,
                        'store_id'       => $param['store_id'],
                        'district_code'  => $input['district_code'] ?? null,
                        'ward_code'      => $input['ward_code'] ?? null,
                        'full_address'   => $input['address'] ?? null,
                        'street_address' => $input['address'] ?? null,
                        'note'           => $input['note'] ?? null,
                        'gender'         => $input['gender'] ?? null,
                    ]
                );
            }
            DB::commit();
            $user_id   = $user->id;
            $user_type = $user->type;
            return response()->json(compact('token', 'user_id', 'user_type'));
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    private function updateLevelNewPartner(User $newUser, User $referUser)
    {
        switch ($referUser->level_number) {
            case 1:
                $newUser->level_number     = 2;
                $newUser->reference_id     = $referUser->id;
                $newUser->reference_code   = $referUser->code;
                $newUser->reference_name   = $referUser->name;
                $newUser->user_level1_id   = $referUser->id;
                $newUser->user_level1_code = $referUser->code;
                $newUser->user_level1_name = $referUser->name;
                $newUser->save();

                $level2_ids                   = explode(",", $referUser->user_level2_ids);
                $level2_ids[]                 = $newUser->id;
                $level2_codes                 = explode(",", $referUser->user_level2_codes);
                $level2_codes[]               = trim($newUser->code);
                $level2_data                  = json_decode($referUser->user_level2_data, true);
                $level2_data[$newUser->code]  = [
                    'id'    => $newUser->id,
                    'code'  => $newUser->code,
                    'name'  => $newUser->name,
                    'phone' => $newUser->phone
                ];
                $referUser->user_level2_ids   = implode(",", array_filter(array_unique($level2_ids)));
                $referUser->user_level2_codes = implode(",", array_filter(array_unique($level2_codes)));
                $referUser->user_level2_data  = json_encode($level2_data);
                $referUser->save();
                break;
            case 2:
                $newUser->level_number      = 3;
                $newUser->reference_id      = $referUser->id;
                $newUser->reference_code    = $referUser->code;
                $newUser->reference_name    = $referUser->name;
                $newUser->user_level1_id    = $referUser->user_level1_id;
                $newUser->user_level1_code  = $referUser->user_level1_code;
                $newUser->user_level1_name  = $referUser->user_level1_name;
                $newUser->user_level2_ids   = $referUser->id;
                $newUser->user_level2_codes = $referUser->code;
                $newUser->user_level2_data  = json_encode([
                    $referUser->code => [
                        'id'    => $referUser->id,
                        'code'  => $referUser->code,
                        'name'  => $referUser->name,
                        'phone' => $referUser->phone
                    ]
                ]);
                $newUser->save();

                $level3_ids                   = explode(",", $referUser->user_level3_ids);
                $level3_ids[]                 = $newUser->id;
                $level3_codes                 = explode(",", $referUser->user_level3_codes);
                $level3_codes[]               = trim($newUser->code);
                $level3_data                  = json_decode($referUser->user_level3_data, true);
                $level3_data[$newUser->code]  = [
                    'id'    => $newUser->id,
                    'code'  => $newUser->code,
                    'name'  => $newUser->name,
                    'phone' => $newUser->phone
                ];
                $referUser->user_level3_ids   = implode(",", array_filter(array_unique($level3_ids)));
                $referUser->user_level3_codes = implode(",", array_filter(array_unique($level3_codes)));
                $referUser->user_level3_data  = json_encode($level3_data);
                $referUser->save();

                $userLevel1                    = User::model()->where('id', $referUser->user_level1_id)->first();
                $level3_ids                    = explode(",", $userLevel1->user_level3_ids);
                $level3_ids[]                  = $newUser->id;
                $level3_codes                  = explode(",", $userLevel1->user_level3_codes);
                $level3_codes[]                = trim($newUser->code);
                $level3_data                   = json_decode($userLevel1->user_level3_data, true);
                $level3_data[$newUser->code]   = [
                    'id'    => $newUser->id,
                    'code'  => $newUser->code,
                    'name'  => $newUser->name,
                    'phone' => $newUser->phone
                ];
                $userLevel1->user_level3_ids   = implode(",", array_filter(array_unique($level3_ids)));
                $userLevel1->user_level3_codes = implode(",", array_filter(array_unique($level3_codes)));
                $userLevel1->user_level3_data  = json_encode($level3_data);
                $userLevel1->save();
                break;
            case 3:
                $newUser->level_number      = 3;
                $newUser->reference_id      = $referUser->id;
                $newUser->reference_code    = $referUser->code;
                $newUser->reference_name    = $referUser->name;
                $newUser->user_level1_id    = (int)$referUser->user_level2_ids;
                $newUser->user_level1_code  = $referUser->user_level2_codes;
                $newUser->user_level1_name  = json_decode(
                    $referUser->user_level2_data,
                    true
                )[$referUser->user_level2_codes]['name'] ?? null;
                $newUser->user_level2_ids   = $referUser->id;
                $newUser->user_level2_codes = $referUser->code;
                $newUser->user_level2_data  = json_encode([
                    $referUser->code => [
                        'id'    => $referUser->id,
                        'code'  => $referUser->code,
                        'name'  => $referUser->name,
                        'phone' => $referUser->phone
                    ]
                ]);
                $newUser->save();

                $referUser->level_number      = 2;
                $referUser->user_level1_id    = $newUser->user_level1_id;
                $referUser->user_level1_code  = $newUser->user_level1_code;
                $referUser->user_level1_name  = $newUser->user_level1_name;
                $referUser->user_level2_ids   = null;
                $referUser->user_level2_codes = null;
                $referUser->user_level2_data  = null;
                $referUser->user_level3_ids   = $newUser->id;
                $referUser->user_level3_codes = $newUser->code;
                $referUser->user_level3_data  = json_encode([
                    $newUser->code => [
                        'id'    => $newUser->id,
                        'code'  => $newUser->code,
                        'name'  => $newUser->name,
                        'phone' => $newUser->phone
                    ]
                ]);
                $referUser->save();

                $userLevel1 = User::model()->where('id', $referUser->user_level1_id)->first();
                $userLevel0 = User::model()->where('id', $userLevel1->user_level1_id)->first();

                $level2_ids = explode(",", $userLevel0->user_level2_ids);
                if (($key = array_search($userLevel1->id, $level2_ids)) !== false) {
                    unset($level2_ids[$key]);
                }
                $level2_codes = explode(",", $userLevel0->user_level2_codes);
                if (($key = array_search($userLevel1->code, $level2_codes)) !== false) {
                    unset($level2_codes[$key]);
                }
                $level2_data = json_decode($userLevel0->user_level2_data, true);
                if (array_key_exists($userLevel1->code, $level2_data)) {
                    unset($level2_data[$userLevel1->code]);
                }
                $userLevel0->user_level2_ids   = implode(",", array_filter(array_unique($level2_ids)));
                $userLevel0->user_level2_codes = implode(",", array_filter(array_unique($level2_codes)));
                $userLevel0->user_level2_data  = json_encode($level2_data);

                $level3_ids = explode(",", $userLevel0->user_level3_ids);
                if (($key = array_search($referUser->id, $level3_ids)) !== false) {
                    unset($level3_ids[$key]);
                }
                $level3_codes = explode(",", $userLevel0->user_level3_codes);
                if (($key = array_search($referUser->code, $level3_codes)) !== false) {
                    unset($level3_codes[$key]);
                }
                $level3_data = json_decode($userLevel0->user_level3_data, true);
                if (array_key_exists($referUser->code, $level3_data)) {
                    unset($level3_data[$referUser->code]);
                }
                $userLevel0->user_level3_ids   = implode(",", array_filter(array_unique($level3_ids)));
                $userLevel0->user_level3_codes = implode(",", array_filter(array_unique($level3_codes)));
                $userLevel0->user_level3_data  = json_encode($level3_data);
                $userLevel0->save();

                $userLevel1->level_number     = 1;
                $userLevel1->user_level1_id   = null;
                $userLevel1->user_level1_code = null;
                $userLevel1->user_level1_name = null;

                $level3_ids                    = explode(",", $userLevel1->user_level3_ids);
                $level3_ids[]                  = $referUser->id;
                $level3_codes                  = explode(",", $userLevel1->user_level3_codes);
                $level3_codes[]                = trim($referUser->code);
                $level3_data                   = json_decode($userLevel1->user_level3_data, true);
                $level3_data[$referUser->code] = [
                    'id'    => $referUser->id,
                    'code'  => $referUser->code,
                    'name'  => $referUser->name,
                    'phone' => $referUser->phone
                ];
                $userLevel1->user_level2_ids   = implode(",", array_filter(array_unique($level3_ids)));
                $userLevel1->user_level2_codes = implode(",", array_filter(array_unique($level3_codes)));
                $userLevel1->user_level2_data  = json_encode($level3_data);
                $userLevel1->user_level3_ids   = $newUser->id;
                $userLevel1->user_level3_codes = $newUser->code;
                $userLevel1->user_level3_data  = json_encode([
                    $newUser->code => [
                        'id'    => $newUser->id,
                        'code'  => $newUser->code,
                        'name'  => $newUser->name,
                        'phone' => $newUser->phone
                    ]
                ]);
                $userLevel1->save();
                break;
        }
    }

    public function forgotPassword(Request $request, ForgetPasswordValidator $validator)
    {
        $input = $request->all();
        $validator->validate($input);
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }
        try {
            DB::beginTransaction();
            $user = User::model()->where('phone', $input['phone'])->where('type', USER_TYPE_CUSTOMER)->first();
            if (empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("info")), 422);
            }
            $smsCode               = mt_rand(100000, 999999);
            $now                   = date("Y-m-d H:i:s", time());
            $user->verify_sms_code = $smsCode;
            $user->sms_at          = $now;
            $user->updated_at      = $now;
            $user->updated_by      = $user->id;
            $user->save();

            // Send SMS to verify
            if (env('SEND_EMAIL', 0) == 1) {
            $message = "$smsCode la ma dat lai mat khau Baotrixemay cua ban";
            $this->sendSMSCode($message, $user->phone);

            if (!empty($input['email'])) {
                $data = [
                    'to'   => $input['email'],
                    'name' => $user->name,
                    'code' => $smsCode,
                ];
                $this->dispatch(new SendMailResetPassword($data));
            }
        }
            DB::commit();
            return ['status' => Message::get("users.opt-send-successfully")];
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }

    private function assignDistributor(User $user)
    {
        $distributors = User::model()->select([
            'users.id',
            'users.code',
            'users.name',
            'p.city_code',
            'p.district_code',
            'p.ward_code'
        ])->join('profiles as p', 'p.user_id', '=', 'users.id')
            ->where('company_id', $user->company_id)
            ->where('group_code', USER_GROUP_DISTRIBUTOR)
            //            ->where(function ($q) use ($user) {
            //                $q->where('city_id', $user->city_id)
            //                    ->orWhere('district_id', $user->district_id)
            //                    ->orWhere('ward_id', $user->ward_id);
            //            })
            ->get()->toArray();

        if (empty($distributors)) {
            return true;
        }

        // Find by Ward
        $key = array_search($user->ward_code, array_column($distributors, 'ward_code'));

        // Find by District
        if (empty($key)) {
            $key = array_search($user->district_code, array_column($distributors, 'district_code'));
        }

        // Find by City
        if (empty($key)) {
            $key = array_search($user->city_code, array_column($distributors, 'city_code'));
        }

        $key = (int)$key;

        $user->distributor_id   = $distributors[$key]['id'];
        $user->distributor_code = $distributors[$key]['code'];
        $user->distributor_name = $distributors[$key]['name'];
        $user->save();

        return true;
    }

    public function forgotPasswordSMSVerify(Request $request)
    {
        $input = $request->all();
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }
        if (empty($input['sms_code'])) {
            return $this->responseError(Message::get("V001", Message::get("sms_code")), 422);
        }

        if (empty($input['password'])) {
            return $this->responseError(Message::get("V001", Message::get("password")), 422);
        }

        $input['phone'] = str_replace([" ", "-"], "", $input['phone']);
        $tmp            = trim($input['phone'], "+0");
        if (strlen($input['phone']) < 9 || strlen($input['phone']) > 12 || !is_numeric($tmp)) {
            return $this->responseError(Message::get("V002", Message::get("phone")), 422);
        }

        try {
            DB::beginTransaction();
            $user = User::model()->where('phone', $input['phone'])->where('type', USER_TYPE_CUSTOMER)->whereNotNull('verify_sms_code')->first();
            if (empty($user)) {
                return $this->responseError(Message::get("V003", Message::get("users")), 422);
            }
            if ($user->verify_sms_code != $input['sms_code']) {
                return $this->responseError(Message::get("V003", Message::get("sms_code")), 422);
            }
            if (strtotime("-5 minutes") > strtotime($user->sms_at)) {
                return $this->responseError(Message::get("V003", Message::get("sms_code_effect")), 422);
            }
            $now                   = date("Y-m-d H:i:s", time());
            $user->password        = password_hash($input['phone'], PASSWORD_BCRYPT);
            $user->verify_sms_code = null;
            $user->sms_at          = null;
            $user->updated_by      = $user->id;
            $user->updated_at      = $now;
            $user->save();
            DB::commit();
        } catch (JWTException $e) {
            DB::rollBack();
            return $this->responseError($e->getMessage(), 500);
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getLine() . "|" . $ex->getMessage(), 500);
        }
        $token     = $this->accessLogin($user, $input);
        $user_id   = $user->id;
        $user_type = $user->type;
        $phone     = $user->type;
        return response()->json(compact('token', 'user_id', 'phone', 'user_type'));
    }

    // private function sendSMSCode($msg, $phone)
    // {
    //     $param = [
    //         "username"   => env('VIVAS_SMS_USERNAME'),
    //         "password"   => env('VIVAS_SMS_PASSWORD'),
    //         "brandname"  => env('VIVAS_SMS_BRANDNAME'),
    //         "textmsg"    => $msg,
    //         "sendtime"   => date('YmdHis'),
    //         "isunicode"  => 0,
    //         "listmsisdn" => $phone,
    //     ];
    //     if (env('SMS_ENABLE_SEND', null) == 1) {
    //         $http = Http::post(env("VIVAS_SMS_ENDPOINT"), $param)->ok();
    //     }
    //     return $http ?? null;
    // }
    public function registerAndLoginZalo()
    {
        $client = new Client();
        $appid  = env('ZALO_APPID_LOGIN');
        $redirect_uri = env('ZALO_REDIRECT_UI');
        $zaloResponse = $client->get('https://oauth.zaloapp.com/v3/auth?app_id=' . $appid . '&redirect_uri=' . $redirect_uri . '');
        $zaloResponse = $zaloResponse->getBody();
        return $zaloResponse;
    }
}
