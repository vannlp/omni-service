<?php

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Config;

if (!function_exists('config_path')) {
    /**
     * Get the configuration path.
     *
     * @param string $path
     *
     * @return string
     */
    function config_path($path = '')
    {
        return app()->basePath() . '/config' . ($path ? '/' . $path : $path);
    }
}

if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = '';
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }

        return $headers;
    }
}

if (!function_exists('public_path')) {
    /**
     * Return the path to public dir
     *
     * @param null $path
     *
     * @return string
     */
    function public_path($path = null)
    {
        return rtrim(app()->basePath('public/' . $path), '/');
    }
}

if (!function_exists('get_image')) {
    /**
     * @param $url
     *
     * @return string
     */
    function get_image($url)
    {
        if (empty($url)) {
            return null;
        }

        $path = storage_path(Config::get('constants.URL_IMG')) . "/$url";

        $type = pathinfo($path, PATHINFO_EXTENSION);

        if (!file_exists($path)) {
            return null;
        }

        $data = file_get_contents($path);

        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}

if (!function_exists('upload_image')) {
    /**
     * @param        $data
     * @param        $dir
     * @param        $file_name
     * @param string $type
     *
     * @return bool|null
     */
    function upload_image($data, $dir, $file_name, $type = "jpg")
    {
        if (empty($data)) {
            return null;
        }

        if (!file_exists(storage_path(Config::get('constants.URL_IMG')) . "/" . $dir)) {
            mkdir(storage_path(Config::get('constants.URL_IMG')) . "/" . $dir, 0777, true);
        }

        file_put_contents(storage_path(Config::get('constants.URL_IMG')) . "/$dir/$file_name.$type",
            base64_decode(preg_replace('#^data:image/\w+;base64,#i',
                '', $data)));

        return true;
    }
}

if (!function_exists('is_image')) {
    /**
     * @param $base64
     *
     * @return bool
     */
    function is_image($base64)
    {
        if (empty($base64)) {
            return false;
        }

        $base = base64_decode($base64);

        if (empty($base)) {
            return false;
        }

        $file_size = strlen($base);

        if ($file_size / 1024 / 1024 > Config::get("constant.IMG_UPLOAD_MAXSIZE", 1)) {
            return false;
        }

        return true;
    }
}

if (!function_exists('array_get_mes')) {
    function array_get_tm($array, $key, $default = null)
    {
        if (!Arr::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (Arr::exists($array, $key)) {
            if ($array[$key] === "" || $array[$key] === null) {
                return $default;
            }
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (Arr::accessible($array) && Arr::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    if (!function_exists('get_device')) {
        function get_device()
        {
            $tablet_browser = 0;
            $mobile_browser = 0;
            $mobile_app     = 0;
            if (empty($_SERVER['HTTP_USER_AGENT'])) {
                $_SERVER['HTTP_USER_AGENT'] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36 Edg/92.0.902.67";
            }

            if (preg_match('/(tablet|ipad|playbook)|(android(?!.*(mobi|opera mini)))/i',
                strtolower($_SERVER['HTTP_USER_AGENT']))) {
                $tablet_browser++;
            }

            if (preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|android|iemobile)/i',
                strtolower($_SERVER['HTTP_USER_AGENT']))) {
                $mobile_browser++;
            }

            if ((strpos(strtolower($_SERVER['HTTP_ACCEPT']),
                        'application/vnd.wap.xhtml+xml') > 0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))
            ) {
                $mobile_browser++;
            }

            $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'], 0, 4));
            $mobile_app_agents = [
                'dart',
            ];

            if (in_array($mobile_ua, $mobile_app_agents)) {
                $mobile_app++;
            }

            $mobile_agents = [
                'w3c ',
                'acs-',
                'alav',
                'alca',
                'amoi',
                'audi',
                'avan',
                'benq',
                'bird',
                'blac',
                'blaz',
                'brew',
                'cell',
                'cldc',
                'cmd-',
                'dang',
                'doco',
                'eric',
                'hipt',
                'inno',
                'ipaq',
                'java',
                'jigs',
                'kddi',
                'keji',
                'leno',
                'lg-c',
                'lg-d',
                'lg-g',
                'lge-',
                'maui',
                'maxo',
                'midp',
                'mits',
                'mmef',
                'mobi',
                'mot-',
                'moto',
                'mwbp',
                'nec-',
                'newt',
                'noki',
                'palm',
                'pana',
                'pant',
                'phil',
                'play',
                'port',
                'prox',
                'qwap',
                'sage',
                'sams',
                'sany',
                'sch-',
                'sec-',
                'send',
                'seri',
                'sgh-',
                'shar',
                'sie-',
                'siem',
                'smal',
                'smar',
                'sony',
                'sph-',
                'symb',
                't-mo',
                'teli',
                'tim-',
                'tosh',
                'tsm-',
                'upg1',
                'upsi',
                'vk-v',
                'voda',
                'wap-',
                'wapa',
                'wapi',
                'wapp',
                'wapr',
                'webc',
                'winw',
                'winw',
                'xda ',
                'xda-',
            ];

            if (in_array($mobile_ua, $mobile_agents)) {
                $mobile_browser++;
            }

            if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'opera mini') > 0) {
                $mobile_browser++;
                $stock_ua = strtolower(isset($_SERVER['HTTP_X_OPERAMINI_PHONE_UA']) ? $_SERVER['HTTP_X_OPERAMINI_PHONE_UA'] : (isset($_SERVER['HTTP_DEVICE_STOCK_UA']) ? $_SERVER['HTTP_DEVICE_STOCK_UA'] : ''));
                if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $stock_ua)) {
                    $tablet_browser++;
                }
            }

            if ($mobile_app > 0) {
                return 'APP';
            } else if ($tablet_browser > 0) {
                return 'TABLET';
            } else if ($mobile_browser > 0) {
                return 'PHONE';
            } else {
                return 'DESKTOP';
            }
        }
    }
}

