<?php

namespace App\Http\Middleware;

use App\Role;
use App\RolePermission;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\User;
use Closure;
use Dingo\Api\Routing\Router;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class Authorize
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var
     */
    protected $userInfo;

    /**
     * @var
     */
    protected $permissions;


    /**
     * Authorize constructor.
     */
    public function __construct(Router $router)
    {
        if (!TM::allowRemote()) {
            throw new AccessDeniedHttpException(Message::get('remote_denied'));
        }

        $this->router = $router;

        $userId         = TM::getCurrentUserId();
        $this->userInfo = User::find($userId);
    }

    /**
     * @param $request
     * @param Closure $next
     * @return \Illuminate\Http\JsonResponse|mixed
     * @throws \Exception
     */
    public function handle($request, Closure $next)
    {
        // For UnAuthorize
        $headers = $request->headers->all();
        if (!empty($headers['authorization'][0]) && strlen($headers['authorization'][0]) == 71) {
            $store_token_input = str_replace("Bearer ", "", $headers['authorization'][0]);
            if ($store_token_input && strlen($store_token_input) == 64) {
                // Check Route
                // APP-MENU-VIEW-MENU
                $route_name = $this->router->getCurrentRoute()->getAction('name');
                if ($route_name && in_array($route_name, config('normalauth.allow_name'))) {
                    return $next($request);
                }
            }
        }
        if (empty($this->userInfo)) {
//            throw new \Exception(Message::get("unauthorized"));
            return response()->json([
                'message'     => Message::get("unauthorized"),
                'status_code' => Response::HTTP_UNAUTHORIZED,
            ], 401);
        }

        if (TM::getCurrentRole() == "SUPERADMIN") {
            return $next($request);
        }
        $permissions = [];
        if (!empty($this->userInfo->role_id)) {
            $currentPermissions = RolePermission::with(['permission'])
                ->where('role_id', $this->userInfo->role_id)->get()->toArray();
            $permissions        = array_pluck($currentPermissions, null, 'permission.code');
        }

        $action = array_get($this->router->getCurrentRoute()->getAction(), 'action', null);

        if (!$action) {
            return $next($request);
            //throw new AccessDeniedHttpException('Permission denied!');
        }

        if (empty($permissions[$action])) {
            throw new AccessDeniedHttpException(Message::get("no_permission"));
        }

        return $next($request);
    }

}
