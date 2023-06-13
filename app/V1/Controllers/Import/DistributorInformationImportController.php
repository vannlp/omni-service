<?php


namespace App\V1\Controllers\Import;


use App\Company;
use App\Role;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\User;
use App\UserGroup;
use App\V1\Models\CategoryModel;
use App\V1\Models\CityModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use App\V1\Models\WardModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistributorInformationImportController
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
            0 => "Mã NPP(*)",
            1 => "Tình trạng DVVC",
            2 => "Loại HUB",
            3 => "KL Tối Đa (Kg)",
            4 => "Tổng đơn nhận/ngày",
            5 => "Tiền tối thiểu/đơn",
            6 => "Tiền tối đa/đơn",
        ];
        $this->_required   = [
            0 => trim($this->_header[0]),
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

        $errors   = [];
        $warnings = [];
        $valids   = [];
        $countRow = 0;
        $error    = false;
        $empty    = true;
        $fileName = "Import-Customer_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        $existedChk = [];
        try {
            $dataCheck = [
//                'code'         => [
//                    'fields' => ['code', 'id', 'name', 'code', 'code'],
//                    'model'  => new UserModel(),
//                ],
//                'phone'        => [
//                    'fields' => ['phone', 'id', 'name', 'code', 'phone'],
//                    'model'  => new UserModel(),
//                ],
//                'groups'       => [
//                    'fields' => ['code', 'id', 'name', 'code'],
//                    'model'  => new UserGroupModel(),
//                ],
//                'distributors' => [
//                    'fields' => ['code', 'name', 'code', 'id'],
//                    'model'  => new UserModel(),
//                    'whereIn'  => 'group_code='.$a,
//                ],
//                'distributor_center_code' => [
//                    'fields' => ['code', 'name', 'code', 'id'],
//                    'model'  => new UserModel(),
//                    'where'  => ' group_code="TTPP"',
//                ],
//'city' => [
//    'fields' => ['code', 'name', 'code', 'id'],
//    'model'  => new CityModel(),
//],
//'district' => [
//    'fields' => ['code', 'name', 'code', 'id'],
//    'model'  => new DistrictModel(),
//],
//'ward' => [
//    'fields' => ['code', 'name', 'code', 'id'],
//    'model'  => new WardModel(),
//],
            ];
            foreach ($dataCheck as $key => $option) {
                $existedChk[$key] = $option['model']->fetchColumns(
                    $option['fields'],
                    $option['where'] ?? "",
                    [],
                    true,
                    true
                );
                print_r(json_encode($existedChk));
                die;
            }
            $file->move($path, "$fileName.$fileExt");
            $filePath = "$path/$fileName.$fileExt";

            try {
                $reader = ReaderFactory::create($fileExt);
                $reader->open($filePath);
            } catch (\Exception $ex) {
                return response()->json(['status' => 'error', 'message' => Message::get('V002', "File Template")], 400);
            }
            $codeUsed   = [];
            $phoneUsed  = [];
            $totalLines = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $row = [
                        0 => $row[0] ?? "",
                        1 => $row[1] ?? "",
                        2 => $row[2] ?? "",
                        3 => $row[3] ?? "",
                        4 => $row[4] ?? "",
                        5 => $row[5] ?? "",
                        6 => $row[6] ?? "",
                    ];

                    $totalLines++;
                    if ($totalLines > 30001) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => Message::get("V013", Message::get("data_row"), 30000),
                        ], 400);
                    }
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
                        $row[1] = str_replace(" ", "", $row[1]);

                        // Check Valid
                        if (empty($row[0])) {
                            // Except Temporarily
                            $row[]      = "Empty " . $this->_header[0];
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }
//                        if (empty($existedChk['distributors'][trim(strtoupper($row[5]))])) {
//                            // Except Temporarily
//                            continue;
//                        }
                        //Check Group
                        if (!empty($row[0])) {
                            $check = User::model()->where('code', $row[0])->first();
                            if (empty($check) || !(in_array($check->group_code, ['HUB', 'TTPP', 'DISTRIBUTOR']))) {
                                ;
                                $row[]      = $row[0] . " Không phải là nhà phân phối";
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }
                        // Check Duplicate Code
//                        if (!empty($row[1])) {
//                            $tmp = strtoupper($row[1]);
//                            if (in_array($tmp, $codeUsed)) {
//                                $row[] = "Duplicate " . $this->_header[0];
//                                $warnings[] = array_map(function ($r) {
//                                    return "\"" . $r . "\"";
//                                }, $row);
//                                continue;
//                            }
//                        }

                        // Check Duplicate Code
                        if (!empty($row[0])) {
                            $tmp = strtoupper($row[0]);
                            if (in_array($tmp, $phoneUsed)) {
                                $row[]      = "Duplicate " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }

                        // Check Duplicated
                        $err = "";
                        foreach ($this->_duplicate as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }
                            $val = trim(strtoupper($row[$key]));
                            if (!empty($existedChk[$name][$val])) {
                                $err .= "[$val existed, please input other]";
                            }
                        }
                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

//                        $tmp = strtoupper($row[1]);
//                        if (strlen($tmp) > 12 || !is_numeric($tmp)) {
//                            $row[] = "[" . Message::get("V002", $this->_header[1]) . "]";
//                            $warnings[] = array_map(function ($r) {
//                                return "\"" . $r . "\"";
//                            }, $row);
//                            continue;
//                        }

                        $errorDetail = $this->checkValidRow($row, $codeUsed, $phoneUsed, $existedChk);
                        $countRow++;
                        $valids[] = [
                            'id'            => $check->id ?? null,
                            'code'          => $row[0],
                            'is_transport'  => !empty($row[1]) ? 1 : 0,
                            'type_hub'      => !empty($row[2]) ?$row[2] : null ,
                            'max_vol'       => $row[3],
                            'max_qty_order' => $row[4],
                            'price_start'   => $row[5],
                            'price_end'     => $row[6],
                            //                            'group_code'       => USER_ROLE_GUEST,
                            //                            //                            'group_id'         => !empty($row[11]) ? $existedChk['groups'][trim(strtoupper($row[11]))]['id'] ?? null : null,
                            //                            //                            'group_name'       => !empty($row[11]) ? $existedChk['groups'][trim(strtoupper($row[11]))]['name'] ?? null : null,
                            //                            //                            'distributor_code' => $row[20],
                            //                            //                            'distributor_id'   => !empty($row[20]) ? $existedChk['distributors'][trim(strtoupper($row[20]))]['id'] ?? null : null,
                            //                            //                            'distributor_name' => !empty($row[20]) ? $existedChk['distributors'][trim(strtoupper($row[20]))]['name'] ?? null : null,
                            //                            'address'          => $row[16],
                            //                            //                            'distributor_center_id'     => !empty($row[20]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[20]))]['id'] ?? null : null,
                            //                            //                            'distributor_center_code'   => !empty($row[20]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[20]))]['code'] ?? null : null,
                            //                            //                            'distributor_center_name'   => !empty($row[20]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[20]))]['name'] ?? null : null,
                            //                            'city'              =>  !empty($row[15]) ? $existedChk['city'][trim(strtoupper($row[15]))]['code'] ?? null : null,
                            //                            'district'          =>  !empty($row[16]) ? $existedChk['district'][trim(strtoupper($row[16]))]['code'] ?? null : null,
                            //                            'ward'              =>  !empty($row[17]) ? $existedChk['ward'][trim(strtoupper($row[17]))]['code'] ?? null : null,
                        ];
                        // Put error
                        if ($errorDetail) {
                            $error = true;
                        }

                        $errorDetail = trim($errorDetail);

                        $row[] = $errorDetail;

                        $errors[] = array_map(function ($r) {
                            return "\"" . $r . "\"";
                        }, $row);

//                        $codeUsed[] = $row[0];
                        $phoneUsed[] = $row[0];
                    }
                }
                // Only process one sheet
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

        if ($error) {
            return response()->json([
                'status'  => 'error',
                'message' => $errors,
            ], 201);
        } else {
            try {
                $this->startImport($valids);
                header('Content-Type: application/json');
                return response()->json([
                    'status'  => 'success',
                    'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                    'warning' => !empty($warnings) ? $warnings : null,
                ], 200);
            } catch (\Exception $ex) {
                return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
            }
        }
        die();
    }

    private function checkValidRow(&$row, &$codeUsed, &$phoneUsed, &$existedChk)
    {
        $errorDetail = "";

        // Check Required
        foreach ($this->_required as $key => $name) {
            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                $errorDetail .= "[" . Message::get("V001", $this->_header[$key]) . "]";
            }
        }

        // Check Duplicated
