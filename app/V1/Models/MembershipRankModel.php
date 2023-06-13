<?php


namespace App\V1\Models;


use App\MembershipRank;
use App\Supports\Message;
use App\TM;

class MembershipRankModel extends AbstractModel
{
    public function __construct(MembershipRank $model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $id           = !empty($input['id']) ? $input['id'] : 0;
        $getCompanyId = TM::getCurrentCompanyId();
        if ($id) {
            $membershipRank = MembershipRank::find($id);
            if (empty($membershipRank)) {
                throw new \Exception(Message::get("V003", "ID: #$id"));
            }
            $membershipRank->name        = array_get($input, 'name', $membershipRank->name);
            $membershipRank->code        = array_get($input, 'code', $membershipRank->code);
            $membershipRank->point       = array_get($input, 'point', $membershipRank->point);
            $membershipRank->point_rate  = array_get($input, 'point_rate', $membershipRank->point_rate);
            $membershipRank->description = array_get($input, 'description', $membershipRank->description);
            $membershipRank->icon        = array_get($input, 'icon', $membershipRank->icon);
            $membershipRank->total_sale        = array_get($input, 'total_sale', $membershipRank->total_sale);
            $membershipRank->company_id  = $getCompanyId;
            $membershipRank->date_start  = !empty($input['date_start']) ? date("Y-m-d", strtotime($input['date_start'])) : $membershipRank->date_start;
            $membershipRank->date_end    = !empty($input['date_end']) ? date("Y-m-d", strtotime($input['date_end'])) : $membershipRank->date_end;
            $membershipRank->updated_at  = date("Y-m-d H:i:s", time());
            $membershipRank->updated_by  = TM::getCurrentUserId();
            $membershipRank->save();
        } else {
            $param          = [
                'code'        => $input['code'],
                'name'        => $input['name'],
                'point'       => $input['point'],
                'total_sale'  => $input['total_sale'] ?? 0,
                'company_id'  => $getCompanyId,
                'date_start'  => !empty($input['date_start']) ? date("Y-m-d", strtotime($input['date_start'])) : null,
                'date_end'    => !empty($input['date_end']) ? date("Y-m-d", strtotime($input['date_end'])) : null,
                'point_rate'  => array_get($input, 'point_rate', 1),
                'description' => array_get($input, 'description'),
                'icon'        => array_get($input, 'icon'),
                'is_active'   => 1,
            ];
            $membershipRank = $this->create($param);
        }

        return $membershipRank;
    }

    public function search($input = [], $with = [], $limit = null)
    {
        $query = $this->make($with);
        $this->sortBuilder($query, $input);
        $companyId = TM::getCurrentCompanyId();
        $query     = $query->where('company_id', $companyId);
        foreach ($input as $column => $value) {
            if (in_array($column,
                    ['code', 'name'])
                && !empty($value)) {
                $query = $query->where($column, 'like', "%$value%");
            }
        }
        $query = $query->orderBy('point', 'asc');
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