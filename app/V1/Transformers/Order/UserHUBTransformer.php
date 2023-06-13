<?php

namespace App\V1\Transformers\Order;

use App\User;
use League\Fractal\TransformerAbstract;

class UserHUBTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        return [
            'id'    => $user->id,
            'code'  => $user->code,
            'name'  => $user->name,
            'phone' => $user->phone,
            'email' => $user->email
        ];
    }
}
