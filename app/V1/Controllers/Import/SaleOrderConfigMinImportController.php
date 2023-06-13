<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\Category;
use App\Product;
use App\Supports\Message;
use App\TM;
use App\V1\Models\CouponModel;
use App\V1\Models\SaleOrderConfigMinModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SaleOrderConfigMinImportController
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
            0 => "SALE_ORDER_CONFIG_MIN_ID",
            1 => "SHOP_ID",
            2 => "FROM_DATE",
            3 => "TO_DATE",
            4 => "PRODUCT_ID",
            5 => "UNIT",
            6 => "QUANTITY",
            7 => "STATUS",
            8 => "CREATE_DATE",
            9 => "CREATE_USER",
            10 => "UPDATE_DATE",
            11 => "UPDATE_USER"
        ];
        $this->_required   = [
            1 => trim($this->_header[1]),
            4 => trim($this->_header[4]),
            5 => trim($this->_header[5]),
            6 => trim($this->_header[6]),
        ];
        $this->_duplicate  = [
        ];
        $this->_notExisted = [];
        $this->_number     = [
            6 => "QUANTITY",
        ];
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
        $fileName = "Import-Coupon_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";
        // print_r($fileName);die;
        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        $company_id = TM::getCurrentCompanyId();
        $store_id = TM::getCurrentStoreId();
        try {
            $dataCheck = [
                'code'         => [
                    'fields' => ['shop_id', 'product_id'],
                    'model'  => new SaleOrderConfigMinModel(),
                    // 'where' => ' `company_id` = ' . $company_id . ' and `store_id` = ' . $store_id,
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
                        8 => $row[8] ?? null,
                        9 => $row[9] ?? null,
                        10 => $row[10] ?? null,
                        11 => $row[11] ?? null,
                    ];

                    $totalLines++;
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
                                $row[]      = "Required " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }
                        $err = "";

                        // Check Date
                        foreach ($this->_date as $key => $name) {
                            //                            if (empty($row[$key])) {
                            //                                continue;
                            //                            }

                            if ($row[$key] instanceof \DateTime) {
                                $row[$key] = $row[$key]->format('Y-m-d');
                                continue;
                            }
                            $val = strtotime(trim($row[$key]));
                            if ($val > 0) {
                                $row[$key] = date('Y-m-d', $val);
                                continue;
                            }

                            $err .= "[" . $this->_header[$key] . " Bắt buộc phải là ngày!]";
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }



                        // Check Existed
                        foreach ($this->_notExisted as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }

                            $val = trim(strtoupper($row[$key]));
                            if (!isset($existedChk[$name][$val])) {
                                $row[]      = "Required " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }

                        $err = "";
                        // Check Number
                        foreach ($this->_number as $key => $name) {

                            if (empty($row[$key])) {
                                continue;
                            }

                            $val = trim($row[$key]);

                            if (!is_numeric($val)) {
                                $err .= "[" . $this->_header[$key] . " Bắt buộc phải là số!]";
                            }
                        }

                        // Check Duplicate Code
                        // $tmp = strtoupper($row[1]);
                        // if (in_array($tmp, $codeUsed)) {
                        //     $row[]      = "Duplicate Code " . $tmp;
                        //     $warnings[] = array_map(function ($r) {
                        //         return "\"" . $r . "\"";
                        //     }, $row);
                        //     continue;
                        // }

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

                        $countRow++;
                        $valids[] = [
                            'shop_id'                   => $row[1],
                            'from_date'                 => $row[2],
                            'to_date'                   => $row[3],
                            'product_id'                => $row[4],
                            'unit_id'                   => $row[5],
                            'quantity'                  => $row[6],
                            'status'                    => $row[7],
                            'created_at'                => $row[8],
                            'created_by'                => $row[9],
                            'updated_at'                => $row[10],
                            'updated_by'                => $row[11]
                        ];
                    }

                }
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
        $queryHeader  = "INSERT INTO `sale_order_config_mins` (" .
            "`shop_id`, " .
            "`from_date`, " .
            "`to_date`, " .
            "`product_id`, " .
            "`unit_id`, " .
            "`quantity`, " .
            "`status`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $fromdate    = date('Y-m-d H:i:s', strtotime($datum['from_date']));
            $todate      = date('Y-m-d H:i:s', strtotime($datum['to_date']));
            $from_date   = !empty($datum['from_date'])? "'$fromdate'": "NULL";
            $to_date     = !empty($datum['to_date'])? "'$todate '": "NULL";
            $quantity    = !empty($datum['quantity'])? "{$datum['quantity']}": 0;
            $status      = !empty($datum['status'])? "{$datum['status']}": 0;
            $created_by  = !empty($datum['created_by'])? "{$datum['created_by']}": "NULL";
            $updated_by  = !empty($datum['updated_by'])? "{$datum['updated_by']}": "NULL";
            $crdate      = date('Y-m-d H:i:s', strtotime($datum['created_at']));
            $update      = date('Y-m-d H:i:s', strtotime($datum['to_date']));
            $created_at  = !empty($datum['created_at'])? "'$crdate'": "NULL";
            $updated_at  = !empty($datum['updated_at'])? "'$update'": "NULL";
            $queryContent
                .= "(" .
                $pdo->quote($datum['shop_id']) . "," .
                $from_date . "," .
                $to_date . "," .
                $pdo->quote($datum['product_id']) . "," .
                $pdo->quote($datum['unit_id']) . "," .
                $quantity . "," .
                $status . "," .
                $created_at . "," .
                $created_by . "," .
                $updated_at . "," .
                $updated_by . "), ";
        }
                
            if (!empty($queryContent)) {
                $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`shop_id`   = values(`shop_id`), " .
                "`from_date` = values(`from_date`), " .
                "`to_date`   = values(`to_date`), " .
                "`product_id`= values(`product_id`), " .
                "`unit_id`   = values(`unit_id`), " .
                "`quantity`  = values(`quantity`), " .
                "`status`    = values(`status`), " .
                "updated_at=$updated_at, updated_by=$updated_by";
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