//        foreach ($this->_duplicate as $key => $name) {
//            if (empty($row[$key])) {
//                continue;
//            }
//            $val = trim(strtoupper($row[$key]));
//            if (!empty($existedChk[$name][$val])) {
//                $errorDetail .= "[" . Message::get("V007", $this->_header[$key]) . "]";
//            }
//        }

        // Check Existed
        foreach ($this->_notExisted as $key => $name) {
            if (empty($row[$key])) {
                continue;
            }
//            dd($existedChk[$name]);
            $val = trim(strtoupper($row[$key]));
            if (!isset($existedChk[$name][$val])) {
                $errorDetail .= "[" . Message::get("V003", $this->_header[$key]) . "]";
            }
        }

        // Check Number
        foreach ($this->_number as $key => $name) {

            if (empty($row[$key])) {
                continue;
            }

            $val = trim($row[$key]);

            if (!is_numeric($val)) {
                $errorDetail .= "[" . $this->_header[$key] . " must be a number!]";
            }
        }

        // Check Date
        foreach ($this->_date as $key => $name) {
            if ($row[$key] instanceof \DateTime) {
                $row[$key] = $row[$key]->format('Y-m-d');
                continue;
            }
            $val = strtotime(trim($row[$key]));
            if ($val > 0) {
                $row[$key] = date('Y-m-d', $val);
                continue;
            }
            $errorDetail .= "[" . $this->_header[$key] . " must be a date!]";
        }

        // Check Duplicate Code
