<?php


namespace App\V1\Transformers\User;

use App\Supports\TM_Error;
use App\User;
use League\Fractal\TransformerAbstract;

class UserZoneHubTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            return [
                'name' => object_get($user, 'zoneHub.name', null),
                'id'   => object_get($user, 'zoneHub.id', null),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}