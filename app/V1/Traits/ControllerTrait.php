<?php
/**
 * User: dai.ho
 * Date: 3/29/2019
 * Time: 04:42 PM
 */

namespace App\V1\Traits;


use App\File;
use App\Order;
use Illuminate\Support\Facades\DB;

trait ControllerTrait
{
    /**
     * @param $id
     * @param $tables
     *
     * @return bool
     * @throws \Exception
     */
    private function checkForeignTable($id, $tables)
    {
        if (empty($tables)) {
            return true;
        }

        $result = "";

        foreach ($tables as $table_key => $table) {
            $temp        = explode(".", $table_key);
            $table_name  = $temp[0];
            $foreign_key = !empty($temp[1]) ? $temp[1] : 'id';
            $data        = DB::table($table_name)->where($foreign_key, $id)->first();
            if (!empty($data)) {
                $result .= "$table; ";
            }
        }

        $result = trim($result, "; ");

        if (!empty($result)) {
            return $this->response->errorBadRequest(Message::get("R004", $result));
        }

        return true;
    }

    private function getAutoOrderCode()
    {
        $orderType = "DH";
        $y         = date("Y", time());
        $m         = date("m", time());
        $d         = date("d", time());
        $lastCode  = DB::table('orders')->select('code')->where('code', 'like', "$orderType$y$m$d%")->orderBy('id', 'desc')->first();
        $index     = "001";
        if (!empty($lastCode)) {
            $index = (int)substr($lastCode->code, -3);
            $index = str_pad(++$index, 3, "0", STR_PAD_LEFT);
        }
        return "$orderType$y$m$d$index";
    }

    private function convert_vi_to_en_to_slug($str, $options = [])
    {
        // Make sure string is in UTF-8 and strip invalid UTF-8 characters
        $str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());

        $defaults = [
            'delimiter'     => '-',
            'limit'         => null,
            'lowercase'     => true,
            'replacements'  => [],
            'transliterate' => true,
        ];

        // Merge options
        $options = array_merge($defaults, $options);

        // Lowercase
        if ($options['lowercase']) {
            $str = mb_strtolower($str, 'UTF-8');
        }

        $char_map = [
            // Latin
            'á' => 'a',
            'à' => 'a',
            'ả' => 'a',
            'ã' => 'a',
            'ạ' => 'a',
            'ă' => 'a',
            'ắ' => 'a',
            'ằ' => 'a',
            'ẳ' => 'a',
            'ẵ' => 'a',
            'ặ' => 'a',
            'â' => 'a',
            'ấ' => 'a',
            'ầ' => 'a',
            'ẩ' => 'a',
            'ẫ' => 'a',
            'ậ' => 'a',
            'đ' => 'd',
            'é' => 'e',
            'è' => 'e',
            'ẻ' => 'e',
            'ẽ' => 'e',
            'ẹ' => 'e',
            'ê' => 'e',
            'ế' => 'e',
            'ề' => 'e',
            'ể' => 'e',
            'ễ' => 'e',
            'ệ' => 'e',
            'í' => 'i',
            'ì' => 'i',
            'ỉ' => 'i',
            'ĩ' => 'i',
            'ị' => 'i',
            'ó' => 'o',
            'ò' => 'o',
            'ỏ' => 'o',
            'õ' => 'o',
            'ọ' => 'o',
            'ô' => 'o',
            'ố' => 'o',
            'ồ' => 'o',
            'ổ' => 'o',
            'ỗ' => 'o',
            'ộ' => 'o',
            'ơ' => 'o',
            'ớ' => 'o',
            'ờ' => 'o',
            'ở' => 'o',
            'ỡ' => 'o',
            'ợ' => 'o',
            'ú' => 'u',
            'ù' => 'u',
            'ủ' => 'u',
            'ũ' => 'u',
            'ụ' => 'u',
            'ư' => 'u',
            'ứ' => 'u',
            'ừ' => 'u',
            'ử' => 'u',
            'ữ' => 'u',
            'ự' => 'u',
            'ý' => 'y',
            'ỳ' => 'y',
            'ỷ' => 'y',
            'ỹ' => 'y',
            'ỵ' => 'y',
        ];

        // Make custom replacements
        $str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);

        // Transliterate characters to ASCII
        if ($options['transliterate']) {
            $str = str_replace(array_keys($char_map), $char_map, $str);
        }

        // Replace non-alphanumeric characters with our delimiter
        $str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);

        // Remove duplicate delimiters
        $str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);

        // Truncate slug to max. characters
        $str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');

        // Remove delimiter from ends
        $str = trim($str, $options['delimiter']);

        return $str;
    }

    private function stringToImage($ids)
    {
        if (empty($ids)) {
            return [];
        }
        $result = [];
        $images = File::model()->whereIn('id', explode(",", $ids))->get();
        foreach ($images as $key => $image) {
            $result[$key]['id']  = $image->id;
            $result[$key]['url'] = env('GET_FILE_URL') . $image->code;
        }
        return $result;
    }
}