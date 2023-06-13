<?php


namespace App\V1\Transformers\Membership;


use App\MembershipRank;
use App\Supports\TM_Error;
use App\User;
use League\Fractal\TransformerAbstract;

class MembershipTransformer extends TransformerAbstract
{
    public function transform(User $membership)
    {
        try {
            return [
                'membership_rank_id'      => object_get($membership, 'user.ranking_id'),
                'membership_rank'         => object_get($membership, 'user.membership.code', 'NEW'),
                'membership_rank_name'    => array_get($membership, 'user.membership.name', 'Thành viên mới'),
                'membership_rank_icon'    => array_get($membership, 'user.membership.icon'),
                'points'                  => $membership->point ?? 0,
                'membership_package'      => null,
                'membership_package_name' => null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}