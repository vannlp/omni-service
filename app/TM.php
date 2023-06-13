<?php

namespace App;

use App\Supports\Message;
use App\V1\Models\PermissionModel;
use App\V1\Models\RolePermissionModel;
use Dingo\Api\Http\Response;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Exceptions\TokenBlacklistedException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Monolog\Logger;
use Monolog\Handler\TelegramBotHandler;

class TM
{
    private static $_data;

    public static final function info()
    {
        self::__getUserInfo();

        return self::$_data;
    }

    public static final function getCurrentCompanyName()
    {
        $storeID = self::getHeaderStoreID();
        if (empty($storeID)) {
            $userInfo = self::info();
            return $userInfo['company_name'] ?? null;
        }
        $store = Store::where('id', $storeID)->value('company_id');
        $company = Company::find($store);
        return $company->name ?? null;
    }

    public static final function getCurrentCompanyCode()
    {
        $storeID = self::getHeaderStoreID();
        if (empty($storeID)) {
            $userInfo = self::info();
            return $userInfo['company_code'] ?? null;
        }
        $store = Store::where('id', $storeID)->value('company_id');
        $company = Company::find($store);
        return $company->code ?? null;
    }

    public static final function __getUserInfo()
    {

        $token = JWTAuth::getToken();

        if (!$token) {
            return response()->json([
                'message'     => 'A token is required',
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (TokenExpiredException $ex) {
            return response()->json([
                'message'     => $ex->getMessage(),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        } catch (TokenBlacklistedException $blacklistedException) {
            return response()->json([
                'message'     => $blacklistedException,
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], Response::HTTP_UNAUTHORIZED);
        }
        $permissionModel = new PermissionModel();
        $rolePermissionModel = new RolePermissionModel();
        $roles = DB::table('role_permissions')
            ->join($permissionModel->getTable(), $permissionModel->getTable() . '.id', '=',
                $rolePermissionModel->getTable() . '.permission_id')
            ->select([
                $permissionModel->getTable() . '.name as permission_name',
                $permissionModel->getTable() . '.code as permission_code',
            ])
            ->where($rolePermissionModel->getTable() . '.role_id', $user->role_id)
            ->whereNull($permissionModel->getTable() . '.deleted_at')
            ->whereNull($rolePermissionModel->getTable() . '.deleted_at')
            ->get()->toArray();
        $permissions = array_pluck($roles, "permission_name", 'permission_code');
        self::$_data = [
            'id'                      => $user->id,
            'email'                   => $user->email,
            'phone'                   => $user->phone,
            'user'                    => $user->user,
            'name'                    => $user->name,
            'code'                    => $user->code,
            'type'                    => $user->type,
            'first_name'              => object_get($user, 'profile.first_name', null),
            'parent_role'              => object_get($user, 'parentLeader.role.code', null),
            'parent_code'               => object_get($user, 'parentLeader.code', null),
            'last_name'               => object_get($user, 'profile.last_name', null),
            'full_name'               => object_get($user, 'profile.full_name', null),
            'partner_type'            => object_get($user, 'partner_type', null),
            'is_super'                => $user->is_super,
            'role_code'               => object_get($user, 'role.code', null),
            'role_name'               => object_get($user, 'role.name', null),
            'role_level'               => object_get($user, 'role.role_level', null),
            'role_group_id'           => object_get($user, 'role.roleGroup.id', null),
            'role_group_code'         => object_get($user, 'role.roleGroup.code', null),
            'role_group_name'         => object_get($user, 'role.roleGroup.name', null),
            'permissions'             => $permissions,
            'ranking_id'              => $user->ranking_id,
            'point'                   => $user->point,
            'company_id'              => $user->company_id,
            'company_name'            => object_get($user, 'company.name', null),
            'company_code'            => object_get($user, 'company.code', null),
            'distributor_center_id'   => object_get($user, 'distributor_center_id', null),
            'distributor_center_code' => object_get($user, 'distributor_center_code', null),
            'distributor_center_name' => object_get($user, 'distributor_center_name', null),
            'store_id'                => $user->store_id,
            'city_code'               => object_get($user, 'shipping_address.city_code', null),
            'district_code'           => object_get($user, 'shipping_address.district_code', null),
            'ward_code'               => object_get($user, 'shipping_address.ward_code', null),
            'group_id'                => $user->group_id,
            'group_code'              => $user->group_code,
        ];
    }

    //------------------static function-------------------------------

    public static final function isSuperUser()
    {
        $userInfo = self::info();

        return ($userInfo['is_super'] ?? null) == 1 ? true : false;
    }

    public static final function getCurrentPermission()
    {
        $userInfo = self::info();
        return $userInfo['permissions'] ?? null;
    }

    public static final function getUpdatedBy()
    {
        $userInfo = self::info();

        return "USER: #" . ($userInfo['id'] ?? null);
    }

    public static final function isAdminUser()
    {
        $userInfo = self::info();

        return !empty($userInfo['role_code']) && $userInfo['role_code'] == "ADMIN" ? true : false;
    }

    public static final function getCurrentUserId()
    {
        $userInfo = self::info();

        return $userInfo['id'] ?? null;
    }

    public static final function getCurrentUserCode()
    {
        $userInfo = self::info();

        return $userInfo['code'] ?? null;
    }

    public static final function getCurrentCityCode()
    {
        $userInfo = self::info();

        return $userInfo['city_code'] ?? null;
    }


    public static final function getCurrentDistrictCode()
    {
        $userInfo = self::info();

        return $userInfo['district_code'] ?? null;
    }

    public static final function getCurrentWardCode()
    {
        $userInfo = self::info();

        return $userInfo['ward_code'] ?? null;
    }


    public static final function getCurrentUserName()
    {
        $userInfo = self::info();

        return $userInfo['full_name'] ?? null;
    }

    public static final function getCurrentUserType()
    {
        $userInfo = self::info();

        return $userInfo['type'] ?? null;
    }


    public static final function getCurrentCompanyId()
    {
        $storeID = self::getHeaderStoreID();
        if (empty($storeID)) {
            $userInfo = self::info();
            return $userInfo['company_id'] ?? null;
        }
        return Store::where('id', $storeID)->value('company_id');
    }

    public static final function getCurrentStoreId()
    {
        $storeID = self::getHeaderStoreID();
        if (empty($storeID)) {
            $userInfo = self::info();
            return $userInfo['store_id'] ?? null;
        }

        return $storeID;
    }

    public static final function getCurrentGroupId()
    {
        $userInfo = self::info();

        return $userInfo['group_id'] ?? null;
    }

    public static final function getCurrentGroupCode()
    {
        $userInfo = self::info();

        return $userInfo['group_code'] ?? null;
    }

    public static final function getMyUserType()
    {
        $userInfo = self::info();

        return $userInfo['type'] ?? null;
    }

    public static final function getMyPartnerType()
    {
        $userInfo = self::info();

        return $userInfo['partner_type'] ?? null;
    }

    public static final function getCurrentRole()
    {
        $userInfo = self::info();

        return $userInfo['role_code'] ?? null;
    }

    public static final function getCurrentRoleGroup()
    {
        $userInfo = self::info();

        return $userInfo['role_group_code'] ?? null;
    }

    public static final function urlBase($url = null)
    {
        $base = env("APP_URL");
        if (!empty($_SERVER['HTTP_REFERER'])) {
            $base = $_SERVER['HTTP_REFERER'];
        } elseif (!empty($_SERVER['HTTP_ORIGIN'])) {
            $base = $_SERVER['HTTP_ORIGIN'];
        }
        $base = $base . $url;
        $base = str_replace(" ", "", $base);
        $base = str_replace("\\", "/", $base);
        $base = str_replace("//", "/", $base);
        $base = str_replace(":/", "://", $base);

        return $base;
    }

    public static final function allowRemote()
    {
        $allowRemote = env("APP_ALLOW_REMOTE", 0);
        if ($allowRemote === 1 || $allowRemote === "1") {
            return true;
        }

        if (empty($_SERVER["REMOTE_ADDR"])) {
            return false;
        }
        if (empty($_SERVER['HTTP_ORIGIN'])) {
            return false;
        }

        $clientDomain = trim($_SERVER["HTTP_ORIGIN"]);
        $clientDomain = str_replace(" ", "", $clientDomain);
        $clientDomain = strtolower($clientDomain);
        $clientIp = $_SERVER["SERVER_ADDR"];

        $remote = DB::table('settings')->select(['id', 'name'])->where('code', 'REMOTE')->first();
        if (!empty($remote)) {
            return false;
        }

        $remote = json_decode($remote, true);
        if (!empty($remote) || !is_array($remote)) {
            return false;
        }

        foreach ($remote as $item) {
            if ($item["ip"] == $clientIp && $item["domain"] == $clientDomain) {
                return true;
            }
        }

        return false;
    }

    public static final function strToSlug($str)
    {
        // replace non letter or digits by -
        $str = preg_replace('~[^\pL\d]+~u', '-', $str);

        // transliterate
        $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);

        // remove unwanted characters
        $str = preg_replace('~[^-\w]+~', '', $str);

        // trim
        $str = trim($str, '-');

        // remove duplicate -
        $str = preg_replace('~-+~', '-', $str);

        // lowercase
        $str = strtolower($str);

        if (empty($str)) {
            return 'n-a';
        }

        return $str;
    }

    public static final function array_get($array, $key, $default = null)
    {
        if (!Arr::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (Arr::exists($array, $key)) {
            if ($array[$key] === "" || $array[$key] === null) {
                return $default;
            }
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (Arr::accessible($array) && Arr::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * @param string $date
     * @param string $format
     * @return false|string
     */
    public static final function dateFEtoBE(string $date, string $format = "d/m/Y H:i")
    {
        $date = trim($date);

        if (empty($date)) {
            return "";
        }
        $dateTime = explode(" ", $date);

        if (count($dateTime) != 2) {
            return " ";
        }

        $date = $dateTime[0];
        $time = $dateTime[1] . ":00";

        $days = explode("/", $date);
        if (count($days) != 3) {
            return "";
        }

        return date("Y-m-d H:i:s", strtotime("{$days[2]}-{$days[1]}-{$days[0]} $time"));
    }

    /**
     * Get header store ID
     * @return mixed
     */
    public static function getHeaderStoreID()
    {
        return app('request')->header('x-api-key');
    }

    public static final function getIDP()
    {
        return json_decode(json_encode([
            'company_id' => 24,
            'store_id'   => 46,
            'sync_name'  => env('VIETTEL_SYNC_NAME'),
        ]));
    }

    public static function sendMessage($message = '', $option = [], $level = Logger::DEBUG)
    {
        try {
            // create a log channel
            $log = new Logger('Nutifood');
            $log->pushHandler(new TelegramBotHandler(env('TELEGRAM_BOT_TOKEN'), env('TELEGRAM_CHAT_ID')));
            $log->log($level, is_string($message) ? $message : json_encode($message), is_array($option) ? $option : array($option));
            $log->reset();
            $log->close();
        } catch (\Exception $e) {
        }
    }
}
