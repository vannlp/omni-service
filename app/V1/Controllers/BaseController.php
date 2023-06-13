<?php

/**
 * User: daihs
 * Date: 07/09/2018
 * Time: 14:18
 */

namespace App\V1\Controllers;

use App\Supports\Message;
use GuzzleHttp\Client;
use Laravel\Lumen\Routing\Controller;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BaseController extends Controller
{
    use Helpers;

    public function ExcelExport($fileName, $dir, $data)
    {
        if (empty($data[0])) {
            throw new \Exception(Message::get("V002", "Export Data"));
        }

        $chars = config("constants.EXCEL.CHAR");
        $totalRow = count($data[0]);
        if (empty($chars[$totalRow])) {
            throw new \Exception(Message::get("V002", "Excel Alphabet Title"));
        }

        $maxChar = $chars[$totalRow];

        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
            chmod($dir, 0777);
        }

        \Excel::create($fileName, function ($writer) use ($data, $maxChar) {

            $writer->sheet('Report', function ($sheet) use ($data, $maxChar) {
                $sheet->setAutoSize(true);
                $sheet->setHeight(1, 30);

                $countData = count($data);
                // Fill suggest Data
                $sheet->fromArray($data, null, 'A1', true, false);

                // Wrap Text
                $sheet->getStyle("{$maxChar}1:{$maxChar}$countData")->getAlignment()->setWrapText(true);

                $sheet->setBorder("A1:$maxChar$countData", 'thin');
                $sheet->cell("A1:{$maxChar}1", function ($cell) {
                    $cell->setAlignment('center');
                    $cell->setFontWeight();
                    $cell->setBackground("#3399ff");
                    $cell->setFontColor("#ffffff");
                });
                $sheet->cell("A1:$maxChar$countData", function ($cell) {
                    $cell->setValignment('center');
                });

                $columsAlign = [
                    "A1:C$countData"                   => "center",
                    "D1:J$countData"                   => "center",
                    "L1:M$countData"                   => "center",
                    "{$maxChar}1:{$maxChar}$countData" => "center",
                ];

                foreach ($columsAlign as $cols => $align) {
                    $sheet->cell($cols, function ($cell) use ($align) {
                        $cell->setAlignment($align);
                    });
                }
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }

    function convert_number_to_words_old($number)
    {
        $hyphen      = ' ';
        $conjunction = '  ';
        $separator   = ' ';
        $negative    = 'âm ';
        $decimal     = ' phẩy ';
        $dictionary  = array(
            0                   => 'không',
            1                   => 'một',
            2                   => 'hai',
            3                   => 'ba',
            4                   => 'bốn',
            5                   => 'năm',
            6                   => 'sáu',
            7                   => 'bảy',
            8                   => 'tám',
            9                   => 'chín',
            10                  => 'mười',
            11                  => 'mười một',
            12                  => 'mười hai',
            13                  => 'mười ba',
            14                  => 'mười bốn',
            15                  => 'mười năm',
            16                  => 'mười sáu',
            17                  => 'mười bảy',
            18                  => 'mười tám',
            19                  => 'mười chín',
            20                  => 'hai mươi',
            30                  => 'ba mươi',
            40                  => 'bốn mươi',
            50                  => 'năm mươi',
            60                  => 'sáu mươi',
            70                  => 'bảy mươi',
            80                  => 'tám mươi',
            90                  => 'chín mươi',
            100                 => 'trăm',
            1000                => 'nghìn',
            1000000             => 'triệu',
            1000000000          => 'tỷ',
            1000000000000       => 'nghìn tỷ',
            1000000000000000    => 'nghìn triệu triệu',
            1000000000000000000 => 'tỷ tỷ'
        );
        if (!is_numeric($number)) {
            return false;
        }
        if (($number >= 0 && (int) $number < 0) || (int) $number < 0 - PHP_INT_MAX) {
            // overflow
            trigger_error(
                'convert_number_to_words only accepts numbers between -' . PHP_INT_MAX . ' and ' . PHP_INT_MAX,
                E_USER_WARNING
            );
            return false;
        }
        if ($number < 0) {
            return $negative . $this->convert_number_to_words(abs($number));
        }
        $string = $fraction = null;
        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }
        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens   = ((int) ($number / 10)) * 10;
                $units  = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= $hyphen . $dictionary[$units];
                }
                break;
            case $number < 1000:
                $hundreds  = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= $conjunction . $this->convert_number_to_words($remainder);
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int) ($number / $baseUnit);
                $remainder = $number % $baseUnit;
                $string = $this->convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= $remainder < 100 ? $conjunction : $separator;
                    $string .= $this->convert_number_to_words($remainder);
                }
                break;
        }
        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = array();
            foreach (str_split((string) $fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }
        return  $string;
    }

    function convert_number_to_words($number)
    {

        $hyphen = ' ';
        $conjunction = ' ';
        $separator = ' ';
        $negative = 'âm ';
        $decimal = ' phẩy ';
        $one = 'mốt';
        $ten = 'lẻ';
        $dictionary = [
            0                   => 'Không',
            1                   => 'Một',
            2                   => 'Hai',
            3                   => 'Ba',
            4                   => 'Bốn',
            5                   => 'Năm',
            6                   => 'Sáu',
            7                   => 'Bảy',
            8                   => 'Tám',
            9                   => 'Chín',
            10                  => 'Mười',
            11                  => 'Mười một',
            12                  => 'Mười hai',
            13                  => 'Mười ba',
            14                  => 'Mười bốn',
            15                  => 'Mười lăm',
            16                  => 'Mười sáu',
            17                  => 'Mười bảy',
            18                  => 'Mười tám',
            19                  => 'Mười chín',
            20                  => 'Hai mươi',
            30                  => 'Ba mươi',
            40                  => 'Bốn mươi',
            50                  => 'Năm mươi',
            60                  => 'Sáu mươi',
            70                  => 'Bảy mươi',
            80                  => 'Tám mươi',
            90                  => 'Chín mươi',
            100                 => 'trăm',
            1000                => 'ngàn',
            1000000             => 'triệu',
            1000000000          => 'tỷ',
            1000000000000       => 'nghìn tỷ',
            1000000000000000    => 'ngàn triệu triệu',
            1000000000000000000 => 'tỷ tỷ',
        ];

        if (!is_numeric($number)) {
            return false;
        }
        if ($number < 0) {
            return $negative . $this->convert_number_to_words(abs($number));
        }

        $string = $fraction = null;

        if (strpos($number, '.') !== false) {
            list($number, $fraction) = explode('.', $number);
        }

        switch (true) {
            case $number < 21:
                $string = $dictionary[$number];
                break;
            case $number < 100:
                $tens = ((int)($number / 10)) * 10;
                $units = $number % 10;
                $string = $dictionary[$tens];
                if ($units) {
                    $string .= strtolower($hyphen . ($units == 1 ? $one : $dictionary[$units]));
                }
                break;
            case $number < 1000:
                $hundreds = $number / 100;
                $remainder = $number % 100;
                $string = $dictionary[$hundreds] . ' ' . $dictionary[100];
                if ($remainder) {
                    $string .= strtolower($conjunction . ($remainder < 10 ? $ten . $hyphen : null) . $this->convert_number_to_words($remainder));
                }
                break;
            default:
                $baseUnit = pow(1000, floor(log($number, 1000)));
                $numBaseUnits = (int)($number / $baseUnit);
                $remainder = $number - ($numBaseUnits * $baseUnit);
                $string = $this->convert_number_to_words($numBaseUnits) . ' ' . $dictionary[$baseUnit];
                if ($remainder) {
                    $string .= strtolower($remainder < 100 ? $conjunction : $separator);
                    $string .= strtolower($this->convert_number_to_words($remainder));
                }
                break;
        }

        if (null !== $fraction && is_numeric($fraction)) {
            $string .= $decimal;
            $words = [];
            foreach (str_split((string)$fraction) as $number) {
                $words[] = $dictionary[$number];
            }
            $string .= implode(' ', $words);
        }

        return $string;
    }
    /**
     * @param null $msg
     * @param int $code
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseError($msg = null, $code = 400)
    {
        $msg = $msg ? $msg : Message::get("V1001");
        return response()->json(['status' => 'error', 'error' => ['errors' => ["msg" => $msg]]], $code);
    }


    /**
     * @param array $data
     * @return \Illuminate\Http\JsonResponse
     */
    protected function responseData(array $data = [])
    {
        return response()->json(['status' => 'success', 'data' => $data]);
    }

    public function getDatesBetween($inputFrom, $inputTo, $frequency = "DAILY")
    {
        $start    = new \DateTime($inputFrom);
        $interval = new \DateInterval('P1D');
        $end      = new \DateTime(date('d-m-Y', strtotime("+1 day", strtotime($inputTo))));

        $period = new \DatePeriod($start, $interval, $end);

        $dates = array_map(function ($d) {
            return $d->format("d-m-Y");
        }, iterator_to_array($period));

        switch ($frequency) {
            case "WEEKLY":
                $temp = [];
                foreach ($dates as $date) {
                    $week_num = date("W", strtotime($date));
                    $date_tmp = explode('-', $date);

                    if ($week_num >= 52 && (int)$date_tmp[1] == 1) {
                        $curWeek = ($date_tmp[0] - 1) . "-W$week_num";
                    } else {
                        if ($week_num == 1 && (int)$date_tmp[1] == 12) {
                            $curWeek = ($date_tmp[0] + 1) . "-W$week_num";
                        } else {
                            $curWeek = date('Y', strtotime($date)) . "-W$week_num";
                        }
                    }

                    if (empty($temp[$curWeek])) {
                        $temp[$curWeek] = $curWeek;
                    }
                }
                $dates = array_values($temp);
                break;
            case "MONTHLY":
                $dates      = null;
                $month_from = date('Y-m', strtotime($inputFrom));
                $month_to   = date('Y-m', strtotime($inputTo));
                $dates[]    = $month_from;
                if (strtotime($month_to . "-01") > strtotime($month_from . "-01")) {
                    $temp = strtotime('+1 month', strtotime($inputFrom));
                    while (date('Y-m', $temp) != $month_to) {
                        $dates[]   = date('Y-m', $temp);
                        $inputFrom = $temp;
                        $temp      = strtotime('+1 month', $inputFrom);
                    }
                    $dates[] = date('Y-m', $temp);
                }
                break;
            case "QUARTERLY":
                $froms        = $this->kpiUser->getQuarterMonths($inputFrom);
                $fromTemp     = explode("-", $froms[3]);
                $quarter_from = $fromTemp[1] / 3;

                $tos        = $this->kpiUser->getQuarterMonths($inputTo);
                $toTemp     = explode("-", $tos[3]);
                $quarter_to = $toTemp[1] / 3;

                $dates                                   = null;
                $dates[$fromTemp[0] . "-Q$quarter_from"] = $fromTemp[0] . "-Q$quarter_from";

                if (strtotime($tos[3] . "-01") > strtotime($froms[3] . "-01")) {
                    $temp         = strtotime('+1 month', strtotime($inputFrom));
                    $tempQuarters = $this->kpiUser->getQuarterMonths(date('Y-m-d', $temp));
                    $tempQuarters = explode("-", $tempQuarters[3]);
                    while ($tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3) != $toTemp[0] . "-Q$quarter_to") {
                        $dates[$tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3)] = $tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3);
                        $inputFrom                                               = implode("-", $tempQuarters) . "-01";
                        $temp                                                    = strtotime('+1 month', strtotime($inputFrom));
                        $tempQuarters                                            = $this->kpiUser->getQuarterMonths(date('Y-m-d', $temp));
                        $tempQuarters                                            = explode("-", $tempQuarters[3]);
                    }
                    $dates[$tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3)] = $tempQuarters[0] . "-Q" . ($tempQuarters[1] / 3);
                }
                break;
            case "YEARLY":
                $year_from = date('Y', strtotime($inputFrom));
                $year_to   = date('Y', strtotime($inputTo));

                $dates = null;

                while ($year_to > $year_from) {
                    $dates[] = $year_from;
                    $year_from++;
                }
                $dates[] = $year_from;
                break;
        }
        return $dates;
    }

    public function sort($input, $model, &$query)
    {
        $validColumn = DB::getSchemaBuilder()->getColumnListing(app($model)->getTable());
        $validConditions = ['asc', 'desc'];
        foreach ($input['sort'] as $key => $value) {
            if (!$value) {
                $value = 'asc';
            }
            if (!in_array($value, $validConditions)) {
                continue;
            }
            if (!in_array($key, $validColumn)) {
                continue;
            }
            $query->orderBy($key, $value);
        }
        return $query;
    }

    public function setCache($key = null, $data = null, $lifeTime = null)
    {
        if (!$key || !$data) {
            return;
        }
        $lifeTime = $lifeTime ?: 180;

        return Cache::set($key, $data, $lifeTime);
    }

    public function getCache($key = null)
    {
        if (!$key) {
            return;
        }
        return Cache::get($key);
    }

    public function delCache($key = null)
    {
        if (!$key) {
            return;
        }
        return Cache::forget($key);
    }

    public function chkCache($key = null)
    {
        if (!$key) {
            return;
        }
        return Cache::has($key);
    }

    public function clearAllCache()
    {
        return Cache::flush();
    }

    public static function sendMessage($message) {
        try {
            $timeout = 1;
            $token = env('TELEGRAM_BOT_TOKEN');
            $chat_id = env('TELEGRAM_CHAT_ID');
            $url = "https://api.telegram.org/bot" . $token . "/sendMessage";
            $client = new Client(['timeout' => $timeout]);
            $client->request('POST', $url, [
                'form_params' => [
                    'chat_id' => $chat_id,
                    'text' => $message,
                ]
            ]);
        } catch (\Exception $e) {
        }
    }
}
