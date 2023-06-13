<?php

namespace App\Supports;

use App\Store;
use App\TM;
use App\User;
use App\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataUser
{
    /**
     * @var null $companyId
     */
    public $companyId = null;

    /**
     * @var null $storeId
     */
    public $storeId = null;

    /**
     * @var null $groupId
     */
    public $groupId = null;

    /**
     * @var array $areaIds
     */
    public $areaIds = [];

    /**
     * @var $instance
     */
    protected static $instance;

    /**
     * DataUser constructor.
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $request = $request ?? app('request');
        $this->handle($request);
    }

    /**
     * Handle
     * @param $request
     */
    private function handle($request)
    {
        if (TM::getCurrentUserId()) {
            $this->storeId = TM::getCurrentStoreId();
            $this->groupId = TM::getCurrentGroupId();
            $this->companyId = TM::getCurrentCompanyId();
            $group = UserGroup::find(TM::getCurrentGroupId());
            if (!empty($group) && $group->is_view) {
                $user = User::find(000);
                if (!empty($user->area)) {
                    $this->areaIds = $user->area->pluck('id')->toArray();
                }
            }
        } else {
            $authorization = $request->header('authorization');
            if (!empty($authorization) && strlen($authorization) == 71) {

                $storeToken = str_replace("Bearer ", "", $authorization);

                $store = Store::select(['id', 'company_id'])->where('token', $storeToken)->first();
                if (!empty($store)) {
                    $this->storeId = $store->id;
                    $this->companyId = $store->company_id;

                    $group = UserGroup::where('company_id', $store->company_id)->where('is_default', 1)->first();

                    if (!empty($group)) {
                        $this->groupId = $group->id;
                    }
                }
            }
        }
    }

    /**
     * Get instance
     * @return DataUser
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = (new self());
        }
        return self::$instance;
    }

    /**
     * All data
     * @return array
     */
    public function all()
    {
        return [$this->storeId, $this->areaIds, $this->groupId, $this->companyId];
    }

    public function info()
    {
        return [$this->storeId, $this->companyId];
    }
}