if (!function_exists("string_to_slug")) {
    function string_to_slug($str)
    {
        $str = strtolower($str);
        $str = strtr($str, [
            'à' => 'a',
            'ạ' => 'a',
            'á' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'â' => 'a',
            'ầ' => 'a',
            'ấ' => 'a',
            'ậ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ă' => 'a',
            'ằ' => 'a',
            'ắ' => 'a',
            'ặ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'À' => 'a',
            'Ạ' => 'a',
            'Á' => 'a',
            'Ả' => 'a',
            'Ã' => 'a',
            'Â' => 'a',
            'Ầ' => 'a',
            'Ấ' => 'a',
            'Ậ' => 'a',
            'Ẩ' => 'a',
            'Ẫ' => 'a',
            'Ă' => 'a',
            'Ằ' => 'a',
            'Ắ' => 'a',
            'Ặ' => 'a',
            'Ẳ' => 'a',
            'Ẵ' => 'a',

            'è' => 'e',
            'é' => 'e',
            'ẹ' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ê' => 'e',
            'ề' => 'e',
            'ế' => 'e',
            'ệ' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'È' => 'e',
            'É' => 'e',
            'Ẹ' => 'e',
            'Ẻ' => 'e',
            'Ẽ' => 'e',
            'Ê' => 'e',
            'Ề' => 'e',
            'Ế' => 'e',
            'Ệ' => 'e',
            'Ể' => 'e',
            'Ễ' => 'e',

            'ì' => 'i',
            'í' => 'i',
            'ị' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'Ì' => 'i',
            'Í' => 'i',
            'Ị' => 'i',
            'Ỉ' => 'i',
            'Ĩ' => 'i',

            'ò' => 'o',
            'ó' => 'o',
            'ọ' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ô' => 'o',
            'ồ' => 'o',
            'ố' => 'o',
            'ộ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ơ' => 'o',
            'ờ' => 'o',
            'ớ' => 'o',
            'ợ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'Ò' => 'o',
            'Ó' => 'o',
            'Ọ' => 'o',
            'Ỏ' => 'o',
            'Õ' => 'o',
            'Ô' => 'o',
            'Ồ' => 'o',
            'Ố' => 'o',
            'Ộ' => 'o',
            'Ổ' => 'o',
            'Ỗ' => 'o',
            'Ơ' => 'o',
            'Ờ' => 'o',
            'Ớ' => 'o',
            'Ợ' => 'o',
            'Ở' => 'o',
            'Ỡ' => 'o',

            'ù' => 'u',
            'ú' => 'u',
            'ụ' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ư' => 'u',
            'ừ' => 'u',
            'ự' => 'u',
            'ứ' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'Ù' => 'u',
            'Ú' => 'u',
            'Ụ' => 'u',
            'Ủ' => 'u',
            'Ũ' => 'u',
            'Ư' => 'u',
            'Ừ' => 'u',
            'Ự' => 'u',
            'Ứ' => 'u',
            'Ử' => 'u',
            'Ữ' => 'u',

            'ỳ' => 'y',
            'ý' => 'y',
            'ỵ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'Ỳ' => 'y',
            'Ý' => 'y',
            'Ỵ' => 'y',
            'Ỷ' => 'y',
            'Ỹ' => 'y',

            'đ' => 'd',
            'Đ' => 'd',
        ]);

        // replace non letter or digits by -
        $str = preg_replace('~[^\pL\d]+~u', '-', $str);

        // transliterate
        $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);

        // remove unwanted characters
        $str = preg_replace('~[^-\w]+~', '', $str);

        // trim
        $str = trim($str, '-');

        // remove duplicate -
        $str = preg_replace('~-+~', '-', $str);

        // lowercase
        $str = strtolower($str);

        if (empty($str)) {
            return 'n-a';
        }

        return $str;
    }
}

