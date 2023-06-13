<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\User;

use App\Supports\TM_Error;
use App\User;
use League\Fractal\TransformerAbstract;

/**
 * Class UserTransformer
 *
 * @package App\V1\Transformers
 */
class UserListViewTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            return [
                'id'            => $user->id,
                'code'          => $user->code,
                'phone'         => $user->phone,
                'email'         => $user->email,
                'type'          => $user->type,
                'address'       => object_get($user, "profile.address", null),
                'full_name'     => object_get($user, "profile.full_name", null),
                'group_id'      => $user->group_id,
                'group_name'    => object_get($user, 'group.name'),
                'group_code'    => object_get($user, 'group.code'),
                'role_id'       => $user->role_id,
                'role_name'     => $user->role->name ?? null,
                'role_code'     => $user->role->code ?? null,
                'is_active'     => $user->is_active,
                'tax'           => object_get($user, 'tax', null),
                'updated_at'    => date('d-m-Y', strtotime($user->updated_at)),
                'parent_leader_code'      => $user->parentLeader->name ?? null,
                'parent_leader_name'      => $user->parentLeader->code ?? null,
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
