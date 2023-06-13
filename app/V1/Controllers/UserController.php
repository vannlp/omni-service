<?php

namespace App\V1\Controllers;

use App\City;
use App\CityHasRegion;
use App\District;
use App\Exports\ExportCustomersUser;
use App\Company;
use App\CustomerInformation;
use App\Distributor;
use App\Exports\ExportOrdersByCaller;
use App\Exports\ExportOrdersByLocation;
use App\Exports\ExportOrdersByUser;
use App\Exports\ExportUsers;
use App\Exports\ExportUsersGroup;
use App\Jobs\SendMailChangeAccountStatusJob;
use App\Jobs\SendMailCustomerHubNewJob;
use App\Order;
use App\PaymentHistory;
use App\Profile;
use App\PromotionTotal;
use App\RegisterArea;
use App\Role;
use App\Setting;
use App\Store;
use App\Supports\DataUser;
use App\Supports\Log;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Supports\TM_PDF;
use App\TM;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\UserReference;
use App\UserRP;
use App\UserSession;
use App\UserStatusOrder;
use App\UserStore;
use App\V1\Controllers\NNDD\NNDDController;
use App\V1\Library\CDP;
use App\V1\Models\OrderModel;
use App\V1\Models\ProfileModel;
use App\V1\Models\UserModel;
use App\V1\Traits\AuthTrait;
use App\V1\Traits\ControllerTrait;
use App\V1\Transformers\Membership\MembershipTransformer;
use App\V1\Transformers\User\UserCLientByPhoneTransformer;
use App\V1\Transformers\User\UserCustomerProfileTransformer;
use App\V1\Transformers\User\UserGetListTransformer;
use App\V1\Transformers\User\UserListViewTransformer;
use App\V1\Transformers\User\UserPaymentHistoryTransformer;
use App\V1\Transformers\User\UserProfileTransformer;
use App\V1\Transformers\User\UserReferenceTransformer;
use App\V1\Transformers\User\UserTransformer;
use App\V1\Transformers\User\UserZoneHubTransformer;
use App\V1\Validators\ChangeMyProfileValidator;
use App\V1\Validators\UserChangePasswordValidator;
use App\V1\Validators\UserCreateClientPasswordValidator;
use App\V1\Validators\UserCreateValidator;
use App\V1\Validators\UserProfileUpdateValidator;
use App\V1\Validators\UserTypeAgentCreateValidator;
use App\V1\Validators\UserUpdateValidator;
use App\ZoneHub;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Tymon\JWTAuth\JWTAuth;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class UserController
 *
 * @package App\V1\Controllers
 */
class UserController extends BaseController
{
    use AuthTrait;

    /**
     * @var UserModel
     */
    protected        $model;
    protected        $profileModel;
    protected static $_user_expired_day;

    /**
     * UserController constructor.
     */
    public function __construct(JWTAuth $jwt)
    {
        $this->model             = new UserModel();
        $this->profileModel      = new ProfileModel();
        $this->jwt               = $jwt;
        self::$_user_expired_day = 365;
    }

    /**
     * @param UserTransformer $userTransformer
     * @return \Dingo\Api\Http\Response
     */
    public function search(Request $request, UserListViewTransformer $userListViewTransformer)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['code']       = isset($input['code']) ? $input['code'] : "";
        $input['full_name']  = isset($input['full_name']) ? $input['full_name'] : "";
        $input['phone']      = isset($input['phone']) ? $input['phone'] : "";
        $input['type']       = isset($input['type']) ? $input['type'] : "";
        $input['is_active']  = isset($input['is_active']) ? $input['is_active'] : null;
        $input['is_partner'] = isset($input['is_partner']) ? $input['is_partner'] : "";
        $input['group_code'] = isset($input['group_code']) ? $input['group_code'] : "";
        $user                = $this->model->searchList($input, ['profile', 'area', 'zoneHub'], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($user, $userListViewTransformer);
    }

