<?php


namespace App\V1\Models;


use App\Profile;
use App\TM;

class UserLocationModel extends AbstractModel
{
    public function __construct($model = null)
    {
        parent::__construct($model);
    }

    public function upsert($input)
    {
        $latLong = explode(',', $input['latlong']);
        $param = [
            'user_id'   => TM::getCurrentUserId(),
            'lat'       => $latLong[0],
            'long'      => $latLong[1],
            'login_at'  => date('Y-m-d H:i:s', time()),
            'is_active' => 1,

        ];
        //Update Profile
        $profile = Profile::model()->where('user_id', TM::getCurrentUserId())->first();
        if (!empty($profile)) {
            $profile->lat = $latLong[0];
            $profile->long = $latLong[1];
            $profile->save();
        }
        $user_location = $this->create($param);
        return $user_location;
    }
}