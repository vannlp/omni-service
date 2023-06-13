<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\Area;
use App\Category;
use App\Distributor;
use App\File;
use App\Jobs\Import\ImportUserJob;
use App\Product;
use App\Supports\Message;
use App\TM;
use App\User;
use App\V1\Models\AreaModel;
use App\V1\Models\CityModel;
use App\V1\Models\CouponModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\FileModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use App\V1\Models\DistributorModel;
use App\V1\Models\WardModel;
use Box\Spout\Reader\ReaderFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistributorImportController
{
    protected $_header;
    protected $_required;
    protected $_duplicate;
    protected $_notExisted;
    protected $_number;
    protected $_date;
    public function __construct()
    {
        $this->_header     = [
            0 => "Tỉnh Thành Phố",
            1 => "Mã TP",
            2 => "Quận Huyện",
            3 => "Mã QH",
            4 => "Phường Xã",
            5 => "Mã PX",
            6 => "Code CH",
            7 => "Tên CH",
            // 8 => "Tình trạng"
        ];
        $this->_required   = [
            1 => trim($this->_header[1]),
            3 => trim($this->_header[3]),
            5 => trim($this->_header[5]),
            6 => trim($this->_header[6]),
            7 => trim($this->_header[7]),
        ];
        $this->_duplicate  = [
        ];
        $this->_notExisted = [
        ];
        $this->_number     = [
        ];
        $this->_date       = [
        ];
    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', '300M');
        $input = $request->all();

        if (empty($input['file'])) {
            return response()->json([
                'status'  => 'error',
                'message' => Message::get("V001", 'File Import')
            ], 400);
        }

        $fileName = explode(".", $_FILES["file"]["name"]);
        $fileExt  = end($fileName);
        if (empty($fileExt) || !in_array($fileExt, ['xlsx', 'csv', 'ods'])) {
            return response()->json([
                'status'  => 'error',
                'message' => Message::get('regex', 'File Import'),
            ], 400);
        }
        $warnings = [];
        $valids   = [];
        $countRow = 0;
        $empty    = true;
        $fileName = "Import-Distributor_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";
        // print_r($fileName);die;
        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        $company_id = TM::getCurrentCompanyId();
        $store_id   = TM::getCurrentStoreId();
        try {
            $dataCheck = [
                'code' => [
                    'fields' => ['code', 'name', 'city_code', 'city_full_name', 'district_code', 'district_full_name', 'ward_code', 'ward_full_name', 'created_at', 'created_by', 'updated_at', 'updated_by', 'store_id', 'company_id'],
                    'model'  => new DistributorModel(),
                    'where'  => ' `company_id` = ' . $company_id . ' and `store_id` = ' . $store_id,
                ],
                'city' => [
                    'fields' => ['code', 'full_name', 'code', 'id'],
                    'model'  => new CityModel(),
                ],
                'district' => [
                    'fields' => ['code', 'full_name', 'code', 'id'],
                    'model'  => new DistrictModel(),
                ],
                'ward' => [
                    'fields' => ['code', 'full_name', 'code', 'id'],
                    'model'  => new WardModel(),
                ],
            ];
            foreach ($dataCheck as $key => $option) {
                $existedChk[$key] = $option['model']->fetchColumns(
                    $option['fields'],
                    $option['where'] ?? "",
                    [],
                    true,
                    true
                );
            }
            $dataCheck = null;
            $file->move($path, "$fileName.$fileExt");
            $filePath = "$path/$fileName.$fileExt";

            try {
                $reader = ReaderFactory::create($fileExt);
                $reader->open($filePath);
            } catch (\Exception $ex) {
                return response()->json(['status' => 'error', 'message' => Message::get('V002', "File Template")], 400);
            }
            $codeUsed   = [];
            $totalLines = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $row = [
                        0 => $row[0] ?? null,
                        1 => $row[1] ?? null,
                        2 => $row[2] ?? null,
                        3 => $row[3] ?? null,
                        4 => $row[4] ?? null,
                        5 => $row[5] ?? null,
                        6 => $row[6] ?? null,
                        7 => $row[7] ?? null,
                        // 8 => $row[8] ?? null,
                    ];
                    $totalLines++;
                    //                    if ($totalLines > 30001) {
                    //                        return response()->json([
                    //                            'status'  => 'error',
                    //                            'message' => Message::get("V013", Message::get("data_row"), 30000),
                    //                        ], 400);
                    //                    }

                    $empty = false;

                    if ($index === 1) {
                        if (array_diff($this->_header, $row)) {
                            $reader->close();
                            unlink($filePath);
                            return response()->json([
                                'status'  => 'error',
                                'message' => Message::get('V002', "File Template"),
                            ], 400);
                        }
                        array_push($row, "Error");
                        $errors[] = $row;
                        continue;
                    }

                    if (array_filter($row)) {
                        foreach ($this->_required as $key => $name) {
                            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                                $row[]      = "Required " . $this->_header[$key];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }
                        $err = "";

                        // Check Date
//                        foreach ($this->_date as $key => $name) {
//                            //                            if (empty($row[$key])) {
//                            //                                continue;
//                            //                            }
//
//                            if ($row[$key] instanceof \DateTime) {
//                                $row[$key] = $row[$key]->format('Y-m-d');
//                                continue;
//                            }
//                            $val = strtotime(trim($row[$key]));
//                            if ($val > 0) {
//                                $row[$key] = date('Y-m-d', $val);
//                                continue;
//                            }
//
//                            $err .= "[" . $this->_header[$key] . " Bắt buộc phải là ngày!]";
//                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }


                        // Check Existed
//                        foreach ($this->_notExisted as $key => $name) {
//                            if (empty($row[$key])) {
//                                continue;
//                            }
//
//                            $val = trim(strtoupper($row[$key]));
//                            if (!isset($existedChk[$name][$val])) {
//                                $row[]      = "Required " . $this->_header[0];
//                                $warnings[] = array_map(function ($r) {
//                                    return "\"" . $r . "\"";
//                                }, $row);
//                                continue;
//                            }
//                        }

//                        $err = "";
                        // Check Number
//                        foreach ($this->_number as $key => $name) {
//
//                            if (empty($row[$key])) {
//                                continue;
//                            }
//
//                            $val = trim($row[$key]);
//
//                            if (!is_numeric($val)) {
//                                $err .= "[" . $this->_header[$key] . " Bắt buộc phải là số!]";
//                            }
//                        }

                        // Check Duplicate Code
//                        $tmp = strtoupper($row[6]);
//                        if (in_array($tmp, $codeUsed)) {
//                            $row[]      = "Duplicate Code " . $tmp;
//                            $warnings[] = array_map(function ($r) {
//                                return "\"" . $r . "\"";
//                            }, $row);
//                            continue;
//                        }
//                        else{
//                        }

                        // Check Duplicate Code
                        // $tmp = strtoupper($row[3]);
                        // if (in_array($tmp, $phoneUsed)) {
                        //      $row[] = "Duplicate Phone" . $tmp;
                        //      $warnings[] = array_map(function ($r) {
                        //           return "\"" . $r . "\"";
                        //      }, $row);
                        //      continue;
                        // }

                        // Check Duplicated
                        $err = "";

                        foreach ($this->_duplicate as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }
                            $val = trim(strtoupper($row[$key]));
                            if (!empty($existedChk[$name][$val])) {
                                $err .= "[$val đã tồn tại, Vui lòng nhập khác]";
                            }
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        //get id product
                        $userCode = [];
                        $err      = "";
                        if (!empty($row[6])) {
                            $code = $row[6];
                            $uid  = User::model()->where([
                                'code'       => strtolower($code),
                                'company_id' => TM::getCurrentCompanyId()
                            ])->first();
                            if(empty($uid)){
                                $err .= "[$code chưa có tài khoản, Vui lòng tạo tài khoản]";
                            }
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }
                        $countRow++;
                        $valids[] = [
                                'code'               => strtolower($row[6]),
                                'name'               => $row[7],
                                'city_code'          => $row[1],
                                'city_full_name'     => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['full_name'] ?? null : null,
                                'district_code'      => $row[3],
                                'district_full_name' => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['full_name'] ?? null : null,
                                'ward_code'          => $row[5],
                                'ward_full_name'     => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['full_name'] ?? null : null,
                                'is_active'          => 1
                            ];
                    }

                }// Only process one sheet
                break;
            }


            if ($empty || $totalLines <= 1) {
                return response()->json([
                    'status'  => 'error',
                    'message' => Message::get('R015', 'File Import'),
                ], 400);
            }
            $reader->close();
            unlink($filePath);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
        try {
            $this->startImport($valids);
            $this->writeWarning($warnings, $this->_header);
            return response()->json([
                'status'  => 'success',
                'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                'warning' => !empty($warnings) ? $warnings : null,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
    }
    private function startImport($data)
    {
        if (empty($data)) {
            return 0;
        }
        DB::beginTransaction();
        //User Company
        $queryHeader  = "INSERT INTO `distributors` (" .
                        "`id`, " .
                        "`code`, " .
                        "`name`, " .
                        "`city_code`, " .
                        "`city_full_name`, " .
                        "`district_code`, " .
                        "`district_full_name`, " .
                        "`ward_code`, " .
                        "`ward_full_name`, " .
                        "`company_id`, " .
                        "`store_id`, " .
                        "`is_active`, " .
                        "`created_at`, " .
                        "`created_by`, " .
                        "`updated_at`, " .
                        "`updated_by`) VALUES ";
        $now          = date('Y-m-d H:i:s', time());
        $me           = TM::getCurrentUserId();
        $company      = TM::getCurrentCompanyId();
        $store        = TM::getCurrentStoreId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $checkDistributor = Distributor::model()->where(['code'=>$datum['code'],'city_code'=>$datum['city_code'],'district_code'=>$datum['district_code'],'ward_code'=>$datum['ward_code']])->first();
            $id_distributor = !empty($checkDistributor) ? (int)$checkDistributor->id : "null";
            $queryContent
                            .= "(" .
                               $id_distributor . "," .
                               $pdo->quote($datum['code']) . "," .
                               $pdo->quote($datum['name']) . "," .
                               $pdo->quote($datum['city_code']) . "," .
                               $pdo->quote($datum['city_full_name']) . "," .
                               $pdo->quote($datum['district_code']) . "," .
                               $pdo->quote($datum['district_full_name']) . "," .
                               $pdo->quote($datum['ward_code']) . "," .
                               $pdo->quote($datum['ward_full_name']) . "," .
                               $pdo->quote($company) . "," .
                               $pdo->quote($store) . "," .
                               1 . "," .
                               $pdo->quote($now) . "," .
                               $pdo->quote($me) . "," .
                               $pdo->quote($now) . "," .
                               $pdo->quote($me) . "), ";
        }

        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                           " ON DUPLICATE KEY UPDATE " .
                           "`code`= values(`code`), " .
                           "`name`= values(`name`), " .
                           "`city_code`= values(`city_code`), " .
                           "`city_full_name`= values(`city_full_name`), " .
                           "`district_code`= values(`district_code`), " .
                           "`district_full_name`= values(`district_full_name`), " .
                           "`ward_code`= values(`ward_code`), " .
                           "`ward_full_name`= values(`ward_full_name`), " .
                           "`company_id` = values(`company_id`), " .
                           "`store_id` = values(`store_id`), " .
                           "`is_active` = values(`is_active`), " .
                           "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }

    /**
     * @param $data
     * @param array $header
     * @return bool
     */
    private function writeWarning($data, $header = [])
    {
        if (empty($data)) {
            return false;
        }
        $dir = storage_path('Imports/Warnings');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $csv = $header ? "\"" . implode("\",\"", $header) . "\"\n" : "";
        foreach ($data as $record) {
            $csv .= implode(',', $record) . PHP_EOL;
        }

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-CUSTOMER-(warning).csv", 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return true;
    }
}