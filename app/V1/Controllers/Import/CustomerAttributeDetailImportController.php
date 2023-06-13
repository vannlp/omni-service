<?php


namespace App\V1\Controllers\Import;


use App\Supports\Message;
use App\TM;
use App\V1\Models\CustomerAttributeDetailModel;
use App\V1\Models\CustomerAttributeModel;
use App\V1\Models\UserCustomerModel;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerAttributeDetailImportController
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
            0 => "CUSTOMER_ATTRIBUTE_DETAIL_ID",
            1 => "CUSTOMER_ATTRIBUTE_ID",
            2 => "CUSTOMER_ID",
            3 => "VALUE",
            4 => "CUSTOMER_ATTRIBUTE_ENUM_ID",
            5 => "STATUS",
            6 => "CREATE_DATE",
            7 => "CREATE_USER",
            8 => "UPDATE_DATE",
            9 => "UPDATE_USER",
        ];
        $this->_required   = [
            0 => trim($this->_header[0]), //CUSTOMER_ATTRIBUTE_DETAIL_ID
            1 => trim($this->_header[1]), //CUSTOMER_ATTRIBUTE_ID
            2 => trim($this->_header[2]), //CUSTOMER_ID
        ];
        $this->_duplicate  = [
            0 => 'customer_attribute_detail_id',
        ];
        $this->_notExisted = [
            1 => 'customer_attribute_id',
            2 => 'customer_id'
        ];
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
        $fileName = "Import-CustomerAttributeDetail_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                'customer_attribute_detail_id' => [
                    'fields' => ['customer_attribute_id','id', 'customer_attribute_id'],
                    'model'  => new CustomerAttributeDetailModel(),
                ],
                'customer_attribute_id' => [
                    'fields' => ['customer_attribute_id', 'id', 'customer_attribute_id'],
                    'model'  => new CustomerAttributeModel(),
                ],
                'customer_id' => [
                    'fields' => ['customer_id', 'id', 'customer_id'],
                    'model'  => new UserCustomerModel(),
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
            $file->move($path, "$fileName.$fileExt");
            $filePath = "$path/$fileName.$fileExt";

            try {
                $reader = ReaderFactory::create($fileExt);
                $reader->open($filePath);
            } catch (\Exception $ex) {
                return response()->json(['status' => 'error', 'message' => Message::get('V002', "File Template")], 400);
            }

            $totalLines = 0;
            $idUsed  = [];

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
                        7 => $row[7] ?? "",
                        8 => $row[8] ?? "",
                        9 => $row[9] ?? "",
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
                        // Check Required
                        $err = "";

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


                        // Check NOT Existed
                        foreach ($this->_notExisted as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }

                            $val = trim(strtoupper($row[$key]));
                            if (!isset($existedChk[$name][$val])) {
                                $err .= $val . " Not Existed " . $this->_header[$key];
                            }
                        }

                        // Check Duplicated
                        foreach ($this->_duplicate as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }
                            $val = trim(strtoupper($row[$key]));
                            if (!empty($existedChk[$name][$val])) {
                                $err .= "[$name : $val existed, please input other]";
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
                            'customer_attribute_detail_id' => $row[0] ?? "",
                            'customer_attribute_id'        => $row[1] ?? "",
                            'customer_id'                  => $row[2] ?? "",
                            'value'                        => $row[3] ?? "",
                            'customer_attribute_enum_id'   => $row[4] ?? "",
                            'status'                       => $row[5] ?? "",
                            'created_at'                   => ($row[6]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[6])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[6]))) : "",
                            'created_by'                   => $row[7] ?? "",
                            'updated_at'                   => ($row[8]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[8])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[8]))) : "",
                            'updated_by'                   => $row[9] ?? "",
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
        $queryHeader  = "INSERT INTO `customer_attribute_details` (" .
            "`customer_attribute_detail_id`, " .
            "`customer_attribute_id`, " .
            "`customer_id`, " .
            "`value`, " .
            "`customer_attribute_enum_id`, " .
            "`status`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now          = date('Y-m-d H:i:s', time());
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $queryContent .=
                "(" .
                ($datum['customer_attribute_detail_id'] ? $pdo->quote($datum['customer_attribute_detail_id']) : "null") . "," .
                ($datum['customer_attribute_id'] ? $pdo->quote($datum['customer_attribute_id']) : "null") . "," .
                ($datum['customer_id'] ? $pdo->quote($datum['customer_id']) : "null") . "," .
                $pdo->quote($datum['value']) . "," .
                ($datum['customer_attribute_enum_id'] ? $pdo->quote($datum['customer_attribute_enum_id']) : "null") . "," .
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

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-CUSTOMER-ATTRIBUTE-DETAILS-(warning).csv", 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return true;
    }
}