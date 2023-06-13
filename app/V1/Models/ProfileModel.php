<?php
/**
 * User: Administrator
 * Date: 28/09/2018
 * Time: 11:03 PM
 */

namespace App\V1\Models;


use App\Profile;
use App\Supports\Message;

class ProfileModel extends AbstractModel
{
    /**
     * CityModel constructor.
     * @param Profile|null $model
     */
    public function __construct(Profile $model = null)
    {
        parent::__construct($model);
    }

    /**
     * Nearest partner list
     *
     * @param array $input
     * @return array
     */
    public function nearestPartners($input = [])
    {
        if (empty($input['lat']) || empty($input['long'])) {
            throw new \Exception(Message::get("V029"));
        }
        // 6370.693485653 = 60 * 1.1515  * 1.609344 * 180/PI
        // 57.295779513082 = 180/PI
        // 0.017453292519943 = PI/180

        $profiles = $this->model
            ->selectRaw(" full_name, phone, CONCAT_WS(',',lat,profiles.long) as latlong,
             acos(
                (sin(lat * 0.017453292519943) * sin('{$input['lat']}' * 0.017453292519943)) + 
                (cos(lat * 0.017453292519943) * cos('{$input['lat']}' * 0.017453292519943) * cos((profiles.long - '{$input['long']}') * 0.017453292519943))
                )
               * 6370.693485653 as distance")
            ->where('ready_work', 1)
            ->orderByRaw("distance ASC")
            ->take(10)
            ->get();

        return $profiles;
    }

    /**
     * Nearest partner
     *
     * @param array $input
     * @return array
     */
    public function nearestPartner($input = [])
    {
        if (empty($input['lat']) || empty($input['long'])) {
            throw new \Exception(Message::get("V029"));
        }
        // 6370.693485653 = 60 * 1.1515  * 1.609344 * 180/PI
        // 57.295779513082 = 180/PI
        // 0.017453292519943 = PI/180

        $profiles = $this->model
            ->selectRaw("user_id, full_name, phone, CONCAT_WS(',',lat,profiles.long) as latlong,
             acos(
                (sin(lat * 0.017453292519943) * sin('{$input['lat']}' * 0.017453292519943)) + 
                (cos(lat * 0.017453292519943) * cos('{$input['lat']}' * 0.017453292519943) * cos((profiles.long - '{$input['long']}') * 0.017453292519943))
                )
               * 6370.693485653 as distance")
            ->where('ready_work', 1)
            ->where('is_active', 1)
            ->orderByRaw("distance ASC")
            ->first();

        return $profiles;
    }

    /**
     * Calculate the length of 2 positions
     *
     * @param $lat1 , $long1, $lat2, $long2, $unit
     * @return float
     */
    public function distance($lat1, $long1, $lat2, $long2, $unit)
    {
        $theta = $long1 - $long2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
        $unit = strtoupper($unit);

        if ($unit == "K") {
            return ($miles * 1.609344);
        } else if ($unit == "N") {
            return ($miles * 0.8684);
        } else {
            return $miles;
        }
    }
}