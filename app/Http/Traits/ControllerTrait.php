<?php


namespace App\Http\Traits;


use App\Store;
use App\User;
use App\UserReference;
use App\V1\Models\UserReferenceModel;

trait ControllerTrait
{
    public function updateUserReference($user, Store $store)
    {
        if (!empty($user->reference_phone)) {
            $userReference = User::model()->where('phone', $user->reference_phone)->where('store_id', $store->id)->first();
            if (!empty($userReference)) {
                $checkUserReferences = UserReference::model()->where('user_id', $userReference->id)->first();
                $userReferenceModel = new UserReferenceModel();
                if (empty($checkUserReferences)) {
                    $paramUserReference = [
                        'user_id'    => $userReference->id,
                        'store_id'   => $store->id,
                        'level'      => 1,
                        'created_by' => $user->id
                    ];
                    $result = $userReferenceModel->create($paramUserReference);
                    if (!empty($result)) {
                        $userReference_register = [
                            'user_id'    => $user->id,
                            'store_id'   => $store->id,
                            'level'      => 2,
                            'parent_id'  => $result->id,
                            'created_by' => $user->id
                        ];
                        $userReferenceModel->refreshModel();
                        $userReferenceModel->create($userReference_register);
                    }
                } else {
                    if ($checkUserReferences->level < 3) {
                        $userReference_register = [
                            'user_id'    => $user->id,
                            'store_id'   => $store->id,
                            'level'      => $checkUserReferences->level + 1,
                            'parent_id'  => $checkUserReferences->id,
                            'created_by' => $user->id
                        ];
                        $userReferenceModel->create($userReference_register);
                    }
                }
            }
        }
    }
}