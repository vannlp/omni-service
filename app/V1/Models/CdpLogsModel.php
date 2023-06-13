<?php

/**
 * User: SANG NGUYEN
 * Date: 2/24/2019
 * Time: 3:08 PM
 */

namespace App\V1\Models;

use App\CdpLogs;
use App\SapLogs;
use App\TM;
use App\Supports\Message;
use App\Supports\TM_Error;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

class CdpLogsModel extends AbstractModel
{
    private static $config = null;
    public function __construct(CdpLogs $model = null)
    {
        parent::__construct($model);
        self::$config = DB::table('active_status')->where(['name' => 'CDP', 'is_active' => 1])->first();
    }

    //set price
    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);

        // if(TM::getCurrentGroupCode() == 'HUB' || TM::getCurrentGroupCode() == 'DISTRIBUTOR') {
        //     $query->whereHas('orders', function ($query) {
        //         $query->where('distributor_code', TM::getCurrentUserCode());
        //     });
        // }   

        if (!empty($input['code'])) {
            $input['code'] = str_clean_special_characters($input['code']);
            $query->where('code', 'like', '%' . $input['code'] . '%');
        }

        if (!empty($input['from']) && !empty($input['to'])) {
            $query->where('created_at', '>=', date('Y-m-d', strtotime($input['from'])))
                ->where('created_at', '<=', date('Y-m-d', strtotime($input['to'])));
        }

        if (!empty($input['from']) && empty($input['to'])) {
            $query->where('created_at', '>=', date('Y-m-d',  strtotime($input['from'])))
                ->where('created_at', '<=', date('Y-m-d', time()));
        }

        if (empty($input['from']) && !empty($input['to'])) {
            $query->where('created_at', '>=', date('Y-m-d',  time()))
                ->where('created_at', '<=', date('Y-m-d', strtotime($input['to'])));
        }

        //SUCCESS / FAILED
        if (!empty($input['status'])) {
            $query->where('status', $input['status']);
        }

        if (!empty($input['sync_type'])) {
            if ($input['sync_type'] == 'DSITRIBUTOR') {
                $query->where('sync_type', 'like', '%Đồng bộ nhà phân phối%');
            } else if ($input['sync_type'] == 'CUSTOMER') {
                $query->where('sync_type', 'like', '%Đồng bộ khách hàng%');
            } else if ($input['sync_type'] == 'ORDER') {
                $query->where('sync_type', 'like', '%Đồng bộ đơn hàng%');
            } else if ($input['sync_type'] == 'WAREHOUSE') {
                $query->where('sync_type', 'like', '%Đồng bộ kho%');
            }
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