if (!function_exists("getDatesBetween")) {
    function getDatesBetween($inputFrom, $inputTo)
    {
        $start    = new \DateTime($inputFrom);
        $interval = new \DateInterval('P1D');
        $end      = new \DateTime(date('Y-m-d', strtotime("+1 day", strtotime($inputTo))));

        $period = new \DatePeriod($start, $interval, $end);

        $dates = array_map(function ($d) {
            return $d->format("Y-m-d");
        }, iterator_to_array($period));

        return $dates;
    }
}

if (!function_exists("pd")) {
    function pd($anyThing)
    {
        print_r($anyThing);
        die;
    }
}

if (!function_exists("ppd")) {
    function ppd($anyThing)
    {
        echo "<pre>";
        print_r($anyThing);
        die;
    }
}

if (!function_exists("mysql_escape")) {
    function mysql_escape($string)
    {
        if (is_array($string)) {
            return array_map(__METHOD__, $string);
        }

        if (!empty($string) && is_string($string)) {
            return str_replace(
                ['\\', "\0", "\n", "\r", "'", '"', "\x1a",],
                ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z',],
                $string);
        }

        return $string;
    }

    if (!function_exists("clean")) {
        function clean($str)
        {
            $str = strtolower($str);
            $str = strtr($str, [
                'à' => 'a',
                'ạ' => 'a',
                'á' => 'a',
                'ả' => 'a',
                'ã' => 'a',
                'â' => 'a',
                'ầ' => 'a',
                'ấ' => 'a',
                'ậ' => 'a',
                'ẩ' => 'a',
                'ẫ' => 'a',
                'ă' => 'a',
                'ằ' => 'a',
                'ắ' => 'a',
                'ặ' => 'a',
                'ẳ' => 'a',
                'ẵ' => 'a',
                'À' => 'a',
                'Ạ' => 'a',
                'Á' => 'a',
                'Ả' => 'a',
                'Ã' => 'a',
                'Â' => 'a',
                'Ầ' => 'a',
                'Ấ' => 'a',
                'Ậ' => 'a',
                'Ẩ' => 'a',
                'Ẫ' => 'a',
                'Ă' => 'a',
                'Ằ' => 'a',
                'Ắ' => 'a',
                'Ặ' => 'a',
                'Ẳ' => 'a',
                'Ẵ' => 'a',

                'è' => 'e',
                'é' => 'e',
                'ẹ' => 'e',
                'ẻ' => 'e',
                'ẽ' => 'e',
                'ê' => 'e',
                'ề' => 'e',
                'ế' => 'e',
                'ệ' => 'e',
                'ể' => 'e',
                'ễ' => 'e',
                'È' => 'e',
                'É' => 'e',
                'Ẹ' => 'e',
                'Ẻ' => 'e',
                'Ẽ' => 'e',
                'Ê' => 'e',
                'Ề' => 'e',
                'Ế' => 'e',
                'Ệ' => 'e',
                'Ể' => 'e',
                'Ễ' => 'e',

                'ì' => 'i',
                'í' => 'i',
                'ị' => 'i',
                'ỉ' => 'i',
                'ĩ' => 'i',
                'Ì' => 'i',
                'Í' => 'i',
                'Ị' => 'i',
                'Ỉ' => 'i',
                'Ĩ' => 'i',

                'ò' => 'o',
                'ó' => 'o',
                'ọ' => 'o',
                'ỏ' => 'o',
                'õ' => 'o',
                'ô' => 'o',
                'ồ' => 'o',
                'ố' => 'o',
                'ộ' => 'o',
                'ổ' => 'o',
                'ỗ' => 'o',
                'ơ' => 'o',
                'ờ' => 'o',
                'ớ' => 'o',
                'ợ' => 'o',
                'ở' => 'o',
                'ỡ' => 'o',
                'Ò' => 'o',
                'Ó' => 'o',
                'Ọ' => 'o',
                'Ỏ' => 'o',
                'Õ' => 'o',
                'Ô' => 'o',
                'Ồ' => 'o',
                'Ố' => 'o',
                'Ộ' => 'o',
                'Ổ' => 'o',
                'Ỗ' => 'o',
                'Ơ' => 'o',
                'Ờ' => 'o',
                'Ớ' => 'o',
                'Ợ' => 'o',
                'Ở' => 'o',
                'Ỡ' => 'o',

                'ù' => 'u',
                'ú' => 'u',
                'ụ' => 'u',
                'ủ' => 'u',
                'ũ' => 'u',
                'ư' => 'u',
                'ừ' => 'u',
                'ự' => 'u',
                'ứ' => 'u',
                'ử' => 'u',
                'ữ' => 'u',
                'Ù' => 'u',
                'Ú' => 'u',
                'Ụ' => 'u',
                'Ủ' => 'u',
                'Ũ' => 'u',
                'Ư' => 'u',
                'Ừ' => 'u',
                'Ự' => 'u',
                'Ứ' => 'u',
                'Ử' => 'u',
                'Ữ' => 'u',

                'ỳ' => 'y',
                'ý' => 'y',
                'ỵ' => 'y',
                'ỷ' => 'y',
                'ỹ' => 'y',
                'Ỳ' => 'y',
                'Ý' => 'y',
                'Ỵ' => 'y',
                'Ỷ' => 'y',
                'Ỹ' => 'y',

                'đ' => 'd',
                'Đ' => 'd',
            ]);

            // replace non letter or digits by -
            $str = preg_replace('~[^\pL\d]+~u', '-', $str);

            // transliterate
            $str = iconv('utf-8', 'ASCII//TRANSLIT//IGNORE', $str);

            // remove unwanted characters
            $str = preg_replace('~[^-\w]+~', '', $str);

            // trim
            $str = trim($str, '-');

            // remove duplicate -
            $str = preg_replace('~-+~', '-', $str);

            // lowercase
            $str = strtoupper($str);

            if (empty($str)) {
                return 'n-a';
            }

            return $str;
        }
    }

    if (!function_exists('format_number_in_k_notation')) {
        function format_number_in_k_notation(int $number): string
        {
            $suffixByNumber = function () use ($number) {
                if ($number < 1000) {
                    return sprintf('%d', $number);
                }

                if ($number < 1000000) {
                    return sprintf('%d%s', floor($number / 1000), 'K+');
                }

                if ($number >= 1000000 && $number < 1000000000) {
                    return sprintf('%d%s', floor($number / 1000000), 'M+');
                }

                if ($number >= 1000000000 && $number < 1000000000000) {
                    return sprintf('%d%s', floor($number / 1000000000), 'B+');
                }

                return sprintf('%d%s', floor($number / 1000000000000), 'T+');
            };

            return $suffixByNumber();
        }
    }

    if(!function_exists('str_clean_special_characters')){
        function str_clean_special_characters($string){
            $string = preg_replace("/(<^\pL\.\ >+)/u", "", strip_tags($string));
            $string = str_replace(array( '\'', '"', ',' , ';', '<', '>' ), '', $string);
            return $string;
        }
    }

    if(!function_exists('randomString')){
        function randomString($length){
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }
    }
}