    public function searchList(Request $request, UserGetListTransformer $userGetListTransformer)
    {
        $input               = $request->all();
        $limit               = array_get($input, 'limit', 20);
        $input['code']       = isset($input['code']) ? $input['code'] : "";
        $input['full_name']  = isset($input['full_name']) ? $input['full_name'] : "";
        $input['phone']      = isset($input['phone']) ? $input['phone'] : "";
        $input['email']      = isset($input['email']) ? $input['email'] : "";
        $input['type']       = isset($input['type']) ? $input['type'] : "";
        $input['is_active']  = isset($input['is_active']) ? $input['is_active'] : null;
        $input['is_partner'] = isset($input['is_partner']) ? $input['is_partner'] : "";
        $input['group_code'] = isset($input['group_code']) ? $input['group_code'] : "";
        $input['store_id']   = TM::getCurrentStoreId();
        $user                = $this->model->searchListPermission($input, ['profile', 'area', 'zoneHub'], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($user, $userGetListTransformer);
    }

    public function searchProfile(Request $request, UserCustomerProfileTransformer $userCustomerProfileTransformer)
    {
        $input = $request->all();
        $limit = array_get($input, 'limit', 20);
        $user  = $this->model->search($input, ['profile', 'membership'], $limit);
        Log::view($this->model->getTable());
        return $this->response->paginator($user, $userCustomerProfileTransformer);
    }
    public function updateStatus($id, Request $request)
    {
        $input = $request->all();
        $user = User::find($id);
        $user->is_active = $input['is_active'];
        $user->save();
        return ['status' => Message::get("users.update-success", $user->code)];
    }

    public function viewProfile(UserCustomerProfileTransformer $userCustomerProfileTransformer)
    {
        try {
            $userId = TM::getCurrentUserId();
            $user   = User::find($userId);
            if (empty($user)) {
                return ['data' => []];
            }
            if ($user->type != "USER") {
                $user->star = $this->getMyStar();
            }
            // Get Total Price
            $orderModel        = new OrderModel();
            $totalSales        = $orderModel->getTotalSalesForCustomers(['user_ids' => [$userId]]);
            $user->total_sales = array_sum(array_column($totalSales, 'total_sales'));
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return $this->response->item($user, $userCustomerProfileTransformer);
    }

    public function getPartnerByOrder($orderId)
    {
        try {
            $order = Order::model()->where('id', $orderId)
                ->where('customer_id', TM::getCurrentUserId())
                ->where('status', '!=', ORDER_STATUS_NEW)
                ->where('status', '!=', ORDER_STATUS_COMPLETED)
                ->first();
            if (empty($order)) {
                return $this->responseError(Message::get("V003", "#ID: $orderId"));
            }
            $userId = $order->partner_id;
            $user   = User::model()->select([
                'users.id',
                'users.phone',
                'us.socket_id',
                'p.email',
                'p.full_name',
                'p.lat',
                'p.long',
                'p.avatar',
            ])
                ->join('profiles as p', 'p.user_id', 'users.id')
                ->join('user_sessions as us', 'us.user_id', 'users.id')
                ->where('users.id', $userId)->where('us.deleted', '0')->groupBy('us.user_id')->first();
            if (empty($user)) {
                return ['data' => []];
            }
            $user['avatar'] = !empty($user['avatar']) ? url('/v0') . "/img/" . "uploads," . $user['avatar'] : null;
            $user['star']   = $this->getStarUser($user['id']) ?? 0;
            return ['data' => $user->toArray()];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
    }

    private function getStarUser($user_id)
    {
        $avg      = 0;
        $user     = User::find($user_id);
        $typeUser = $user->type == "USER" ? null : ($user->type == USER_TYPE_PARTNER ? 'partner' : 'customer');
        if (empty($typeUser)) {
            return 0;
        }
        $orders = Order::model()->where($typeUser . "_id", $user_id)
            ->whereNotNull($typeUser . "_star")
            ->where($typeUser . "_star", '>', '0')
            ->where($typeUser . "_star", '!=', '')
            ->get()->toArray();
        if (!empty($orders)) {
            $stars = [];
            foreach ($orders as $order) {
                $stars[] = $order[$typeUser . "_star"];
            }
            $avg = round(array_sum($stars) / count($stars), 1);
        }
        return $avg;
    }

    public function view($id, UserTransformer $userTransformer)
    {
        $user = User::find($id);
        if (empty($user)) {
            return ['data' => []];
        }
        Log::view($this->model->getTable());
        return $this->response->item($user, $userTransformer);
    }

    public function info(UserProfileTransformer $userProfileTransformer)
    {
        $id   = TM::getCurrentUserId();
        $user = User::find($id);
        if (empty($user)) {
            return $this->response->errorBadRequest(Message::get('V003', "ID #$id"));
        }
        Log::view($this->model->getTable());
        return $this->response->item($user, $userProfileTransformer);
    }

    public function getInfo(Request $request, UserTransformer $userTransformer)
    {
        $input = $request->all();
        try {
            $userId  = TM::getCurrentUserId(true);
            $user    = $this->model->getFirstBy('id', $userId);
            $profile = Profile::model()->where('user_id', $user->id)->first();
            // Get Employee
            $result = [
                'user_id'      => $user->id,
                'user_code'    => $user->code,
                'role_code'    => Arr::get($user, 'role.code', null),
                'role_name'    => Arr::get($user, 'role.name', null),
                'role_level'   => Arr::get($user, 'role.role_level', null),
                'full_name'    => object_get($profile, 'full_name', null),
                'email'        => $user->email,
                'is_super'     => $user->is_super,
                'company_id'   => $user->company_id,
                'company_code' => object_get($user, 'company.code'),
                'company_name' => object_get($user, 'company.name'),
                'group_id'     => object_get($user, 'group.id'),
                'group_code'   => object_get($user, 'group.code'),
                'group_name'   => object_get($user, 'group.name'),
            ];
            // Show Profile
            if (!empty($input['profile']) && $input['profile'] == 1) {
                $result['profile'] = [
                    'first_name'  => object_get($user, "profile.first_name", null),
                    'last_name'   => object_get($user, "profile.last_name", null),
                    'email'       => object_get($user, "profile.email", null),
                    'short_name'  => object_get($user, "profile.short_name", null),
                    'address'     => object_get($user, "profile.address", null),
                    'phone'       => object_get($user, "profile.phone", null),
                    'language'    => object_get($user, "profile.language", "VI"),
                    'birthday'    => object_get($user, "profile.birthday", null),
                    'gender'      => object_get($user, "profile.gender", null),
                    'gender_name' => config('constants.STATUS.GENDER')[strtoupper(object_get($user, "profile.gender", 'O'))],
                    'avatar'      => !empty($input['img']) && $input['img'] == 1 ? get_image(object_get(
                        $user,
                        "profile.avatar",
                        null
                    )) : null,
                ];
            }
            // Show Permissions
            if (!empty($input['permissions']) && $input['permissions'] == 1) {
                $result['permissions'] = TM::getCurrentPermission();
            }
            Log::view($this->model->getTable());
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }

        return ['status' => Response::HTTP_OK, 'data' => $result];
    }


    public function create(
        Request                      $request,
        UserCreateValidator          $userCreateValidator,
        UserTypeAgentCreateValidator $userTypeAgentCreateValidator
    ) {
        $input = $request->all();
        if (empty($input['type'])) {
            $this->response->errorBadRequest(Message::get("V001", Message::get("user_type")));
        }
        if ($input['type'] == USER_TYPE_AGENT) {
            $userTypeAgentCreateValidator->validate($input);
            $input['code'] = str_clean_special_characters($input['code']);
            if (!empty($input['full_name'])) {
                $input['full_name'] = str_clean_special_characters($input['full_name']);
            }
            if (!empty($input['email'])) {
                $input['email'] = str_clean_special_characters($input['email']);
            }
            $userTypeAgentCreateValidator->validate($input);
        } else {
            $userCreateValidator->validate($input);
            if (!empty($input['full_name'])) {
                $input['full_name'] = str_clean_special_characters($input['full_name']);
            }
            $input['code'] = str_clean_special_characters($input['code']);
            if (!empty($input['email'])) {
                $input['email'] = str_clean_special_characters($input['email']);
            }
            $userCreateValidator->validate($input);
        }
        try {
            DB::beginTransaction();
            $user = $this->model->upsert($input);
            //Send Mail
            if (!empty($user->email) && !empty($input['send_mail']) && $input['send_mail'] == 1 && env('SEND_EMAIL', 0) == 1) {
                $storeName = Store::find(!empty($input['store_id']) ? $input['store_id'] : TM::getCurrentStoreId());
                $mail      = [
                    'to'        => $user->email,
                    'user_name' => $user->name,
                    'msg'       => $storeName->name,
                    'phone'     => substr($user->phone, 0, -3) . '***',
                    'password'  => $input['password'],
                ];
                dispatch(new SendMailCustomerHubNewJob($mail['to'], $mail));
            }

            $user = User::find($user->id);
            # CDP
            if (in_array($user->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])) {
                try {
                    CDP::pushCustomerCdp($user, 'create - UserController - line: 318');
                } catch (\Exception $exception) {
                    TM_Error::handle($exception);
                }
            }

            Log::create($this->model->getTable(), "#ID:" . $user->id . "-" . $user->code);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("users.create-success", $user->phone)];
    }

    public function update(
        $id,
        Request $request,
        UserUpdateValidator $userUpdateValidator,
        UserTransformer $userTransformer
    ) {
        $input       = $request->all();
        $input['id'] = $id;
        $userUpdateValidator->validate($input);

        try {
            DB::beginTransaction();
            //            if (!empty($input['email']) && $input['type'] == USER_TYPE_USER) {
            //                $checkEmail = User::model()->where('email', $input['email'])->first();
            //                if (!empty($checkEmail) && $checkEmail->id != $id) {
            //                    return $this->responseError(Message::get("unique", Message::get("email")), 500);
            //                }
            //            }
            //            if (!empty($input['code']) && $input['type'] == USER_TYPE_USER) {
            //                $checkCode = User::model()->where('code', $input['code'])->first();
            //                if (!empty($checkCode) && $checkCode->id != $id) {
            //                    return $this->responseError(Message::get("unique", Message::get("code")), 500);
            //                }
            //            }
            $user = $this->model->upsert($input);
            Log::update($this->model->getTable(), "#ID:" . $user->id, null, $user->code);
            DB::commit();

            # CDP
            if (in_array($user->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])) {

                $user = User::model()->find($user->id);
                try {
                    CDP::pushCustomerCdp($user, 'update - UserController - line: 384');
                } catch (\Exception $exception) {
                    TM_Error::handle($exception);
                }
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return $this->response->item($user, $userTransformer);
    }

    public function updateProfile(Request $request, UserProfileUpdateValidator $userProfileUpdateValidator)
    {
        $input            = $request->all();
        $input['user_id'] = TM::getCurrentUserId();
        $userProfileUpdateValidator->validate($input);
        if (!empty($input['full_name'])) {
            $input['full_name'] = str_clean_special_characters($input['full_name']);
        }
        try {
            DB::beginTransaction();
            $profile = Profile::where('user_id', $input['user_id'])->whereNull('deleted_at')->first();
            $users   = User::find($input['user_id']);
            $now     = date("Y-m-d H:i:s", time());
            if (empty($profile)) {
                $profile             = new Profile();
                $profile->created_at = $now;
                $profile->created_by = $input['user_id'];
            }
            if (empty($users)) {
                $users             = new User();
                $users->created_at = $now;
                $users->created_by = $input['user_id'];
            }
            if (!empty($input['first_name'])) {
                $profile->first_name = $input['first_name'];
            }
            if (!empty($input['last_name'])) {
                $profile->last_name = $input['last_name'];
            }
            if (!empty($input['first_name']) && !empty($input['last_name'])) {
                $profile->short_name = $profile->full_name = trim($input['first_name'] . " " . $input['last_name']);
            } elseif (!empty($input['first_name']) && empty($input['last_name'])) {
                $profile->short_name = $profile->full_name = $input['first_name'];
            } elseif (empty($input['first_name']) && !empty($input['last_name'])) {
                $profile->short_name = $profile->full_name = $input['last_name'];
            } else {
                $profile->short_name = $profile->full_name = null;
            }
            if (!empty($input['address'])) {
                $profile->address = $input['address'];
            }
            if (!empty($input['temp_address'])) {
                $profile->temp_address = $input['temp_address'];
            }
            if (!empty($input['registed_address'])) {
                $profile->registed_address = $input['registed_address'];
            }
            if (!empty($input['marital_status'])) {
                $profile->marital_status = $input['marital_status'];
            }
            if (!empty($input['work_experience'])) {
                $profile->work_experience = $input['work_experience'];
            }
            if (!empty($input['email'])) {
                $profile->email = $input['email'];
                $users->email   = $input['email'];
            }
            if (!empty($input['phone'])) {
                $profile->phone = $input['phone'];
                $users->phone   = $users->code = $input['phone'];
            }
            if (!empty($input['birthday'])) {
                $birthday          = date('Y-m-d', strtotime($input['birthday']));
                $profile->birthday = $birthday;
            }
            if (!empty($input['city_code'])) {
                $profile->city_code = $input['city_code'];
            }
            if (!empty($input['district_code'])) {
                $profile->district_code = $input['district_code'];
            }
            if (!empty($input['ward_code'])) {
                $profile->ward_code = $input['ward_code'];
            }
            //            if (!empty($input['avatar'])) {
            //                $avatar = explode(';base64,', $input['avatar']);
            //                if (empty($avatar[1])) {
            //                    return $this->response->errorBadRequest(Message::get("V002", Message::get("avatar")));
            //                }
            //                $data = base64_decode($avatar[1]);
            //                if (!empty($avatar[1])) {
            //                    file_put_contents(public_path() . "/uploads/avatar/user-{$input['user_id']}.png",
            //                        $data);
            //                    $profile->avatar = "uploads,avatar,user-{$input['user_id']}.png";
            //                }
            //            }
            if (!empty($input['avatar'])) {
                $profile->avatar = $input['avatar'];
            }
            if (!empty($input['gender'])) {
                $profile->gender = $input['gender'];
            }
            if (!empty($input['id_number'])) {
                $profile->id_number = $input['id_number'];
            }
            if (!empty($input['id_number_at'])) {
                $profile->id_number_at = $input['id_number_at'];
            }
            if (!empty($input['id_number_place'])) {
                $profile->id_number_place = $input['id_number_place'];
            }
            if (!empty($input['transaction_total'])) {
                $profile->transaction_total = $input['transaction_total'];
            }
            if (!empty($input['transaction_cancel'])) {
                $profile->transaction_cancel = $input['transaction_cancel'];
            }
            if (!empty($input['point'])) {
                $profile->point = $input['point'];
            }
            if (!empty($input['point_type'])) {
                $profile->point_type = $input['point_type'];
            }
            if (!empty($input['money_total'])) {
                $profile->money_total = $input['money_total'];
            }

            $full                = explode(" ", $input['full_name']);
            $profile->full_name  = $input['full_name'];
            $profile->first_name = trim($full[count($full) - 1]);
            unset($full[count($full) - 1]);
            $profile->last_name = trim(implode(" ", $full));

            $profile->created_at = $now;
            $profile->created_by = $input['user_id'];
            $users->name         = $input['full_name'];
            $users->created_at   = $now;
            $users->created_by   = $input['user_id'];
            $profile->save();
            $users->save();
            if (!empty($input['phone'])) {
                $cusInfo = CustomerInformation::where([
                    'phone'    => $input['phone'],
                    'store_id' => TM::getCurrentStoreId()
                ])->first();

                if ($cusInfo) {
                    if (!empty($input['full_name'])) {
                        $cusInfo->name = $input['full_name'];
                    }
                    $cusInfo->phone = $input['phone'];
                    if (!empty($input['email'])) {
                        $cusInfo->email = $input['email'];
                    }
                    if (!empty($input['address'])) {
                        $cusInfo->address = $input['address'];
                    }
                    if (!empty($input['city_code'])) {
                        $cusInfo->city_code = $input['city_code'];
                    }
                    $cusInfo->store_id = TM::getCurrentStoreId();
                    if (!empty($input['district_code'])) {
                        $cusInfo->district_code = $input['district_code'];
                    }
                    if (!empty($input['ward_code'])) {
                        $cusInfo->ward_code = $input['ward_code'];
                    }
                    if (!empty($input['address'])) {
                        $cusInfo->full_address   = $input['address'];
                        $cusInfo->street_address = $input['address'];
                    }
                    $cusInfo->note   = $input['note'] ?? null;
                    $cusInfo->gender = $input['gender'] ?? null;
                    $cusInfo->update();
                } else {
                    CustomerInformation::insert(
                        [
                            'name'           => $input['full_name'] ?? null,
                            'phone'          => $input['phone'] ?? null,
                            'email'          => $input['email'] ?? null,
                            'address'        => $input['address'] ?? null,
                            'city_code'      => $input['city_code'] ?? null,
                            'store_id'       => TM::getCurrentStoreId(),
                            'district_code'  => $input['district_code'] ?? null,
                            'ward_code'      => $input['ward_code'] ?? null,
                            'full_address'   => $input['address'] ?? null,
                            'street_address' => $input['address'] ?? null,
                            'note'           => $input['note'] ?? null,
                            'gender'         => $input['gender'] ?? null,
                        ]
                    );
                }
            }
            Log::update($this->model->getTable(), "#ID:" . $users->id);
            Log::update($this->profileModel->getTable(), "#ID:" . $profile->id);


            #CDP
            if (in_array($users->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])) {
                try {
                    CDP::pushCustomerCdp($users, 'updateProfile - UserController - line: 438');
                } catch (\Exception $exception) {
                    TM_Error::handle($exception);
                }
            }

            #NNDD
            if(empty($input['source'])){
                try {
                    $client = new Client();
                    $url = env('URL_NNDD', 'https://nutifood-virtualstore-api.dev.altasoftware.vn');
                    $api_key = env('API_KEY_NNDD', "c869c601-589a-424d-9f89-f5d5f5eb3f24");
                    
                    $request_nndd = [
                        "FullName" => $profile->full_name,
                        "EmailAddress" => $profile->email,
                        'address' => $profile->address,
                        "OldPassword" => $input['old_password'] ?? null,
                        "NewPassword" => $input['password'] ?? null,
                    ];
                    // dd($request_nndd);
                    $response = $client->request('PUT', $url."/api/Customers/Sync/". $profile->phone, [
                        'verify' => false,
                        'headers' => [
                            'X-API-Key' => $api_key,
                            'Content-Type' => 'application/json'
                        ],
                        'body' => json_encode($request_nndd)
                    ]);
                    $res = $response->getBody()->getContents();
                    NNDDController::writeLogNNDD("Đồng bộ khách hàng", null, $request_nndd, $res, null, $request_nndd['PhoneNumber'] ?? $profile->phone, "SUCCESS", "UserController - updateProfile - line: 435", null);
                } catch (\Exception $exception) {
                    NNDDController::writeLogNNDD("Đồng bộ khách hàng", null, $request_nndd, $res ?? $exception, null, $request_nndd['PhoneNumber'] ?? $profile->phone, "FAILED", "UserController - updateProfile - line: 435", null);
                    TM_Error::handle($exception);
                }
            }

            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("users.update-success", Message::get("profile"))]; 
    }

    /// Update Phone
    public function updatePhoneUserOTP(Request $request)
    {
        $input = $request->all();
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (empty($input['code_otp'])) {
            return $this->responseError(Message::get("V001", Message::get("sms_code_otp")), 422);
        }
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }
        if (empty($input['group_id'])) {
            return $this->responseError(Message::get("V001", Message::get("group_id")), 422);
        }
        if (empty($input['group_code'])) {
            return $this->responseError(Message::get("V001", Message::get("group_code")), 422);
        }
        if (empty($input['group_name'])) {
            return $this->responseError(Message::get("V001", Message::get("group_name")), 422);
        }
        $user = User::model()->where('phone', $input['phone'])->first();
        if (!empty($user)) {
            return $this->responseError(Message::get("V003", Message::get("phone")), 422);
        }
        try {
            DB::beginTransaction();
            if ($input['social_type'] == "FACEBOOK") {
                $typeId = 'fb_id';
            } elseif ($input['social_type'] == "GOOGLE") {
                $typeId = 'gg_id';
            } else {
                $typeId = 'zl_id';
            }
            $user_check_otp = User::model()->where([
                $typeId      => $input['id'],
                'store_id'   => $store_id,
                'company_id' => $company_id,
                'verify_sms_code' => $input['code_otp']
            ])->first();
            if (empty($user_check_otp)) {
                return $this->responseError(Message::get("V003", Message::get("sms_code_incorrect")), 422);
            }
            //            if (strtotime("-2 minutes") < strtotime($user_check_otp->sms_at)) {
            //                // Don't Send Message
            //                return $this->responseError(Message::get('sms_code_effect'));
            //            }
            $user_check_otp->phone      = $input['phone'];
            $user_check_otp->code       = $input['phone'];
            //            $user_check_otp->group_id   = $input['group_id'];
            //            $user_check_otp->group_code = $input['group_code'];
            //            $user_check_otp->group_name = $input['group_name'];
            $user_check_otp->updated_by = TM::getCurrentUserId();
            $user_check_otp->save();
            $profile        = Profile::model()->where('user_id', $user_check_otp->id)->first();
            $profile->phone = $user_check_otp->phone;
            $profile->save();
            $now = time();
            $session = UserSession::where('user_id', $user_check_otp->id)->first();
            $token = $session->token;
            CustomerInformation::insert(
                [
                    'name'     => $profile->full_name ?? null,
                    'phone'    => $input['phone'] ?? null,
                    'email'    => $profile->email ?? null,
                    'store_id' => $store_id ?? null
                ]
            );
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
        return response()->json([
            'token'          => $token
        ]);
    }

    public function updatePhoneUserSMS(Request $request)
    {
        $input = $request->all();
        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        if (empty($input['phone'])) {
            return $this->responseError(Message::get("V001", Message::get("phone")), 422);
        }
        $check_phone = User::model()->where([
            'phone'      => $input['phone'],
            'store_id'   => $store_id,
            'company_id' => $company_id
        ])->first();
        if (!empty($check_phone)) {
            return $this->responseError(Message::get('phone_already_exists'));
        }
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
            if ($input['social_type'] == "FACEBOOK") {
                $typeId = 'fb_id';
            } elseif ($input['social_type'] == "GOOGLE") {
                $typeId = 'gg_id';
            } else {
                $typeId = 'zl_id';
            }
            $user = User::model()->where([
                $typeId      => $input['id'],
                'store_id'   => $store_id,
                'company_id' => $company_id,
                'is_active'  => 1
            ])->first();
            //            $user = User::find(TM::getCurrentUserId());
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
            //            $message = Message::get('SMS-REGISTER-ORDER', $smsCode);
            //            $this->sendSMSCode($message, $input['phone']);
            $this->sendSMSCode(Message::get('SMS-REGISTER', $smsCode),  $input['phone']);
            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            return $this->responseError($ex->getMessage(), 500);
        }
    }


    public function updateProfileLinkLogin(Request $request)
    {
        $input = $request->all();
        if ($input['social_type'] == "FACEBOOK") {
            $typeId = 'fb_id';
        } else {
            $typeId = 'gg_id';
        }
        $user            = User::find(TM::getCurrentUserId());
        $user->{$typeId} = $input['id'];
        $user->save();
        return ['status' => Message::get("users.link-login-success", $input['social_type'])];
    }

    public function changeMyProfile(Request $request, ChangeMyProfileValidator $changeMyProfileValidator)
    {
        $input            = $request->all();
        $input['user_id'] = TM::getCurrentUserId();
        $changeMyProfileValidator->validate($input);

        try {
            DB::beginTransaction();
            $profile = Profile::model()->where('user_id', $input['user_id'])->first();
            $user    = User::find($input['user_id']);
            $now     = date("Y-m-d H:i:s", time());

            if (empty($profile)) {
                $profile             = new Profile();
                $profile->created_at = $now;
                $profile->created_by = $input['user_id'];
            }
            $full = explode(" ", $input['name']);

            $profile->full_name  = $input['name'];
            $profile->first_name = trim($full[count($full) - 1]);
            unset($full[count($full) - 1]);
            $profile->last_name = trim(implode(" ", $full));

            if (!empty($input['email'])) {
                $input['email'] = trim($input['email']);
                $user->email    = $input['email'];
                $profile->email = $input['email'];
            }

            if (!empty($input['address'])) {
                $profile->address = $input['address'];
            }

            if (!empty($input['phone'])) {
                $profile->phone = $input['phone'];
                $user->phone    = $user->code = $input['phone'];
            }

            if (!empty($input['avatar_upload'])) {
                $avatar = explode(';base64,', $input['avatar_upload']);
                if (empty($avatar[1])) {
                    return $this->response->errorBadRequest(Message::get("V002", Message::get("avatar")));
                }
                $data = base64_decode($avatar[1]);
                if (!empty($avatar[1])) {
                    if (!file_exists(public_path() . "/uploads/avatar")) {
                        mkdir(public_path() . "/uploads/avatar", 0777, true);
                    }
                    file_put_contents(
                        public_path() . "/uploads/avatar/user-{$input['user_id']}.png",
                        $data
                    );
                    $profile->avatar = "uploads,avatar,user-{$input['user_id']}.png";
                }
            }
            if (!empty($input['password'])) {
                $user->password = password_hash($input['password'], PASSWORD_BCRYPT);
                Log::update($this->model->getTable(), "#ID:" . $user->id . " change password");
            }


            $profile->created_at = $now;
            $profile->created_by = $input['user_id'];
            $user->updated_at    = $now;
            $user->updated_by    = $input['user_id'];
            $profile->save();
            $user->name = $input['name'];
            $user->save();

            # CDP
            if (in_array($user->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])) {
                try {
                    CDP::pushCustomerCdp($user, 'changeMyProfile - UserController - line: 784');
                } catch (\Exception $exception) {
                    TM_Error::handle($exception);
                }
            }

            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("users.update-success", Message::get("profile"))];
    }

