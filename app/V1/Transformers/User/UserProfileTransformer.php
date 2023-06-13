<?php
/**
 * User: Administrator
 * Date: 14/10/2018
 * Time: 07:04 PM
 */

namespace App\V1\Transformers\User;


use App\Supports\TM_Error;
use App\User;
use Illuminate\Support\Facades\URL;
use League\Fractal\TransformerAbstract;

class UserProfileTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        $avatar = !empty($user->profile->avatar) ? url('/v0') . "/img/" . $user->profile->avatar : null;

        try {
            return [
                'id'            => $user->id,
                'code'          => $user->code,
                'phone'         => $user->phone,
                'email'         => $user->email,
                'type'          => $user->type,
                'role_code '    => object_get($user, "role.code", null),
                'first_name'    => object_get($user, "profile.first_name", null),
                'last_name'     => object_get($user, "profile.last_name", null),
                'short_name'    => object_get($user, "profile.short_name", null),
                'full_name'     => object_get($user, "profile.full_name", null),
                'address'       => object_get($user, "profile.address", null),
                'city_code'     => object_get($user, "profile.city_code", null),
                'city_name'     => object_get($user, "profile.city.name", null),
                'district_code' => object_get($user, "profile.district_code", null),
                'district_name' => object_get($user, "profile.district.name", null),
                'ward_code'     => object_get($user, "profile.ward_code", null),
                'ward_name'     => object_get($user, "profile.ward.name", null),
                'avatar'        => $avatar,
                'companies'     => $user->userCompanies,
                'group_id'      => $user->group_id,
                'group_code'    => object_get($user, 'group.code'),
                'group_name'    => object_get($user, 'group.name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
