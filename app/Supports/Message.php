<?php
/**
 * User: Sy Dai
 * Date: 02-Aug-16
 * Time: 16:40
 */

namespace App\Supports;

/**
 * Class Message
 *
 * @package App\Supports
 */
class Message
{
    /**
     * Get Message. English is Default.
     *
     * @param $code string or array. Ex: $code = "KPI001"
     * @param ...$params
     *
     * @return mixed|null
     */
    public static function get($code, ...$params)
    {
        if (empty($code)) {
            return null;
        }
        $lang = array_get($_REQUEST, 'lang', 'VI');
        $message = array_get(config('messages'), "$code", ["EN" => "`$code`"]);
        $message = array_get($message, $lang, $message['EN']);

        if (!empty($params)) {
            foreach ($params as $key => $param) {
                $message = str_replace("{{$key}}", $param, $message);
            }
        }
        return $message;
    }
}