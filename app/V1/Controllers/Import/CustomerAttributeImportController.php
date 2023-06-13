<?php


namespace App\V1\Controllers\Import;


use App\File;
use App\Supports\Message;
use App\TM;
use App\V1\Models\CustomerAttributeModel;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAttributeImportController
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
            0  => "CUSTOMER_ATTRIBUTE_ID",
            1  => "CODE",
            2  => "NAME",
            3  => "DESCRIPTION",
            4  => "TYPE",
            5  => "DATA_LENGTH",
            6  => "MIN_VALUE",
            7  => "MAX_VALUE",
            8  => "IS_ENUMERATION",
            9  => "IS_SEARCH",
            10 => "MANDATORY",
            11 => "DISPLAY_ORDER",
            12 => "STATUS",
            13 => "CREATE_DATE",
            14 => "CREATE_USER",
            15 => "UPDATE_DATE",
            16 => "UPDATE_USER",


        ];
        $this->_required   = [
            0 => trim($this->_header[1]), //CUSTOMER_ATTRIBUTE_ID
            1 => trim($this->_header[1]), //CODE
            2 => trim($this->_header[2]), //NAME
            4 => trim($this->_header[4]), //TYPE
        ];
        $this->_duplicate  = [
            0 => 'customer_attribute_id',
            1 => 'code',
        ];
        $this->_notExisted = [];
        $this->_number     = [];
        $this->_date       = [];

    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 300);
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
        $fileName = "Import-CustomerAttribute_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
//        $company_id = TM::getCurrentCompanyId();
//        $company_code = TM::getCurrentCompanyCode();
//        $store_id = TM::getCurrentStoreId();
        // print_r($companyy);die;
        try {
            $dataCheck = [
                'customer_attribute_id' => [
                    'fields' => ['customer_attribute_id', 'id', 'name', 'customer_attribute_id'],
                    'model'  => new CustomerAttributeModel(),
                ],
                'code'                  => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new CustomerAttributeModel(),
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
            $idUsed     = [];
            $totalLines = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $row = [
                        0  => $row[0] ?? "",
                        1  => $row[1] ?? "",
                        2  => $row[2] ?? "",
                        3  => $row[3] ?? "",
                        4  => $row[4] ?? "",
                        5  => !empty($row[5]) ? $row[5] : null,
                        6  => !empty($row[6]) ? $row[6] : null,
                        7  => !empty($row[7]) ? $row[7] : null,
                        8  => $row[8] ?? "",
                        9  => $row[9] ?? "",
                        10 => $row[10] ?? "",
                        11 => $row[11] ?? "",
                        12 => $row[12] ?? "",
                        13 => $row[13] ?? "",
                        14 => $row[14] ?? "",
                        15 => $row[15] ?? "",
                        16 => $row[16] ?? "",
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
                        $err = "";
                        // Check Required
                        foreach ($this->_required as $key => $name) {
                            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                                $err .= "Required " . $this->_header[$key];
                                continue;
                            }
                        }
                        // Check Duplicate ID
                        if (!empty($row[0])) {
                            $tmp = strtoupper($row[0]);
                            if (in_array($tmp, $idUsed)) {
                                $err .= "Duplicate ID " . $tmp;
                            } else array_push($idUsed, $tmp);
                        }

                        // Check Duplicate Code
                        if (!empty($row[1])) {
                            $tmp = strtoupper($row[1]);
                            if (in_array($tmp, $codeUsed)) {
                                $err .= "Duplicate Code " . $tmp;
                            } else array_push($codeUsed, $tmp);
                        }


                        // Check Duplicated
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
                        // print_r( unlink($filePath));die;
                        $countRow++;
                        $valids[] = [
                            'customer_attribute_id' => $row[0],
                            'code'                  => $row[1],
                            'name'                  => $row[2],
                            'description'           => $row[3],
                            'type'                  => $row[4],
                            'data_length'           => $row[5] ?? null,
                            'min_value'             => $row[6] ?? null,
                            'max_value'             => $row[7] ?? null,
                            'is_enumeration'        => $row[8],
                            'is_search'             => $row[9],
                            'mandatory'             => $row[10],
                            'display_order'         => $row[11],
                            'status'                => $row[12],
                            'created_at'            => $row[13] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[13])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[13]))) : "",
                            'created_by'            => $row[14],
                            'updated_at'            => $row[15] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[15])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[15]))) : "",
                            'updated_by'            => $row[16],
                        ];
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
        $queryHeader  = "INSERT INTO `customer_attributes` (" .
            "`customer_attribute_id`, " .
            "`code`, " .
            "`name`, " .
            "`description`, " .
            "`type`, " .
            "`data_length`, " .
            "`min_value`, " .
            "`max_value`, " .
            "`is_enumeration`, " .
            "`is_search`, " .
            "`mandatory`, " .
            "`display_order`, " .
            "`status`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now          = date('Y-m-d H:i:s', time());
        $me           = TM::getCurrentUserId();
        $store        = TM::getCurrentStoreId();
        $company      = TM::getCurrentCompanyId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $queryContent .=
                "(" .
                $pdo->quote($datum['customer_attribute_id']) . "," .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($datum['description']) . "," .
                $pdo->quote($datum['type']) . "," .
                ($datum['data_length'] ? $pdo->quote($datum['data_length']) : "null") . "," .
                ($datum['min_value'] ? $pdo->quote($datum['min_value']) : "null") . "," .
                ($datum['max_value'] ? $pdo->quote($datum['max_value']) : "null") . "," .
                $pdo->quote($datum['is_enumeration']) . "," .
                $pdo->quote($datum['is_search']) . "," .
                $pdo->quote($datum['mandatory']) . "," .
                ($datum['display_order'] ? $pdo->quote($datum['display_order']) : "null") . "," .
                $pdo->quote($datum['status']) . "," .
                ($datum['created_at'] ? $pdo->quote($datum['created_at']) : "null") . "," .
                ($datum['created_by'] ? $pdo->quote($datum['created_by']) : "null") . "," .
                ($datum['updated_at'] ? $pdo->quote($datum['updated_at']) : "null") . "," .
                ($datum['updated_by'] ? $pdo->quote($datum['updated_by']) : "null") . "),";
        }

        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", "));
//             print_r($queryUpdate);die;
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