//        $tmp = strtoupper($row[0]);
//        if (in_array($tmp, $codeUsed)) {
//            $errorDetail .= "[" . Message::get("V028", $this->_header[0]) . "]";
//        }

        // Check Duplicate Code
//        $tmp = strtoupper($row[3]);
//        if (in_array($tmp, $phoneUsed)) {
//            $errorDetail .= "[" . Message::get("V028", $this->_header[3]) . "]";
//        }

        return $errorDetail;
    }

    private function startImport($data)
    {
        if (empty($data)) {
            return 0;
        }
        DB::beginTransaction();
        $queryHeader  = "INSERT INTO `users` (" .
                        "`id`, " .
                        "`type_delivery_hub`, " .
                        "`qty_max_day`, " .
                        "`min_amt`, " .
                        "`max_amt`, " .
                        "`maximum_volume`, " .
                        "`is_transport`, " .
                        "`created_at`, " .
                        "`updated_at`, " .
                        "`updated_by`) VALUES ";
        $now          = date('Y-m-d H:i:s', time());
        $me           = TM::getCurrentUserId();
        $store        = TM::getCurrentStoreId();
        $company      = TM::getCurrentCompanyId();
        $userImported = [];
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();

        foreach ($data as $datum) {
            $typeHub = !empty($datum['type_hub']) ?  $pdo->quote($datum['type_hub']) : 'null';
            $maxQtyOrder = !empty($datum['max_qty_order']) ?  $pdo->quote($datum['max_qty_order']) : 'null';
            $priceStart = !empty($datum['price_start']) ?  $pdo->quote($datum['price_start']) : 'null';
            $priceEnd = !empty($datum['price_end']) ?  $pdo->quote($datum['price_end']) : 'null';
            $maxVol = !empty($datum['max_vol']) ?  $pdo->quote($datum['max_vol']) : 'null';
            $queryContent
                .= "(" .
                   $pdo->quote($datum['id']) . "," .
                   $typeHub . "," .
                   $maxQtyOrder . "," .
                   $priceStart . "," .
                   $priceEnd .",".
                   $maxVol .",".
                   $pdo->quote($datum['is_transport']) . "," .
                   $pdo->quote($now) . "," .
                   $pdo->quote($now) . "," .
                   $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                           " ON DUPLICATE KEY UPDATE " .
                           "`type_delivery_hub`= values(`type_delivery_hub`), " .
                           "`qty_max_day`= values(`qty_max_day`), " .
                           "`min_amt` = values(`min_amt`), " .
                           "`max_amt` = values(`max_amt`), " .
                           "`maximum_volume` = values(`maximum_volume`), " .
                           "`is_transport` = values(`is_transport`), " .
                           "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        DB::commit();
    }
}