    public function changePassword(Request $request, UserChangePasswordValidator $userChangePasswordValidator)
    {
        $input   = $request->all();
        $user_id = TM::getCurrentUserId();
        $userChangePasswordValidator->validate($input);
        try {
            DB::beginTransaction();
            $user = User::find($user_id);
            // Change password
            if (!password_verify($input['password'], $user->password)) {
                return $this->responseError(Message::get("V002", Message::get("password")));
            }
            if (password_verify($input['new_password'], $user->password)) {
                return $this->responseError(Message::get("users.is-vail-password"));
            }
            $smsVerify = $this->getSMSVerify($user->phone);
            if (empty($smsVerify)) {
                return $this->responseError(Message::get('phone_not_sms_code'));
            }

            if ($smsVerify->code != $request->input('sms_code')) {
                return $this->responseError(Message::get('sms_code_incorrect'));
            }

            if (strtotime('-3 minutes') > $smsVerify->time) {
                return $this->responseError(Message::get('sms_code_effect'));
            }
            $user->password = password_hash($input['new_password'], PASSWORD_BCRYPT);
            $user->save();

            #CDP
            if (in_array($user->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])) {
                try {
                    CDP::pushCustomerCdp($user, 'changePassword - UserController - line: 871');
                } catch (\Exception $exception) {
                    TM_Error::handle($exception);
                }
            }

            DB::table('sms_verify')->where('phone', $user->phone)->delete();
            Log::update($this->model->getTable(), "#ID:" . $user->id . " change password");
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("users.change-password")];
    }

    public function changePassword2(Request $request, UserChangePasswordValidator $userChangePasswordValidator)
    {
        $input   = $request->all();
        $user_id = TM::getCurrentUserId();
        $userChangePasswordValidator->validate($input);
        try {
            DB::beginTransaction();
            $user = User::find($user_id);
            // Change password
            if (!password_verify($input['password'], $user->password)) {
                return $this->responseError(Message::get("V002", Message::get("password")));
            }
            if (password_verify($input['new_password'], $user->password)) {
                return $this->responseError(Message::get("users.is-vail-password"));
            }
            // $smsVerify = $this->getSMSVerify($user->phone);
            // if (empty($smsVerify)) {
            //     return $this->responseError(Message::get('phone_not_sms_code'));
            // }

            // if ($smsVerify->code != $request->input('sms_code')) {
            //     return $this->responseError(Message::get('sms_code_incorrect'));
            // }

            // if (strtotime('-3 minutes') > $smsVerify->time) {
            //     return $this->responseError(Message::get('sms_code_effect'));
            // }
            $user->password = password_hash($input['new_password'], PASSWORD_BCRYPT);
            $user->save();
            // DB::table('sms_verify')->where('phone', $user->phone)->delete();
            Log::update($this->model->getTable(), "#ID:" . $user->id . " change password");
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get("users.change-password")];
    }

    public function active($id)
    {
        $user = User::find($id);
        if (empty($user)) {
            return $this->response->errorBadRequest(Message::get("users.not-exist", "#$id"));
        }

        try {
            DB::beginTransaction();
            if ($user->is_active === 1) {
                $msgCode         = "users.inactive-success";
                $user->is_active = "0";
            } else {
                $user->is_active = "1";
                $msgCode         = "users.active-success";
            }
            $user->save();
            Log::update($this->model->getTable(), "#ID:" . $user->id);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest(Message::get($msgCode, $user->phone));
        }
        return ['status' => Message::get($msgCode, $user->phone)];
    }

    public function changeAccountStatus($id, Request $request)
    {
        $user           = User::find($id);
        $input          = $request->all();
        $account_status = $request->input(['account_status']);
        if (empty($user)) {
            return $this->response->errorBadRequest(Message::get("users.not-exist", "#$id"));
        }
        if (empty($account_status)) {
            return $this->response->errorBadRequest(Message::get("V001", Message::get("status")));
        }
        if (!in_array($account_status, [ACCOUNT_STATUS_PENDING, ACCOUNT_STATUS_REJECTED, ACCOUNT_STATUS_APPROVED])) {
            return $this->response->errorBadRequest(Message::get("V003", Message::get("status")));
        }
        try {
            DB::beginTransaction();
            switch ($account_status) {
                case ACCOUNT_STATUS_PENDING:
                    $msgCode              = "đang chờ xử lý";
                    $msgMail              = "Tài khoản của bạn đang được xử lý. Thông tin đăng ký của bạn:";
                    $user->account_status = ACCOUNT_STATUS_PENDING;
                    break;
                case ACCOUNT_STATUS_REJECTED:
                    $msgCode              = "đã bị từ chối";
                    $msgMail              = "Tài khoản của bạn đã bị từ chối. Vui lòng liên hệ với quản trị viên để biết thêm chi tiết. Thông tin đăng ký của bạn:";
                    $user->account_status = ACCOUNT_STATUS_REJECTED;
                    break;
                case ACCOUNT_STATUS_APPROVED:
                    $msgCode = "đã duyệt";
                    $msgMail = "Xin chúc mừng, hồ sơ đăng ký của bạn đã được phê duyệt, giờ đây bạn có thể đăng nhập vào ứng dụng với thông tin:";
                    if ($input['group_code'] == USER_GROUP_AGENT && $user->agent_register == 1) {
                        $group = UserGroup::model()->where(['company_id' => $user->company_id, 'code' => USER_GROUP_AGENT])->first();
                        if (!isset($group)) {
                            return $this->responseError(Message::get("V043", Message::get("stores"), Message::get('user_groups')), 400);
                        }
                        $user->group_id   = $group->id;
                        $user->group_code = $group->code;
                        $user->group_name = $group->name;
                    }
                    $user->account_status = ACCOUNT_STATUS_APPROVED;

                    //Save Info Customer
                    $cusInfo = CustomerInformation::where([
                        'phone'    => $user->phone,
                        'store_id' => TM::getCurrentStoreId()
                    ])->first();
                    if ($cusInfo) {
                        $cusInfo->name           = Arr::get($user, 'name', $cusInfo->name);
                        $cusInfo->phone          = Arr::get($user, 'phone', $cusInfo->phone);
                        $cusInfo->email          = Arr::get($user, 'email', $cusInfo->email);
                        $cusInfo->address        = Arr::get($user, 'profile.address', $cusInfo->address);
                        $cusInfo->city_code      = Arr::get($user, 'profile.city_code', $cusInfo->city_code);
                        $cusInfo->store_id       = TM::getCurrentStoreId();
                        $cusInfo->district_code  = Arr::get($user, 'profile.district_code', $cusInfo->district_code);
                        $cusInfo->ward_code      = Arr::get($user, 'profile.ward_code', $cusInfo->ward_code);
                        $cusInfo->full_address   = Arr::get($user, 'profile.address', $cusInfo->address);
                        $cusInfo->street_address = Arr::get($user, 'profile.address', $cusInfo->street_address);
                        $cusInfo->gender         = Arr::get($user, 'profile.gender', $cusInfo->gender);
                        $cusInfo->update();
                    } else {
                        CustomerInformation::insert(
                            [
                                'name'           => Arr::get($user, 'name', null),
                                'phone'          => Arr::get($user, 'phone', null),
                                'email'          => Arr::get($user, 'email', null),
                                'address'        => Arr::get($user, 'profile.address', null),
                                'city_code'      => Arr::get($user, 'profile.city_code', null),
                                'district_code'  => Arr::get($user, 'profile.district_code', null),
                                'ward_code'      => Arr::get($user, 'profile.ward_code', null),
                                'full_address'   => Arr::get($user, 'profile.address', null),
                                'street_address' => Arr::get($user, 'profile.address', null),
                                'gender'         => Arr::get($user, 'profile.gender', null),
                                'store_id'       => TM::getCurrentStoreId(),
                            ]
                        );
                    }

                    break;
            }

            $user->save();
            //Send Mail
            if (!empty($user->email)) {
                $mail = [
                    'to'        => $user->email,
                    'user_name' => $user->name,
                    'msg'       => $msgMail,
                    'phone'     => $user->phone,
                    'password'  => "***** (bạn đã tạo)",
                ];
                if (env('SEND_EMAIL', 0) == 1) {
                    dispatch(new SendMailChangeAccountStatusJob($mail));
                }
            }
            #CDP
            if (in_array($user->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR', 'LEAD', 'GUEST'])) {
                try {
                    CDP::pushCustomerCdp($user, 'changeAccountStatus - UserController - line: 985');
                } catch (\Exception $exception) {
                    TM_Error::handle($exception);
                }
            }
            Log::update($this->model->getTable(), "#ID:" . $user->id);
            DB::commit();
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest(Message::get($msgCode, $user->phone));
        }
        return ['status' => "Tài khoản [$user->phone] $msgCode"];
    }

    public function readyWork(Request $request)
    {
        $input   = $request->all();
        $profile = Profile::model()->where('user_id', TM::getCurrentUserId())->first();
        if (empty($profile)) {
            return $this->response->errorBadRequest(Message::get(
                "user_profiles.not-exist",
                "#" . TM::getCurrentUserId()
            ));
        }
        try {
            if (isset($input['ready_work'])) {
                DB::beginTransaction();
                if ($input['ready_work'] == 1) {
                    $ready_work          = 1;
                    $msgCode             = "user_profiles.profile-active-success";
                    $profile->ready_work = $ready_work;
                } elseif ($input['ready_work'] == 0) {
                    $ready_work          = 0;
                    $msgCode             = "user_profiles.profile-inactive-success";
                    $profile->ready_work = $ready_work;
                } else {
                    $msgCode = "user_profiles.profile-change-error";
                }
                $profile->save();
                Log::update($this->profileModel->getTable(), "#ID:" . $profile->id);
                DB::commit();
            } else {
                $msgCode = "user_profiles.profile-change-error";
            }
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return ['status' => Message::get($msgCode)];
    }

    public function membership($id, MembershipTransformer $membershipTransformer)
    {
        $user = User::find($id);
        if (empty($user)) {
            return $this->response->errorBadRequest(Message::get("users.not-exist", "#$id"));
        }
        return $this->response->item($user, $membershipTransformer);
    }

    public function getPoint($user_id)
    {

        $user = User::find($user_id);
        if (empty($user)) {
            return $this->response->errorBadRequest(Message::get("users.not-exist", "#$user_id"));
        }

        $typeUser = $user->type == USER_TYPE_PARTNER ? 'partner' : ($user->type == USER_TYPE_CUSTOMER ? 'customer' : null);
        if (empty($typeUser)) {
            return $this->response->errorBadRequest(Message::get("V002", "User"));
        }

        $orders = Order::model()->where($typeUser . "_id", $user_id)->whereNotNull($typeUser . "_point")->get();
        $data   = [];
        foreach ($orders as $order) {
            $data[] = [
                'point'          => $order->{$typeUser . "_point"},
                'order_id'       => $order->id,
                'order_code'     => $order->code,
                'created_date'   => date('d-m-Y H:i', strtotime($order->created_at)),
                'updated_date'   => date('d-m-Y H:i', strtotime($order->updated_at)),
                'completed_date' => date('d-m-Y H:i', strtotime($order->completed_date)),
                'user_id'        => $user_id,
                'user_name'      => object_get($order, $typeUser . ".profile.full_name"),
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getMyPoint()
    {
        $userId   = TM::getCurrentUserId();
        $user     = User::find($userId);
        $typeUser = $user->type == USER_TYPE_PARTNER ? 'partner' : ($user->type == USER_TYPE_CUSTOMER ? 'customer' : null);
        if (empty($typeUser)) {
            return $this->response->errorBadRequest(Message::get("V002", "User"));
        }
        $orders = Order::model()->where($typeUser . "_id", $userId)->whereNotNull($typeUser . "_point")->get();
        $data   = [];
        foreach ($orders as $order) {
            $data[] = [
                'point'                 => $order->{$typeUser . "_point"},
                'order_id'              => $order->id,
                'order_code'            => $order->code,
                'order_price'           => $order->total_price,
                'order_price_formatted' => number_format($order->total_price) . " đ",
                'completed_date'        => date('d-m-Y H:i', strtotime($order->completed_date)),
                'updated_date'          => date('d-m-Y H:i', strtotime($order->updated_at)),
                'created_date'          => date('d-m-Y H:i', strtotime($order->created_at)),
                'user_id'               => $userId,
                'user_name'             => object_get($order, $typeUser . ".profile.full_name"),
            ];
        }
        return response()->json(['data' => $data]);
    }

    public function getMyRating()
    {
        return response()->json(['star' => $this->getMyStar()]);
    }

    public function getMyPaymentHistory(Request $request, UserPaymentHistoryTransformer $userPaymentHistoryTransformer)
    {
        $input     = $request->all();
        $limit     = array_get($input, 'limit', 20);
        $myHistory = PaymentHistory::model()->select([
            'id',
            'transaction_id',
            'date',
            'type',
            'method',
            'status',
            'content',
            'total_pay',
        ])->where('user_id', TM::getCurrentUserId())->orderBy('id', 'desc')->paginate($limit);
        return $this->response->paginator($myHistory, $userPaymentHistoryTransformer);
    }

    public function orderStatistic()
    {
        $user     = TM::info();
        $typeUser = $user['type'] == "USER" ? null : ($user['type'] == USER_TYPE_PARTNER ? 'partner' : 'customer');
        if (empty($typeUser)) {
            return $this->response->errorBadRequest(Message::get("V002", "User"));
        }

        $completedOrder = Order::model()->where('status', ORDER_STATUS_COMPLETED)
            ->where($typeUser . '_id', TM::getCurrentUserId())
            ->get()->toArray();
        $receivedOrder  = Order::model()->where('status', ORDER_STATUS_RECEIVED)
            ->where($typeUser . '_id', TM::getCurrentUserId())
            ->get()->toArray();
        $totalPrice     = Order::model()->select(DB::raw('sum(total_price) as total_price'))
            ->where('status', ORDER_STATUS_COMPLETED)
            ->where($typeUser . '_id', TM::getCurrentUserId())
            ->first()->toArray();

        $userStatusOrder = UserStatusOrder::model()->select([DB::raw("count(status) as qty"), "status"])
            ->where('user_id', TM::getCurrentUserId())->groupBy('status')->get()->pluck("qty", "status")->toArray();

        $receiveQty = $userStatusOrder["RECEIVED"] ?? 0;
        $cancelQty  = $userStatusOrder["CANCELLED"] ?? 0;

        $rejectOrder = UserStatusOrder::model()->select(DB::raw('count(order_id) as reject'))
            ->where('user_id', TM::getCurrentUserId())
            ->groupBy('order_id')->first();

        return [
            "data" => [
                "completed"          => count($completedOrder),
                "received"           => count($receivedOrder),
                "total_revenue"      => $totalPrice['total_price'] ?? 0,
                "receive_per_cancel" => $receiveQty . "/" . $cancelQty,
                "reject_order"       => $rejectOrder->reject ?? 0,
            ],
        ];
    }

    public function personalIncome($id, Request $request)
    {
        $input = $request->all();
        if (empty($input['from']) || count(explode('-', $input['from'])) <= 1) {
            return $this->responseError(Message::get("V002", "Date from"));
        }
        if (empty($input['to']) || $countTo = count(explode('-', $input['to'])) <= 1) {
            return $this->responseError(Message::get("V002", "Date to"));
        }
        if ($countTo < 3) {
            $input['to'] = date("Y-m-t 23:59:59", strtotime($input['to']));
        }
        $user = User::find($id);
        if (empty($user)) {
            return ['data' => []];
        }
        $order = Order::model()->where('partner_id', $id)
            ->where('status', ORDER_STATUS_COMPLETED)
            ->whereDate('orders.completed_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
            ->whereDate('orders.completed_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
            ->select([DB::raw("sum(total_price) as total_price"), DB::raw("count(id) as total_order")])
            ->get()->toArray();
        $data  = [
            'total'       => $order[0]['total_price'] ?? 0,
            'total_order' => $order[0]['total_order'] ?? 0,
        ];
        return response()->json(['data' => $data]);
    }

    public function personalIncomePartner(Request $request)
    {
        $input = $request->all();
        if (empty($input['from']) || count(explode('-', $input['from'])) <= 1) {
            return $this->responseError(Message::get("V002", "Date from"));
        }
        if (empty($input['to']) || $countTo = count(explode('-', $input['to'])) <= 1) {
            return $this->responseError(Message::get("V002", "Date to"));
        }
        if ($countTo < 3) {
            $input['to'] = date("Y-m-t 23:59:59", strtotime($input['to']));
        }
        $userId = TM::getCurrentUserId();
        if (empty($userId)) {
            return ['data' => []];
        }
        $order = Order::model()->where('partner_id', $userId)
            ->where('status', ORDER_STATUS_COMPLETED)
            ->whereDate('orders.completed_date', '>=', date("Y-m-d 00:00:00", strtotime($input['from'])))
            ->whereDate('orders.completed_date', '<=', date("Y-m-d 23:59:59", strtotime($input['to'])))
            ->select([DB::raw("sum(total_price) as total_price"), DB::raw("count(id) as total_order")])
            ->get()->toArray();
        $data  = [
            'total'       => $order[0]['total_price'] ?? 0,
            'total_order' => $order[0]['total_order'] ?? 0,
        ];
        return response()->json(['data' => $data]);
    }

    public function listPartnerFreeTime()
    {
        $userReadyWork = User::model()->select([
            'profiles.lat',
            'profiles.long',
            'profiles.full_name',
            'users.phone',
            'users.id',
            'profiles.ready_work',
        ])
            ->join('profiles', 'profiles.user_id', '=', 'users.id')
            ->join('user_sessions as us', 'us.user_id', '=', 'users.id')
            ->leftJoin('orders as o', 'o.partner_id', 'us.user_id')
            ->where(function ($q) {
                $q->where('o.status', ORDER_STATUS_COMPLETED)
                    ->orWhereNull('o.status');
            })
            ->where('users.type', USER_TYPE_PARTNER)
            ->where('profiles.ready_work', 1)
            ->whereNotNull('us.socket_id')
            ->where('us.socket_id', '!=', '')
            ->where('us.deleted', 0)
            ->groupBy('us.user_id')
            ->get();
        return response()->json(['data' => $userReadyWork]);
    }

    public function listPartnerWorking()
    {
        $userWorking = User::model()
            ->select(['p.lat', 'p.long', 'p.full_name', 'users.phone', 'users.id', 'p.ready_work'])
            ->join('profiles as p', 'p.user_id', '=', 'users.id')
            ->join('user_sessions as us', 'us.user_id', '=', 'users.id')
            ->join('orders as o', 'o.partner_id', '=', 'us.user_id')
            ->where('o.status', '!=', ORDER_STATUS_COMPLETED)
            ->where('users.type', USER_TYPE_PARTNER)
            ->where('p.ready_work', 1)
            ->whereNotNull('us.socket_id')
            ->where('us.socket_id', '!=', '')
            ->where('us.deleted', 0)
            ->groupBy('us.user_id')
            ->get();
        return response()->json(['data' => $userWorking]);
    }

    private function getMyStar()
    {
        $user     = TM::info();
        $typeUser = $user['type'] == "USER" ? null : ($user['type'] == USER_TYPE_PARTNER ? 'partner' : 'customer');
        if (empty($typeUser)) {
            return $this->response->errorBadRequest(Message::get("V002", "User"));
        }
        $orders = Order::model()->where($typeUser . "_id", $user['id'])
            ->whereNotNull($typeUser . "_star")
            ->where($typeUser . "_star", '>', '0')
            ->where($typeUser . "_star", '!=', '')
            ->get()->toArray();

        if (empty($orders)) {
            return null;
        }

        $stars = [];
        foreach ($orders as $order) {
            $stars[] = $order[$typeUser . "_star"];
        }

        $avg = round(array_sum($stars) / count($stars), 1);

        return $avg;
    }

    public function setActiveCompany($companyId)
    {
        try {
            DB::beginTransaction();
            $myCompanyId = UserCompany::model()->where('user_id', TM::getCurrentUserId())->get()
                ->pluck('company_name', 'company_id')->toArray();
            if (!key_exists($companyId, $myCompanyId)) {
                return $this->responseError(Message::get("V002", "Company ID"));
            }

            $user             = User::find(TM::getCurrentUserId());
            $user->company_id = $companyId;
            $user->save();

            DB::commit();
        } catch (\Exception $ex) {
            DB::rollBack();
            if (env('APP_ENV') == 'testing') {
                return $this->response->errorBadRequest($ex->getMessage());
            } else {
                return $this->response->errorBadRequest(Message::get("R011"));
            }
        }

        return ['status' => 'OK', 'message' => "Company [{$myCompanyId[$companyId]}] is now active"];
    }

    public function viewTree($id)
    {
        $user = User::find($id);
        if (empty($user->level_number)) {
            return $this->responseData();
        }

        $userLevel1 = $this->model->getUserLevel1FromUser($user);
        if (empty($userLevel1)) {
            return $this->responseData();
        }

        // Get Tree
        $data = $this->model->getUserTree($id, $userLevel1, [
            'id',
            'code',
            'name',
            'email',
            'level_number',
            'user_level1_id',
            'user_level2_ids',
            'user_level3_ids'
        ]);

        return $this->responseData($data);
    }

    ########################################### NOT AUTHENTICATION ############################################

    public function getClientBusinessResult(Request $request)
    {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
            $group_id = TM::getCurrentGroupId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }

        // $myUserCode = array_get(TM::info(), 'code');

        $input             = $request->all();
        $total_discount    = PromotionTotal::model()->select([DB::raw("sum(value) as total_discount")])
            ->where([
                'order_customer_id' => TM::getCurrentUserId(),
                'approval_status'   => 'APPROVED',
                'promotion_type'    => 'DISCOUNT',
                'store_id'          => $store_id
            ])->first();
        $total_order_price = Order::model()->select([DB::raw("sum(total_price) as total_price")])
            ->where('customer_id', TM::getCurrentUserId())
            ->where('store_id', $store_id)
            ->where('status', '!=', ORDER_STATUS_NEW)
            ->first();

        return [
            'data' => [
                'total_discount' => $total_discount->total_discount ?? 0,
                'total_price'    => $total_order_price->total_price ?? 0
            ]
        ];
    }

    ################################### GET USER HUB ############################################
    public function searchUserHub($id, UserZoneHubTransformer $userZoneHubTransformer)
    {
        $zoneHub = ZoneHub::model()->where('user_id', $id)->first();
        if (empty($zoneHub)) {
            return ['data' => null];
        }
        $userZoneHub = User::find($id);
        if (empty($userZoneHub)) {
            return ['data' => null];
        }
        return $this->response->item($userZoneHub, $userZoneHubTransformer);
    }

    public function getUserReference(Request $request)
    {
        $userReference = UserReference::with(['grandChildren', 'user:id,name,email,phone,store_id,code'])->where([
            'level'    => 1,
            'store_id' => TM::getCurrentStoreId()
        ])->paginate($request->input('limit', 20));
        return $this->response->paginator($userReference, new UserReferenceTransformer());
    }

    public function getClientUserListByPhone(
        $phone,
        UserCLientByPhoneTransformer $userCLientByPhoneTransformer,
        Request $request
    ) {
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id = TM::getCurrentStoreId();
            $group_id = TM::getCurrentGroupId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id = $store->id;
                }
            }
        }

        $input['phone'] = $phone;
        $limit          = array_get($input, 'limit', 20);

        $user = $this->model->search($input, ['profile'], $limit);

        Log::view($this->model->getTable());
        return $this->response->paginator($user, $userCLientByPhoneTransformer);
    }

    public function getClientUserByPhone($phone, Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => null];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        $cusInfo = CustomerInformation::with(['user'])->where([
            'phone'    => $phone,
            'store_id' => $store_id
        ]);
        $cusInfo->whereHas('user', function ($q) use ($input, $store_id, $company_id) {
            if (!empty($input['group_code'])) {
                $q->where('users.group_code', $input['group_code']);
            }
            if (!empty($input['account_status'])) {
                $q->where('users.account_status', $input['account_status']);
            }
            $q->where('store_id', $store_id)->where('company_id', $company_id)
                ->whereNull('deleted_at');
        });
        $user = $cusInfo->first();

        if (empty($user)) {
            return ['data' => null];
        }
        $user['group_code'] = $user->user->group_code ?? null;
        $user['group_name'] = $user->user->group_name ?? null;
        $user['is_new']     = $user->user->password == 'FROM-IMPORT' || $user->user->password == 'NOT-VERIFY-ACCOUNT' ? 0 : 1;

        Log::view($this->model->getTable());
        return response()->json(['data' => $user]);
    }
    public function checkClientByPhone($phone, Request $request)
    {
        return response()->json(['data' => true]);
    }
    public function checkClientPhoneExistedByUser($phone, Request $request)
    {
        $input    = $request->all();
        $store_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => null];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        $cusInfo = CustomerInformation::with(['user'])->where([
            'phone'    => $phone,
            'store_id' => $store_id
        ]);
        $cusInfo->whereHas('user', function ($q) use ($input, $store_id, $company_id) {
            if (!empty($input['group_code'])) {
                $q->where('users.group_code', $input['group_code']);
            }
            $q->where(function ($d) {
                $d->where('users.type', ORDER_TYPE_CUSTOMER)->orwhere('users.role_id', 18);
                $d->whereNotIn('users.group_code', [USER_GROUP_DISTRIBUTOR, USER_GROUP_HUB, USER_GROUP_DISTRIBUTOR_CENTER])->orwhereNull('users.group_code');
            });
            //            $q->where('users.type',ORDER_TYPE_CUSTOMER);
            //            $q->whereNotIn('users.group_code',[USER_GROUP_DISTRIBUTOR,USER_GROUP_HUB,USER_GROUP_DISTRIBUTOR_CENTER]);
            if (!empty($input['account_status'])) {
                $q->where('users.account_status', $input['account_status']);
            }
            $q->where('store_id', $store_id)->where('company_id', $company_id)
                ->whereNull('deleted_at');
        });
        $user = $cusInfo->first();
        return response()->json(['data' => $user ? true : false]);
    }
    public function checkClientPhoneExisted($phone, Request $request)
    {
        $user = $this->model->getFirstWhere(['phone' => $phone, 'is_active' => '1']);

        return response()->json(['data' => $user ? true : false]);
    }

    public function getClientDistributor($city_code, $district_code, $ward_code, Request $request, $hub = null, $code = null)
    {

        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $group_id   = TM::getCurrentGroupId();
            $company_id = TM::getCurrentCompanyId();
            //
            //            $distributor = $this->model->findDistributor2(
            //                TM::getCurrentUserId(),
            //                $city_code,
            //                $district_code,
            //                $ward_code
            //            );
            //            return response()->json(['data' => $distributor]);
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($company_id)) {
            return response()->json(['data' => []]);
        }
        $distributors = Distributor::with([
            'users:code,id,phone,name,group_code',
            'users.profile:user_id,address,ward_code,district_code,city_code',
            'users.profile.ward:code,full_name',
            'users.profile.district:code,full_name',
            'users.profile.city:code,full_name',
        ])->select([
            'id',
            'code',
            'name',
            'value',
            'city_code',
            'district_code',
            'ward_code',
        ])->where('company_id', $company_id)
            ->where('is_active', 1)
            ->whereHas('users', function ($q) use ($code, $hub) {
                if ($hub == 1) {
                    $q->where('distributor_center_code', $code);
                }
                $q->whereNull('deleted_at');
                $q->where('is_active', 1);
            })
            ->groupBy(['code', 'name', 'city_code', 'district_code', 'ward_code'])
            ->distinct()
            ->get()->toArray();
        if (empty($distributors)) {
            if ($hub == 1) {
                return [];
            }
            return response()->json(['data' => []]);
        }
        if ($hub == 1 && !empty($distributors)) {
            return [];
        }
        // Find by Ward
        $key = array_search($ward_code, array_column($distributors, 'ward_code'));
        if (is_numeric($key) && $key === 0) {
            $key = (int)$key;
            if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
                $code = $distributors[$key]['code'];
                $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
                    $q->where('code', $code);
                })->first();
                if (!empty($checkDistributor)) {
                    $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, 1, $distributors[$key]['code']);
                    if (!empty($distributorsHub)) {
                        return $distributorsHub;
                    }
                }
            }
            $data = $this->getDataDistributorProfile($distributors[$key]);
            return response()->json(['data' => $data]);
        }
        // Find by District
        if (empty($key)) {
            $key = array_search($district_code, array_column($distributors, 'district_code'));
        }
        if (is_numeric($key) && $key === 0) {
            $key = (int)$key;
            if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
                $code = $distributors[$key]['code'];
                $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
                    $q->where('code', $code);
                })->first();
                if (!empty($checkDistributor)) {
                    $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, 1, $distributors[$key]['code']);
                    if (!empty($distributorsHub)) {
                        return $distributorsHub;
                    }
                }
            }
            $data = $this->getDataDistributorProfile($distributors[$key]);
            return response()->json(['data' => $data]);
        }
        // Find by City
        if (empty($key)) {
            $key = array_search($city_code, array_column($distributors, 'city_code'));
        }
        if (is_numeric($key) && $key === 0) {
            $key = (int)$key;
            if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
                $code = $distributors[$key]['code'];
                $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
                    $q->where('code', $code);
                })->first();
                if (!empty($checkDistributor)) {
                    $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, 1, $distributors[$key]['code']);
                    if (!empty($distributorsHub)) {
                        return $distributorsHub;
                    }
                }
            }
            $data = $this->getDataDistributorProfile($distributors[$key]);
            return response()->json(['data' => $data]);
        }

        if (empty($key) && $hub != 1) {
            $city = CityHasRegion::model()->where('code_city', $city_code)
                ->where('company_id', TM::getCurrentCompanyId())
                ->where('store_id', TM::getCurrentStoreId())->first();
            return response()->json(['data' => [
                "id"                 => $city->region->distributor_id ?? 0,
                "code"               => $city->region->distributor_code ?? '',
                "name"               => $city->region->distributor_name ?? '',
                "city_code"          => $city->region->city_code ?? '',
                "city_full_name"     => $city->region->city_full_name ?? '',
                "district_code"      => $city->region->district_code ?? '',
                "district_full_name" => $city->region->district_full_name ?? '',
                "ward_code"          => $city->region->ward_code ?? '',
                "ward_full_name"     => $city->region->ward_full_name ?? ''
            ]]);
            //            return response()->json(['data' => [
            //                "id"            => 0,
            //                "code"          => '',
            //                "name"          => '',
            //                "value"         => '',
            //                "city_code"     => '',
            //                "district_code" => '',
            //                "ward_code"     => ''
            //            ]]);
        }
        $key = (int)$key;
        if ($distributors[$key]['users']['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
            $code = $distributors[$key]['code'];
            $checkDistributor = Distributor::model()->whereHas('users', function ($q) use ($code) {
                $q->where('code', $code);
            })->first();
            if (!empty($checkDistributor)) {
                $distributorsHub = $this->getClientDistributor($city_code, $district_code, $ward_code, $request, 1, $distributors[$key]['code']);
                if (!empty($distributorsHub)) {
                    return $distributorsHub;
                }
            }
        }
        $data = $this->getDataDistributorProfile($distributors[$key]);
        return response()->json(['data' => $data]);
    }
    function getDataDistributorProfile($distributors)
    {
        return [
            "id"                 => $distributors['users']['id'] ?? "",
            "code"               => $distributors['users']['code'] ?? "",
            "name"               => $distributors['users']['name'] ?? "",
            "city_code"          => $distributors['users']['profile']['city']['code'] ?? "",
            "city_full_name"     => $distributors['users']['profile']['city']['full_name'] ?? "",
            "district_code"      => $distributors['users']['profile']['district']['code'] ?? "",
            "district_full_name" => $distributors['users']['profile']['district']['full_name'] ?? "",
            "ward_code"          => $distributors['users']['profile']['ward']['code'] ?? "",
            "ward_full_name"     => $distributors['users']['profile']['ward']['full_name'] ?? ""
        ];
    }
    public function getClientSeller($city_code, $district_code, $ward_code, Request $request)
    {
        $store_id   = null;
        $company_id = null;
        if (TM::getCurrentUserId()) {
            $store_id   = TM::getCurrentStoreId();
            $company_id = TM::getCurrentCompanyId();
        } else {
            $headers = $request->headers->all();
            if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
                $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
                if ($store_token_input && strlen($store_token_input) == 64) {
                    $store = Store::model()->select(['id', 'company_id'])->where('token', $store_token_input)->first();
                    if (!$store) {
                        return ['data' => []];
                    }
                    $store_id   = $store->id;
                    $company_id = $store->company_id;
                }
            }
        }

        if (empty($company_id)) {
            return response()->json(['data' => []]);
        }

        $sellers = RegisterArea::select([
            'id',
            'user_id as seller_id',
            'user_code as seller_code',
            'user_name as seller_name',
            'city_code',
            'city_name',
            'district_code',
            'district_name',
            'ward_code',
            'ward_name'
        ])
            ->where('company_id', $company_id)
            ->where('store_id', $store_id)
            ->get()->toArray();

        if (empty($sellers)) {
            return response()->json(['data' => []]);
        }

        // Find by Ward
        $key = array_search($ward_code, array_column($sellers, 'ward_code'));

        // Find by District
        if (empty($key)) {
            $key = array_search($district_code, array_column($sellers, 'district_code'));
        }

        // Find by City
        if (empty($key)) {
            $key = array_search($city_code, array_column($sellers, 'city_code'));
        }

        $key = (int)$key;

        return response()->json(['data' => $sellers[$key]]);
    }

    public function createClientUserPassword(
        Request                           $request,
        UserCreateClientPasswordValidator $userCreateClientPasswordValidator
    ) {
        $input = $request->all();
        $userCreateClientPasswordValidator->validate($input);
        $password = password_hash($input['password'], PASSWORD_BCRYPT);
        try {
            DB::beginTransaction();
            User::model()->where('id', $input['user_id'])->update(['password' => $password]);
            Log::update($this->model->getTable(), $input['user_id']);
            DB::commit();
            $user  = User::model()->where('id', "{$input['user_id']}")->first();
            $token = $this->jwt->fromUser($user);
            $time  = time();
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
        } catch (\Exception $ex) {
            DB::rollBack();
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response['message']);
        }
        return response()->json(['token' => $token]);
    }

    public function getOderStatusSummary(Request $request)
    {
        $userOrderNew       = $this->getCountUserOrderStatus(ORDER_STATUS_NEW);
        $userOrderApproved  = $this->getCountUserOrderStatus(ORDER_STATUS_APPROVED);
        $userOrderRejected  = $this->getCountUserOrderStatus(ORDER_STATUS_REJECTED);
        $userOrderReceived  = $this->getCountUserOrderStatus(ORDER_STATUS_RECEIVED);
        $userOrderCompleted = $this->getCountUserOrderStatus(ORDER_STATUS_COMPLETED);
        $userOrderCanceled  = $this->getCountUserOrderStatus(ORDER_STATUS_CANCELED);
        $userOrderShipped   = $this->getCountUserOrderStatus(ORDER_STATUS_SHIPPED);
        $userOrderShipping  = $this->getCountUserOrderStatus(ORDER_STATUS_SHIPPING);
        $data               = [
            'new'       => $userOrderNew ?? 0,
            'approved'  => $userOrderApproved ?? 0,
            'rejected'  => $userOrderRejected ?? 0,
            'received'  => $userOrderReceived ?? 0,
            'completed' => $userOrderCompleted ?? 0,
            'canceled'  => $userOrderCanceled ?? 0,
            'shipped'   => $userOrderShipped ?? 0,
            'shipping'  => $userOrderShipping ?? 0
        ];
        return response()->json(['data' => $data]);
    }

    public function getAllOderStatusSummary(Request $request)
    {
        $userOrderNew       = $this->getCountUserAllOrderStatus(ORDER_STATUS_NEW);
        $userOrderApproved  = $this->getCountUserAllOrderStatus(ORDER_STATUS_APPROVED);
        $userOrderRejected  = $this->getCountUserAllOrderStatus(ORDER_STATUS_REJECTED);
        $userOrderReceived  = $this->getCountUserAllOrderStatus(ORDER_STATUS_RECEIVED);
        $userOrderCompleted = $this->getCountUserAllOrderStatus(ORDER_STATUS_COMPLETED);
        $userOrderCanceled  = $this->getCountUserAllOrderStatus(ORDER_STATUS_CANCELED);
        $userOrderShipped   = $this->getCountUserAllOrderStatus(ORDER_STATUS_SHIPPED);
        $userOrderShipping  = $this->getCountUserAllOrderStatus(ORDER_STATUS_SHIPPING);
        $data               = [
            'new'       => $userOrderNew ?? 0,
            'approved'  => $userOrderApproved ?? 0,
            'rejected'  => $userOrderRejected ?? 0,
            'received'  => $userOrderReceived ?? 0,
            'completed' => $userOrderCompleted ?? 0,
            'canceled'  => $userOrderCanceled ?? 0,
            'shipped'   => $userOrderShipped ?? 0,
            'shipping'  => $userOrderShipping ?? 0
        ];
        return response()->json(['data' => $data]);
    }

    private function getCountUserOrderStatus($status)
    {
        $month      = Carbon::now()->month;
        $year       = Carbon::now()->year;
        $group_code = TM::getCurrentGroupCode();
        $count      = Order::model()
            ->where('status', $status)
            ->where('store_id', TM::getCurrentStoreId())
            ->whereMonth('updated_at', $month)
            ->whereYear('updated_at', $year);
        if ($group_code == USER_GROUP_DISTRIBUTOR) {
            $count->where('distributor_id', TM::getCurrentUserId());
        } else {
            $count->where('customer_id', TM::getCurrentUserId());
        }
        $count = $count->count('id');
        return $count;
    }

    private function getCountUserAllOrderStatus($status)
    {
        $group_code = TM::getCurrentGroupCode();
        $count      = Order::model()
            ->where('status', $status)
            ->where('store_id', TM::getCurrentStoreId());
        if ($group_code == USER_GROUP_DISTRIBUTOR) {
            $count->where('distributor_id', TM::getCurrentUserId());
        } else {
            $count->where('customer_id', TM::getCurrentUserId());
        }
        $count = $count->count('id');
        return $count;
    }

    public function printAgentRegistrationForm($id)
    {
        $user = User::with('profile')->where('id', $id)->first();

        if (!$user) {
            $this->response->errorBadRequest(Message::get("V003", "ID #$id"));
        }
        $userGroup = Arr::get($user, 'group_code', null);

        //        if ($userGroup != USER_GROUP_AGENT) {
        //            $this->response->errorBadRequest("Tài khoản [ID: #$id - Group: $userGroup] không được phép .");
        //        }
        if ($user->agent_register != 1) {
            $this->response->errorBadRequest("Tài khoản không được phép .");
        }
        $pdf = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetPrintHeader(false);
        $pdf->SetPrintFooter(false);
        // set font
        $pdf->SetFont('dejavusans', '', 10);
        // add a page
        $pdf->AddPage();
        $gender       = [
            'F' => 'Nữ',
            'M' => 'Nam',
            'O' => 'Khác'
        ];
        $birthday     = Arr::get($user, 'profile.birthday', null);
        $id_number_at = Arr::get($user, 'profile.id_number_at', null);
        $data         = [
            'reference_name'      => Arr::get($user, 'reference_name', null),
            'reference_phone'     => Arr::get($user, 'reference_phone', null),
            'name'                => Arr::get($user, 'name', null),
            'gender'              => $gender[Arr::get($user, 'profile.gender', "O")],
            'birthday'            => $birthday ? date('d-m-Y', strtotime($birthday)) : null,
            'id_number'           => Arr::get($user, 'profile.id_number', null),
            'id_number_at'        => $id_number_at ? date('d-m-Y', strtotime($id_number_at)) : null,
            'id_number_place'     => Arr::get($user, 'profile.id_number_place', null),
            'address'             => Arr::get($user, 'profile.address', null),
            'ward'                => Arr::get($user, 'profile.ward.name', null),
            'district'            => Arr::get($user, 'profile.district.name', null),
            'city'                => Arr::get($user, 'profile.district.name', null),
            'phone'               => Arr::get($user, 'phone', null),
            'email'               => Arr::get($user, 'email', null),
            'bank_account_number' => Arr::get($user, 'bank_account_number', null),
            'bank_account_name'   => Arr::get($user, 'bank_account_name', null),
            'bank_branch'         => Arr::get($user, 'bank_branch', null),
            'bank_name'           => Arr::get($user, 'bank_branch', null),
            'bank_id'             => Arr::get($user, 'bank_id', null),
            'tax'                 => Arr::get($user, 'tax', null),
            'created_at'          => Arr::get($user, 'created_at', null),
        ];
        $html         = view("user-register.print_agent_registration_form", compact("data"));
        $pdf->writeHTML($html);
        $name = "User-$id-AgentRegistration.pdf";
        if (!file_exists(storage_path() . "/AgentRegistrationForm")) {
            mkdir(storage_path() . "/AgentRegistrationForm", 0755, true);
        }
        $filePdf = storage_path() . "/AgentRegistrationForm/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=$name");
        header('Access-Control-Allow-Origin: *');
        readfile($filePdf);

        return Message::get('print-success', $name);
    }

    public function syncUser()
    {
        $user = User::model()->select('id', 'code', 'name', 'store_id', 'company_id', 'role_id')->where([
            'store_id'   => 46,
            'company_id' => 24
        ])->get()->toArray();

        $arrayUserStore = UserStore::model()->select('user_id')->where('store_id', 46)->get();
        $arrayUserStore = array_pluck($arrayUserStore, 'user_id');
        //User stores
        try {
            DB::beginTransaction();
            $queryHeader  = "INSERT INTO `user_stores` (" .
                "`user_id`, " .
                "`role_id`, " .
                "`company_id`, " .
                "`store_id`, " .
                "`user_code`, " .
                "`user_name`, " .
                "`store_code`, " .
                "`store_name`, " .
                "`company_code`, " .
                "`company_name`, " .
                "`role_code`, " .
                "`role_name`, " .
                "`created_at`, " .
                "`created_by`, " .
                "`updated_at`, " .
                "`updated_by`) VALUES ";
            $now          = date('Y-m-d H:i:s', time());
            $me           = TM::getCurrentUserId();
            $queryContent = "";
            /** @var \PDO $pdo */
            $pdo = DB::getPdo();
            foreach ($user as $item) {
                if (!empty(in_array($item['id'], $arrayUserStore))) {
                    continue;
                }
                $role    = Role::model()->select(['id', 'code', 'name'])->where('id', $item['role_id'])->first()->toArray();
                $store   = Store::model()->select(['id', 'code', 'name'])->where('id', $item['store_id'])->first()->toArray();
                $company = Company::model()->select(['id', 'code', 'name'])->where('id', $item['company_id'])->first()->toArray();
                $queryContent
                    .= "(" .
                    $pdo->quote($item['id']) . "," .
                    $pdo->quote($item['role_id']) . "," .
                    $pdo->quote($item['company_id']) . "," .
                    $pdo->quote($item['store_id']) . "," .
                    $pdo->quote($item['code']) . "," .
                    $pdo->quote($item['name']) . "," .
                    $pdo->quote($store['code']) . "," .
                    $pdo->quote($store['name']) . "," .
                    $pdo->quote($company['code']) . "," .
                    $pdo->quote($company['name']) . "," .
                    $pdo->quote($role['code']) . "," .
                    $pdo->quote($role['name']) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
            }
            if (!empty($queryContent)) {
                $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`user_id`= values(`user_id`), " .
                    "`store_id` = values(`store_id`), " .
                    "`company_id` = values(`company_id`), " .
                    "updated_at='$now', updated_by=$me";
                DB::statement($queryUpdate);
            }
            // Commit transaction
            DB::commit();
        } catch (\Exception $e) {
            return $e;
        }
        return ['status' => 'Sync User Success'];
    }

    public function personalAgentCertification()
    {
        $user = User::find(TM::getCurrentUserId());
        $pdf  = new TM_PDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetMargins(PDF_MARGIN_LEFT, 20, -1, true);
        $pdf->SetHeaderData($ln = '', $lw = 0, $ht = '<br/>', $hs = '
            <table style="display: block">
                <tr style="font-size: 8px;color: #4e555b">
                    <td width="200px">
                        <p><img src="' . (URL::to('logo-header/logoheaderIDP.png')) . '"></p>
                    </td>
                    <td width="20px">&nbsp;</td>
                    <td width="10px">&nbsp;</td>
                    <td width="280px">
                       <p>Địa chỉ: Km29, Quốc lộ 6, Xã Trường Yên, Huyện Chương Mỹ, Hà Nội<br/>
                        VPĐD: 217 Nguyễn Văn Thủ, Đa Kao, Quận 1, TP.HCM<br/>
                        ĐT: (+84) 2862544455 &nbsp;&nbsp;&nbsp;&nbsp; Fax: (+)84 2862544466
                        </p>
                    </td>
                </tr>
            </table>
            ');
        $pdf->SetPrintFooter(false);
        $pdf->SetFont('dejavusans', '', 10);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->AddPage();
        $data = [
            'name'       => Arr::get($user, 'name', null),
            'code'       => Arr::get($user, 'code', null),
            'updated_at' => Arr::get($user, 'updated_at', null),
        ];
        // Add Line Hr
        $pdf->Line(PDF_MARGIN_LEFT, $pdf->getY(), $pdf->getPageWidth() - PDF_MARGIN_LEFT, $pdf->getY());
        $pdf->Ln();
        // Ẹnd Hr
        $html = view("user-register.print_personal_agent_certification", compact("data"));
        $pdf->writeHTML($html, true, false, true, false, '');
        $name = "Personal_Agent_Certification.pdf";
        if (!file_exists(storage_path() . "/AgentRegistrationForm")) {
            mkdir(storage_path() . "/AgentRegistrationForm", 0755, true);
        }
        $filePdf = storage_path() . "/AgentRegistrationForm/$name";
        $pdf->Output($filePdf, 'F');

        header("Content-type:application/pdf");
        header("Content-Disposition:attachment;filename=$name");
        header('Access-Control-Allow-Origin: *');
        readfile($filePdf);

        return Message::get('print-success', $name);
    }

    public function delete($id)
    {
        try {
            $result = User::findOrFail($id);

            if ($result->deleted_at != null && $result->deleted == 1 && $result->is_active == 0) {
                return $this->responseError(Message::get('V061'));
            }
            $result->is_active  = 0;
            $result->deleted    = 1;
            $result->deleted_at = date('Y-m-d H:i:s', time());
            $result->deleted_by = TM::getCurrentUserId();
            $result->save();
        } catch (\Exception $exception) {
            $response = TM_Error::handle($exception);
            return $this->responseError($response['message']);
        }
        return response()->json(['status' => Message::get('R003', $result->name)]);
    }

    public function clientGetListHub(Request $request)
    {
        $input = $request->all();

        list($store_id, $area_ids, $group_id, $company_id) = DataUser::getInstance()->all();
        $user = User::searchHub($request)
            ->with([
                'profile',
                'profile.city',
                'profile.district',
                'profile.ward',
                'group',
            ])
            ->where('store_id', $store_id)->whereNull('deleted_at')
            ->where('is_active', 1)
            ->where(function ($q) use ($input) {
                $q->whereHas('group', function ($b) use ($input) {
                    if (!empty($input['group_code'])) {
                        $b->where('group_code', $input['group_code']);
                    } else {
                        $b->where('group_code', USER_GROUP_DISTRIBUTOR);
                    }
                });
                $q->WhereHas("profile", function ($q) use ($input) {
                    if (!empty($input['ward_code'])) {
                        $q->where('ward_code', $input['ward_code']);
                    }
                    if (!empty($input['district_code'])) {
                        $q->where('district_code', $input['district_code']);
                    }
                    if (!empty($input['city_code'])) {
                        $q->where('city_code', $input['city_code']);
                    }
                });
            })
            ->orderBy('group_code', 'asc')
            ->paginate($request->get('limit', 20));
        // dd($user);die;

        return $this->response->paginator($user, new UserGetListTransformer());
    }

    ########################Export Customer Excel$##############################
    public function customerExportExcel()
    {
        //ob_end_clean();
        $date = date('YmdHis', time());
        $user = User::With('role')->where('type', USER_TYPE_CUSTOMER)->where('company_id', TM::getCurrentCompanyId())->get();
        //ob_start(); // and this
        return Excel::download(new ExportCustomersUser($user), 'list_customers_user' . $date . '.xlsx');
    }

    private function sendSMSCode($message, $phone)
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

    public function socialSync(Request $request)
    {
        $input = $request->all();
        $user  = User::find(TM::getCurrentUserId());

        $user->fb_id = Arr::get($input, 'fb_id', $user->fb_id);
        $user->gg_id = Arr::get($input, 'gg_id', $user->gg_id);
        $user->zl_id = Arr::get($input, 'zl_id', $user->zl_id);
        $user->save();

        return ['message' => Message::get('V001', "[$user->name]")];
    }
    public function exportUserOrderBySeller(Request $request)
    {
        $input = $request->all();
        $date   = date('YmdHis', time());
        $users = User::model()->with('sellerOrder')->where(['company_id' => TM::getCurrentCompanyId()])
            ->whereHas('role', function ($q) {
                $q->where('code', USER_ROLE_SELLER);
            })
            ->get();
        $i = 1;
        foreach ($users as $user) {
            $order = Order::model()
                ->select([
                    DB::raw('COUNT(orders.id) as total_order'),
                    'crm_check'
                ])
                ->where('seller_id', $user->id);
            if (!empty($input['from']) && !empty($input['to'])) {
                $order = $order->whereDate('created_at', '>=', $input['from'])->whereDate('created_at', '<=', $input['to']);
            }
            $order = $order->groupBy('crm_check')->get()->toArray();
            $totalCompleteCaller = 0;
            $totalCancelCaller = 0;
            $totalCaller1 = 0;
            $totalCaller2 = 0;
            $totalCaller3 = 0;
            $totalCallerPending = 0;
            $total = array_sum(array_column($order, 'total_order'));
            $keyCompleteCaller = array_search(ORDER_STATUS_SELLER_COMPLETE, array_column($order, 'crm_check'));
            if (is_numeric($keyCompleteCaller)) {
                $totalCompleteCaller = $order[$keyCompleteCaller]['total_order'];
            }
            $keyCancelCaller = array_search(ORDER_STATUS_SELLER_CANCELED, array_column($order, 'crm_check'));
            if (is_numeric($keyCancelCaller)) {
                $totalCancelCaller = $order[$keyCancelCaller]['total_order'];
            }
            $keyCaller1 = array_search(ORDER_STATUS_SELLER_CALLER1, array_column($order, 'crm_check'));
            if (is_numeric($keyCaller1)) {
                $totalCaller1 = $order[$keyCaller1]['total_order'];
            }
            $keyCaller2 = array_search(ORDER_STATUS_SELLER_CALLER2, array_column($order, 'crm_check'));
            if (is_numeric($keyCaller2)) {
                $totalCaller2 = $order[$keyCaller2]['total_order'];
            }
            $keyCaller3 = array_search(ORDER_STATUS_SELLER_CALLER3, array_column($order, 'crm_check'));
            if (is_numeric($keyCaller3)) {
                $totalCaller3 = $order[$keyCaller3]['total_order'];
            }
            $totalPending = array_search(null, array_column($order, 'crm_check'));
            if (is_numeric($totalPending)) {
                $totalCallerPending = $order[$totalPending]['total_order'];
            }
            $data[] = [
                'stt'         => $i,
                'name_caller' => $user->name,
                'name_leader' => $user->parentLeader->name ?? null,
                'total'       => $total,
                'total_pending' => $totalCallerPending,
                'total_complete' => $totalCompleteCaller,
                'total_cancel'   => $totalCancelCaller,
                'total_caller1'   => $totalCaller1,
                'total_caller2'   => $totalCaller2,
                'total_caller3'   => $totalCaller3,
            ];
            $i++;
        }
        if (empty($input['from'])) {
            $input['from'] = '';
        }
        if (empty($input['to'])) {
            $input['to'] = '';
        }
        return Excel::download(new ExportOrdersByCaller($data ?? [], $input['from'], $input['to']), 'list_orders_' . $date . '.xlsx');
    }

    function clean($string) {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string); // Removes special chars.
     }


    public function exportUserOrder(Request $request)
    {
        $input = $request->all();
        $date = date('YmdHis', time());

        $data = [];
        try {
            $users = UserRP::model()->with('profile')
                ->select('id', 'code', 'name', 'phone', 'email')
                ->where('type', USER_TYPE_CUSTOMER)
                ->where('company_id', TM::getCurrentCompanyId())
                ->where('group_code', USER_GROUP_GUEST)
                ->offset($input['offset'] ?? 0)
                ->limit($input['limit'] ?? 99999)
                ->get();

            $i = 1;
            foreach ($users as $user) {

                if (empty($input['from']) && empty($input['to'])) {

                    $sql       = "SELECT DISTINCT o.code,
                SUM(o.total_price)  AS total_price ,
                 SUM(o.saving) AS saving,
                 count(*) as total_completed,
                (SELECT COUNT(*) FROM orders WHERE STATUS != 'INPROGRESS' 
                AND store_id = " . TM::getCurrentStoreId() . "
                AND customer_id = " . $user->id . "
                AND deleted_at IS null
                ) AS total_order, SUM(od.qty) AS qty_order
                FROM orders  AS o
                JOIN order_details AS od
                ON o.id = od.order_id
                WHERE o.STATUS = 'COMPLETED'
                AND o.store_id = " . TM::getCurrentStoreId() . "
                AND o.customer_id = " . $user->id . "
                AND o.deleted_at IS NULL
                AND od.deleted_at IS NULL
                ";

                    $dataOrder = DB::connection('mysql2')->select($sql);
                } else {

                    if (empty($input['from'])) {
                        $input['from'] = '2019-01-01';
                    }

                    if (empty($input['to'])) {
                        $input['to'] = date('Y-m-d');
                    }

                    $from      = date('Y-m-d', strtotime($input['from']));
                    $to        = date('Y-m-d', strtotime($input['to']));

                    $sql       = "SELECT DISTINCT o.code,
                SUM(o.total_price)  AS total_price , SUM(o.saving) AS saving, count(*) as total_completed,
                (SELECT COUNT(*) FROM orders WHERE STATUS != 'INPROGRESS' 
                AND store_id = " . TM::getCurrentStoreId() . "
                AND customer_id = " . $user->id . "
                AND deleted_at IS null
                AND created_at BETWEEN '" . $from . "' AND '" . $to . "'
                ) AS total_order, SUM(od.qty) AS qty_order
                FROM orders  AS o
                JOIN order_details AS od
                ON o.id = od.order_id
                WHERE o.STATUS = 'COMPLETED'
                AND o.store_id = " . TM::getCurrentStoreId() . "
                AND o.customer_id = " . $user->id . "
                AND o.deleted_at IS NULL
                AND od.deleted_at IS NULL
                AND o.created_at BETWEEN '" . $from . "' AND '" . $to . "'
                ";

                    $dataOrder = DB::connection('mysql2')->select($sql);
                }

                $orderAverage           = $dataOrder[0]->total_completed != 0 ? $dataOrder[0]->total_price / $dataOrder[0]->total_completed : 0;
                $qtyAverage             = $dataOrder[0]->total_order != 0 ? $dataOrder[0]->qty_order / $dataOrder[0]->total_order : 0;
                // $sumTotal = Order::model()->where([
                //     'status'   => ORDER_STATUS_COMPLETED,
                //     'store_id' => TM::getCurrentStoreId(),
                //     'customer_id' => $user->id
                // ]);

                // if (!empty($sumTotal)) {
                //     if (!empty($input['from']) && !empty($input['to'])) {
                //         $sumTotal = $sumTotal->whereDate('created_at', '>=', $input['from'])->whereDate('created_at', '<=', $input['to']);
                //     }

                //     // count đơn luôn
                //     $sumTotal = $sumTotal->sum('total_price');
                //     $sumSavingTotal = Order::model()->where([
                //         'status'   => ORDER_STATUS_COMPLETED,
                //         'store_id' => TM::getCurrentStoreId(),
                //         'customer_id' => $user->id
                //     ]);
                //     if (!empty($input['from']) && !empty($input['to'])) {
                //         $sumSavingTotal = $sumSavingTotal->whereDate('created_at', '>=', $input['from'])->whereDate('created_at', '<=', $input['to']);
                //     }
                //     $sumSavingTotal = $sumSavingTotal->sum('saving');
                //     $orderStatistic = Order::model()->select([
                //         DB::raw('COUNT(orders.id) as total_order'),
                //         'orders.status',
                //         'os.name as status_text',
                //     ])->join('order_status as os', 'os.code', '=', 'orders.status')
                //         ->where('orders.status', '!=', ORDER_STATUS_IN_PROGRESS)
                //         ->where('orders.store_id', TM::getCurrentStoreId())
                //         ->where('orders.customer_id', $user->id)
                //         ->where('os.company_id', TM::getCurrentCompanyId());
                //     if (!empty($input['from']) && !empty($input['to'])) {
                //         $orderStatistic = $orderStatistic->whereDate('orders.created_at', '>=', $input['from'])->whereDate('orders.created_at', '<=', $input['to']);
                //     }
                //     $orderStatistic         = $orderStatistic->groupBy('orders.status')->get()->toArray();
                //     $key                    = array_search(ORDER_STATUS_COMPLETED, array_column($orderStatistic, 'status'));
                //     $totalCompleteOrder     = is_numeric($key) ? $orderStatistic[$key]['total_order'] : 0;
                //     $totalOrder             = array_sum(array_column($orderStatistic, 'total_order'));
                //     $orderAverage           = $totalCompleteOrder != 0 ? $sumTotal / $totalCompleteOrder : 0;
                //     $orders = Order::model()->with('details')->where([
                //         'store_id' => TM::getCurrentStoreId(),
                //         'customer_id' => $user->id
                //     ]);
                //     if (!empty($input['from']) && !empty($input['to'])) {
                //         $orders = $orders->whereDate('created_at', '>=', $input['from'])->whereDate('created_at', '<=', $input['to']);
                //     }
                //     $orders = $orders->get();
                // }
                // $qtyOrder = 0;
                // if (!empty($orders)) {
                //     foreach ($orders as $order) {
                //         foreach ($order->details as $detail) {
                //             $qtyOrder += $detail->qty;
                //         }
                //     }
                //     $totalDetailsOrder = $totalOrder != 0 ? $qtyOrder / $totalOrder : 0;
                // }
                $data[] = [
                    'code'              => $user->code,
                    'name'              => clean($user->name),
                    'phone'             => $user->phone,
                    'email'             => $user->email ?? null,
                    'ward'              => $user->profile->ward->full_name ?? null,
                    'district'          => $user->profile->district->full_name ?? null,
                    'city'              => $user->profile->city->full_name ?? null,
                    // 'qty_order'         => $dataOrder[0]->qty_order ?? 0,
                    'saving'            => $dataOrder[0]->saving ?? 0,
                    'qtyOrderAverage'   => $qtyAverage,
                    'total_order'       => $dataOrder[0]->total_order ?? 0,
                    // 'sum_total'         => $dataOrder[0]->total_price ?? 0,
                    'total_completed'   => $dataOrder[0]->total_completed ?? 0,
                    'stt'               => $i,
                    'orderAverage'      => $orderAverage
                ];
                $i++;
            }
            if (empty($input['from'])) {
                $input['from'] = '';
            }
            if (empty($input['to'])) {
                $input['to'] = '';
            }
            return Excel::download(new ExportOrdersByUser($data ?? [], $input['from'], $input['to']), 'list_customers_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        }
    }

    public function exportUserGroup(Request $request)
    {
        $input = $request->all();
        $date = date('YmdHis', time());
        $data = [];
        try {
            $users = User::model()
                ->where(function ($q) use ($input) {
                    if (!empty($input['group_code'])) {
                        $q->where('group_code', $input['group_code']);
                    }
                    if (!empty($input['is_active'])) {
                        $q->where('is_active', $input['is_active']);
                    }
                    if (!empty($input['store_id'])) {
                        $q->where('store_id', $input['store_id']);
                    }
                    if (!empty($input['is_logged'])) {
                        $q->where('is_logged', $input['is_logged']);
                    }
                    if (!empty($input['code'])) {
                        $q->where('code', $input['code']);
                    }
                    if (!empty($input['full_name'])) {
                        $q->where('full_name', $input['full_name']);
                    }
                    if (!empty($input['email'])) {
                        $q->where('email', $input['email']);
                    }
                    if (!empty($input['phone'])) {
                        $q->where('phone', $input['phone']);
                    }
                    if (!empty($input['membership_rank'])) {
                        $q->where('membership_rank', $input['membership_rank']);
                    }
                    if (!empty($input['type'])) {
                        $q->where('type', $input['type']);
                    }
                })
                ->get();
            $i = 1;
            foreach ($users as $user) {
                $data[] = [
                    'stt' => $i,
                    'date' => $date,
                    'code' => $user->code,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->emai ?? null,
                    'ward' => $user->profile->ward->full_name ?? null,
                    'district' => $user->profile->district->full_name ?? null,
                    'city' => $user->profile->city->full_name ?? null,
                    'type' => $user->type
                ];
                $i++;
            }

            return Excel::download(new ExportUsersGroup($data ?? []), 'list_users_group_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        };
    }
    public function exportUser(Request $request)
    {
        $input = $request->all();
        $date = date('YmdHis', time());
        $data = [];
        try {
            $users = User::model()
                ->where(function ($q) use ($input) {

                    if (!empty($input['store_id'])) {
                        $q->where('store_id', $input['store_id']);
                    }
                    if (!empty($input['code'])) {
                        $q->where('code', $input['code']);
                    }
                    if (!empty($input['full_name'])) {
                        $q->where('full_name', $input['full_name']);
                    }
                    if (!empty($input['is_active'])) {
                        $q->where('is_active', $input['is_active']);
                    }
                    if (!empty($input['role_code'])) {
                        $q->where('role_code', $input['role_code']);
                    }
                    if (!empty($input['email'])) {
                        $q->where('email', $input['email']);
                    }
                    if (!empty($input['phone'])) {
                        $q->where('phone', $input['phone']);
                    }
                    if (!empty($input['type'])) {
                        $q->where('type', $input['type']);
                    }
                })
                ->get();
            $i = 1;
            foreach ($users as $user) {
                $data[] = [
                    'stt' => $i,
                    'date' => $date,
                    'code' => $user->code,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email ?? null,
                    'type' => $user->type
                ];
                $i++;
            }

            return Excel::download(new ExportUsers($data ?? []), 'list_users_' . $date . '.xlsx');
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            return $this->response->errorBadRequest($response["message"]);
        };
    }

    public function exportUserByLocation(Request $request)
    {
        $input = $request->all();
        $date = date('YmdHis', time());
        //        if($input['is_city']){
        $areas = City::model()->get();
        //        }
        if (!empty($input['is_location'] && $input['is_location'] == 1)) {
            $areas = District::model()->get();
        }
        $data = [
            'is_district' => !empty($input['is_location'] && $input['is_location'] == 1) ? 1 : 0
        ];
        foreach ($areas as $area) {
            $order = Order::model()->select(DB::raw('SUM(orders.total_price) as totalPrice'), 'orders.customer_id', 'users.phone', 'users.code', 'users.email', 'users.name')
                ->join('users', 'users.id', 'orders.customer_id')
                ->join('profiles as p', 'p.user_id', 'users.id')
                ->where('status', ORDER_STATUS_COMPLETED)
                ->where(function ($q) use ($area, $input) {
                    if (!empty($input['is_city'])) {
                        $q->where('orders.shipping_address_city_code', $area->code);
                    }
                    if (!empty($input['is_district'])) {
                        $q->where('orders.shipping_address_district_code', $area->code);
                    }
                    if (!empty($input['from']) && !empty($input['to'])) {
                        $q->whereDate('orders.created_at', '>=', $input['from'])->whereDate('orders.created_at', '<=', $input['to']);
                    }
                });
            $order = $order->groupBy('orders.customer_id')->orderBy('totalPrice', 'desc')->limit(10)->get();
            $data['data'][] = [
                'city_code' => !empty($input['is_location'] && $input['is_location'] == 1) ? $area->city->code : $area->code,
                'city_name' => !empty($input['is_location'] && $input['is_location'] == 1) ? $area->city->full_name : $area->full_name,
                'district_code' => !empty($input['is_location'] && $input['is_location'] == 1) ? $area->code : null,
                'district_name' => !empty($input['is_location'] && $input['is_location'] == 1) ? $area->full_name : null,
                'details' => $order
            ];
        }
        if (empty($input['from'])) {
            $input['from'] = '';
        }
        if (empty($input['to'])) {
            $input['to'] = '';
        }
        return Excel::download(new ExportOrdersByLocation($data ?? [], $input['from'], $input['to']), 'list_user_top_' . $date . '.xlsx');
    }
    private function getSMSVerify($phone)
    {
        return DB::table('sms_verify')
            ->where('phone', $phone)
            ->first();
    }
}

/**
 * select `p`.`lat`, `p`.`long`, `p`.`full_name`, `users`.`phone`, `users`.`id`, `p`.`ready_work` from `users` inner join `profiles` as `p` on `p`.`user_id` = `users`.`id` inner join `user_sessions` as `us` on `us`.`user_id` = `users`.`id` inner join `orders` as `o` on `o`.`partner_id` = `us`.`user_id` where `users`.`deleted_at` is null and `o`.`status` != 'COMPLETED' and `p`.`ready_work` = 1 and `us`.`socket_id` is not null and `us`.`socket_id` != '' and `us`.`deleted` = 0 group by `us`.`user_id`
 */
