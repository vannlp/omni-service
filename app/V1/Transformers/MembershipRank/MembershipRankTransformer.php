<?php


namespace App\V1\Transformers\MembershipRank;


use App\MembershipRank;
use App\Supports\TM_Error;
use League\Fractal\TransformerAbstract;

class MembershipRankTransformer extends TransformerAbstract
{
    public function transform(MembershipRank $membershipRank)
    {
        try {
            return [
                'id'          => $membershipRank->id,
                'code'        => $membershipRank->code,
                'name'        => $membershipRank->name,
                'point'       => $membershipRank->point,
                'point_rate'  => $membershipRank->point_rate,
                'total_sale'  => $membershipRank->total_sale,
                'description' => $membershipRank->description,
                'icon'        => $membershipRank->icon,
                'company_id'  => $membershipRank->company_id,
                'date_start'  => date('d-m-Y', strtotime($membershipRank->date_start)),
                'date_end'    => date('d-m-Y', strtotime($membershipRank->date_end)),
                'is_active'   => $membershipRank->is_active,
                'created_at'  => date('d-m-Y', strtotime($membershipRank->created_at)),
                'updated_at'  => date('d-m-Y', strtotime($membershipRank->updated_at)),
            ];
        }
        catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}