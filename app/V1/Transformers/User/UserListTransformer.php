<?php
/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\V1\Transformers\User;

use App\Card;
use App\Image;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\User;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;
use tests\Mockery\Adapter\Phpunit\EmptyTestCase;

/**
 * Class UserTransformer
 *
 * @package App\V1\Transformers
 */
class UserListTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            return [
                'id'              => $user->id,
                'code'            => $user->code,
                'phone'           => $user->phone,
                'email'           => $user->email,
                'type'            => $user->type,
                'first_name'      => object_get($user, "profile.first_name", null),
                'last_name'       => object_get($user, "profile.last_name", null),
                'short_name'      => object_get($user, "profile.short_name", null),
                'full_name'       => object_get($user, "profile.full_name", null),
                'id_number'       => object_get($user, "profile.id_number", null),
                'role_id'         => $user->role_id,
                'store_id'        => $user->store_id ?? null,
                'company_id'      => $user->company_id,
                'company_code'    => object_get($user, 'company.code'),
                'company_name'    => object_get($user, 'company.name'),
                'area_id'         => !empty($user->area_id) ? explode(",", $user->area_id) : null,
                'group_id'        => $user->group_id,
                'group_code'      => object_get($user, 'group.code'),
                'group_name'      => object_get($user, 'group.name'),
                'is_partner'      => $user->is_partner,
                'agent_register'  => $user->agent_register,
                'is_active'       => $user->is_active,
                'tax'             => object_get($user, 'tax', null),
                'account_status'  => object_get($user, 'account_status', null),
                'customer_type'   => object_get($user, 'customer_type', null),
                'reference_phone' => object_get($user, 'reference_phone', null),
                'created_at'      => date('d-m-Y', strtotime($user->created_at)),
                'updated_at'      => date('d-m-Y', strtotime($user->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
