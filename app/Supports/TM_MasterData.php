<?php
/**
 * User: dai.ho
 * Date: 11/18/2019
 * Time: 05:00 PM
 */

namespace App\Supports;


use App\MasterData;

class TM_MasterData
{
    public static function get($code)
    {
        if (empty($code)) {
            return new MasterData();
        }

        return MasterData::model()->where('code', $code)->first();
    }
}