<?php


namespace App\V1\Models;


use App\TM;
use App\User;
use App\VideoCallAccount;

class VideoCallAccountModel extends AbstractModel
{
    /**
     * VideoCallAccountModel constructor.
     * @param VideoCallAccount|null $model
     */
    public function __construct(VideoCallAccount $model = null)
    {
        parent::__construct($model);
    }

    public function getAccount()
    {
        $userId = TM::getCurrentUserId();
        $data = $this->model->where('user_id', $userId)->first();
        return $data;
    }

    public function createAccount()
    {
        $userId = TM::getCurrentUserId();
        $user = User::find($userId);
        $autoPassword = substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'), 1, 12);
        $param = [
            'user_id'  => $userId,
            'phone'    => $user->phone,
            'password' => $autoPassword,
        ];
        $data = $this->model->create($param);
        return $data;
    }
}