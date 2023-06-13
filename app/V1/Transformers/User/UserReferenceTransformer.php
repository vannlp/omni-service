<?php


namespace App\V1\Transformers\User;


use App\User;
use App\UserReference;
use Illuminate\Support\Arr;
use League\Fractal\TransformerAbstract;

class UserReferenceTransformer extends TransformerAbstract
{
    public function transform(UserReference $userReference)
    {
        return [
            'id'            => $userReference->id,
            'user_id'       => $userReference->user_id,
            'level'         => $userReference->level,
            'store_id'      => $userReference->store_id,
            'parent_id'     => $userReference->parent_id,
            'user'          => Arr::get($userReference, 'user', null),
            'grandChildren' => Arr::get($userReference, 'grandChildren', null),
            'is_active'     => $userReference->is_active,
        ];
    }
}