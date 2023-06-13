<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Models;

use App\TM;
use App\UserCompany;
use App\UserLog;
use App\UserStore;

/**
 * Class UserLogModel
 *
 * @package App\V1\Models
 */
class UserLogModel extends AbstractModel
{
    public function __construct(UserLog $model = null)
    {
        parent::__construct($model);
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $companyId = TM::getCurrentCompanyId();
        $userCompanies = UserCompany::model()->where('company_id', $companyId)->pluck('user_id')->toArray();
        $query = $query->whereIn('user_id', $userCompanies);
        if (!empty($input['user_name'])) {
            $query->whereHas('user.profile', function ($q) use ($input) {
                $q->where('full_name', 'like', "%{$input['user_name']}%");
            });
        }
        if (!empty($input['sort']['created_at'])) {
            $query->orderBy('created_at', $input['sort']['created_at']);
        }

        $userIds = UserStore::model()->where('store_id', TM::getCurrentStoreId())->pluck('user_id')->toArray();
        $query->whereIn('user_id', $userIds);

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