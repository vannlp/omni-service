<?php

/**
 * User: Dai Ho
 * Date: 22-Mar-17
 * Time: 23:43
 */

namespace App\Http\Transformers;

use App\Supports\TM_Error;
use App\User;
use League\Fractal\TransformerAbstract;

/**
 * Class UserTransformer
 *
 * @package App\V1\Transformers
 */
class UserInfoTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            $avatar_url = object_get($user, "profile.avatar_url");
            $avatar = !empty($avatar_url) ? $avatar_url : (!empty($user->profile->avatar) ? $user->profile->avatar : null);
            return [
                'id'         => $user->id,
                'user'       => $user->user,
                'email'      => $user->email,
                'code'       => strtoupper($user->code),
                'first_name' => object_get($user, "profile.first_name", null),
                'last_name'  => object_get($user, "profile.last_name", null),
                'short_name' => object_get($user, "profile.short_name", null),
                'full_name'  => object_get($user, "profile.full_name", null),
                'address'    => object_get($user, "profile.address", null),
                'phone'      => object_get($user, "profile.phone", null),
                'birthday'   => object_get($user, 'profile.birthday', null),
                'gender'     => object_get($user, "profile.gender", "O"),
                'gender_name'=> config('constants.STATUS.GENDER')[strtoupper(object_get($user, "profile.gender", 'O'))],
                'avatar'     => $avatar ?? null,
                'id_number'  => object_get($user, "profile.id_number", null),
                'language'   => object_get($user, "profile.language", "VI"),
                'is_active'  => $user->is_active,
                'updated_at' => date('Y/m/d', strtotime($user->updated_at)),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
