<?php

namespace App\V1\Controllers\Import;

use App\Supports\Message;
use App\TM;
use App\V1\Models\RoutingModel;
use App\V1\Models\VisitPlanModel;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VisitPlanImportController
{
    protected $_header;
    protected $_required;
    protected $_duplicate;
    protected $_notExisted;
    protected $_number;
    protected $_date;

    public function __construct()
    {
        $this->_header = [
            0   => 'VISIT_PLAN_ID',
            1   => 'ROUTING_ID',
            2   => 'SHOP_ID',
            3   => 'STAFF_ID',
            4   => 'FROM_DATE',
            5   => 'TO_DATE',
            6   => 'STATUS',
            7   => 'CREATE_DATE',
            8   => 'CREATE_USER',
            9   => 'UPDATE_DATE',
            10  => 'UPDATE_USER',
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
            4 => trim($this->_header[4]),
            5 => trim($this->_header[5]),
        ];
        $this->_duplicate = [
            0 => trim($this->_header[0]),
        ];
        $this->_notExisted = [
            1 => trim($this->_header[1]),

        ];
        $this->_number = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
        ];
        $this->_date = [];
    }
    public function import(Request $request)
    {
        $input = $request->all();

        if (empty($input['file'])) {
            return response()->json([
                'status'  => 'error',
                'message' => Message::get("V001", 'File Import')
            ], 400);
        }

        $fileName = explode(".", $_FILES["file"]["name"]);
        $fileExt = end($fileName);
        if (empty($fileExt) || !in_array($fileExt, ['xlsx', 'csv', 'ods'])) {
            return response()->json([
                'status'  => 'error',
                'message' => Message::get('regex', 'File Import'),
            ], 400);
        }

        $errors = [];
        $valids = [];
        $countRow = 0;
        $error = false;
        $empty = true;
        $fileName = "Import-VisitPlan_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                'VISIT_PLAN_ID' => [
                    'fields' => ['visit_plan_id', 'id', 'visit_plan_id'],
                    'model'  => new VisitPlanModel(),
                ],
                'ROUTING_ID' => [
                    'fields' => ['routing_id', 'routing_id', 'routing_id'],
                    'model'  => new RoutingModel(),
                ]
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
            $codeUsed = [];
            $totalLines = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $row = [
                        0  => $row[0] ?? "",
                        1  => $row[1] ?? "",
                        2  => $row[2] ?? "",
                        3  => $row[3] ?? "",
                        4  => $row[4] ?? "",
                        5  => $row[5] ?? "",
                        6  => $row[6] ?? "",
                        7  => $row[7] ?? "",
                        8  => $row[8] ?? "",
                        9  => $row[9] ?? "",
                        10  => $row[10] ?? "",
                    ];

                    $totalLines++;
                    if ($totalLines > 10001) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => Message::get("V013", Message::get("data_row"), 10000),
                        ], 400);
                    }
                    // dd(array_diff($this->_header, $row));
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

                    if (!empty($row[0])) {
                        $tmp = strtoupper($row[0]);
                        if (in_array($tmp, $codeUsed)) {
                            $row[] = "Duplicate Code " . $tmp;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        } else array_push($codeUsed, $tmp);
                    }

                    if (array_filter($row)) {
                        // Check Valid
                        $errorDetail = $this->checkValidRow($row, $codeUsed, $existedChk);
                        $countRow++;
                        $valids[] = [
                            'visit_plan_id'     => !empty($row[0]) ? $row[0] : NULL,
                            'routing_id'        => !empty($row[1]) ? $row[1] : null,
                            'shop_id'           => !empty($row[2]) ? $row[2] : NULL,
                            'staff_id'          => !empty($row[3]) ? $row[3] : NULL,
                            'from_date'         => !empty($row[4]) ? $row[4] : NULL,
                            'to_date'           => !empty($row[5]) ? $row[5] : NULL,
                            'status'            => !empty($row[6]) ? $row[6] : NULL,
                            'created_at'        => !empty($row[7]) ? $row[7] : NULL,
                            'created_by'        => !empty($row[8]) ? $row[8] : NULL,
                            'updated_at'        => !empty($row[9]) ? $row[9] : NULL,
                            'updated_by'        => !empty($row[10]) ? $row[10] : NULL
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
                        $codeUsed[] = '';
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

            // dd($valids);

            $reader->close();
            unlink($filePath);

            if ($error) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $errors,
                ], 406);
            } else {
                try {
                    $this->startImport($valids, $codeUsed);
                    return response()->json([
                        'status'  => 'success',
                        'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                    ], 200);
                } catch (\Exception $ex) {
                    return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
                }
            }
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
    }



    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }

        DB::beginTransaction();
        $queryHeader = "INSERT INTO `visit_plans` (" .
            "`visit_plan_id`, " .
            "`routing_id`, " .
            "`shop_id`, " .
            "`staff_id`, " .
            "`from_date`, " .
            "`to_date`, " .
            "`status`, " .
            "`created_at`," .
            "`created_by`," .
            "`updated_at`," .
            "`updated_by`) VALUES ";
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo        = DB::getPdo();
        $me         = TM::getCurrentUserId();
        $store      = TM::getCurrentStoreId();
        $company    = TM::getCurrentCompanyId();
        foreach ($data as $datum) {

            $create_date = DateTime::createFromFormat('d/m/Y H.i.s', $datum['created_at'])->format('Y-m-d H:i:s');

            $update_date = DateTime::createFromFormat('d/m/Y H.i.s', $datum['updated_at'])->format('Y-m-d H:i:s');

            $from_date   = DateTime::createFromFormat('d/m/Y H.i.s', $datum['from_date'])->format('Y-m-d H:i:s');

            $to_date     = DateTime::createFromFormat('d/m/Y H.i.s', $datum['to_date'])->format('Y-m-d H:i:s');;

            $queryContent .=
                "(" .
                $pdo->quote($datum['visit_plan_id']) . "," .
                $pdo->quote($datum['routing_id']) . "," .
                $pdo->quote($datum['shop_id']) . "," .
                $pdo->quote($datum['staff_id']) . "," .
                $pdo->quote($from_date) . "," .
                $pdo->quote($to_date) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote($create_date) . "," .
                $pdo->quote($datum['created_by']) . "," .
                $pdo->quote($update_date) . "," .
                $pdo->quote($datum['updated_by']) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`visit_plan_id` = values(`id`) ";
            // dd($queryUpdate);
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }

    private function checkValidRow(&$row, &$codeUsed, $existedChk = [])
    {
        $errorDetail = "";

        // Check Required
        foreach ($this->_required as $key => $name) {
            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                $errorDetail .= "[" . Message::get("V001", $this->_header[$key]) . "]";
            }
        }
        // Check Duplicated
        foreach ($this->_duplicate as $key => $name) {
            if (empty($row[$key])) {
                continue;
            }
            $val = trim(strtoupper($row[$key]));


            if (!empty($existedChk[$name][$val])) {
                $errorDetail .= "[" . Message::get("V007", $this->_header[$key]) . "]";
            }
        }

        // Check Existed
        foreach ($this->_notExisted as $key => $name) {
            if (empty($row[$key])) {
                continue;
            }

            $val = trim(strtoupper($row[$key]));

            if (!isset($existedChk[$name][$val])) {
                $errorDetail .= "[" . Message::get("V003", $this->_header[$key]) . "]";
            } else {
                if ($name == 'code') {
                    $row[$key] = $existedChk[$name][$val];
                    continue;
                }
                $row[$key] = $val;
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
            if (empty($row[$key])) {
                continue;
            }

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

        return $errorDetail;
    }
}
