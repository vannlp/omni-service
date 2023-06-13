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
class UserCLientByPhoneTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        try {
            return [
                'id'         => $user->id,
                'full_name'  => object_get($user, "profile.full_name", null),
                'group_name' => object_get($user, 'group.name'),
            ];
        } catch (\Exception $ex) {
            $response = TM_Error::handle($ex);
            throw new \Exception($response['message'], $response['code']);
        }
    }
}
