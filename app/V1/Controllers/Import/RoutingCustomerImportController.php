<?php

namespace App\V1\Controllers\Import;

use App\Supports\Message;
use App\TM;
use App\V1\Models\RoutingCustomerModel;
use App\V1\Models\RoutingModel;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoutingCustomerImportController
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
            0   => 'ROUTING_CUSTOMER_ID',
            1   => 'ROUTING_ID',
            2   => 'CUSTOMER_ID',
            3   => 'SHOP_ID',
            4   => 'MONDAY',
            5   => 'TUESDAY',
            6   => 'WEDNESDAY',
            7   => 'THURSDAY',
            8   => 'FRIDAY',
            9   => 'SATURDAY',
            10  => 'SUNDAY',
            11  => 'SEQ2',
            12  => 'SEQ3',
            13  => 'SEQ4',
            14  => 'SEQ5',
            15  => 'SEQ6',
            16  => 'SEQ7',
            17  => 'SEQ8',
            18  => 'SEQ',
            19  => 'START_WEEK',
            20  => 'START_DATE',
            21  => 'END_DATE',
            22  => 'WEEK1',
            23  => 'WEEK2',
            24  => 'WEEK3',
            25  => 'WEEK4',
            26  => 'WEEK_INTERVAL',
            27  => 'FREQUENCY',
            28  => 'LAST_ORDER',
            29  => 'LAST_APPROVE_ORDER',
            30  => 'DAY_PLAN',
            31  => 'PLAN_DATE',
            32  => 'DAY_PLAN_AVG',
            33  => 'PLAN_AVG_DATE',
            34  => 'STATUS',
            35  => 'CREATE_DATE',
            36  => 'CREATE_USER',
            37  => 'UPDATE_DATE',
            38  => 'UPDATE_USER',
        ];
        $this->_required = [
            0   => trim($this->_header[0]),
            1   => trim($this->_header[1]),
            2   => trim($this->_header[2]),
            3   => trim($this->_header[3]),
            19  => trim($this->_header[19]),
            20  => trim($this->_header[20]),
            28  => trim($this->_header[28]),
            29  => trim($this->_header[29]),
            34  => trim($this->_header[34]),
            35  => trim($this->_header[35]),
            37  => trim($this->_header[37]),

        ];
        $this->_duplicate = [
            0 => trim($this->_header[0]),
        ];
        $this->_notExisted = [
            1   => trim($this->_header[1]),

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
        $fileName = "Import-Routing-Customer_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];

        try {
            $dataCheck = [
                'ROUTING_CUSTOMER_ID' => [
                    'fields' => ['routing_customer_id', 'id', 'routing_customer_id'],
                    'model'  => new RoutingCustomerModel(),
                ],
                'ROUTING_ID' => [
                    'fields' => ['routing_id', 'routing_id', 'routing_id'],
                    'model'  => new RoutingModel(),
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
            $codeUsed = [];
            $totalLines = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $row = [
                        0   => $row[0] ?? "",
                        1   => $row[1] ?? "",
                        2   => $row[2] ?? "",
                        3   => $row[3] ?? "",
                        4   => $row[4] ?? "",
                        5   => $row[5] ?? "",
                        6   => $row[6] ?? "",
                        7   => $row[7] ?? "",
                        8   => $row[8] ?? "",
                        9   => $row[9] ?? "",
                        10  => $row[10] ?? "",
                        11  => $row[11] ?? "",
                        12  => $row[12] ?? "",
                        13  => $row[13] ?? "",
                        14  => $row[14] ?? "",
                        15  => $row[15] ?? "",
                        16  => $row[16] ?? "",
                        17  => $row[17] ?? "",
                        18  => $row[18] ?? "",
                        19  => $row[19] ?? "",
                        20  => $row[20] ?? "",
                        21  => $row[21] ?? "",
                        22  => $row[22] ?? "",
                        23  => $row[23] ?? "",
                        24  => $row[24] ?? "",
                        25  => $row[25] ?? "",
                        26  => $row[26] ?? "",
                        27  => $row[27] ?? "",
                        28  => $row[28] ?? "",
                        29  => $row[29] ?? "",
                        30  => $row[30] ?? "",
                        31  => $row[31] ?? "",
                        32  => $row[32] ?? "",
                        33  => $row[33] ?? "",
                        34  => $row[34] ?? "",
                        35  => $row[35] ?? "",
                        36  => $row[36] ?? "",
                        37  => $row[37] ?? "",
                        38  => $row[38] ?? "",
                    ];

                    $totalLines++;
                    if ($totalLines > 10001) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => Message::get("V013", Message::get("data_row"), 10000),
                        ], 400);
                    }
                    $empty = false;
                    // dd(array_diff($this->_header, $row));
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
                            'routing_customer_id'   => !empty($row[0]) ? $row[0] : NULL,
                            'routing_id'            => !empty($row[1]) ? $row[1] : NULL,
                            'customer_id'           => !empty($row[2]) ? $row[2] : NULL,
                            'shop_id'               => !empty($row[3]) ? $row[3] : NULL,
                            'monday'                => !empty($row[4]) ? $row[4] : NULL,
                            'tuesday'               => !empty($row[5]) ? $row[5] : NULL,
                            'wednesday'             => !empty($row[6]) ? $row[6] : NULL,
                            'thursday'              => !empty($row[7]) ? $row[7] : NULL,
                            'friday'                => !empty($row[8]) ? $row[8] : NULL,
                            'saturday'              => !empty($row[9]) ? $row[9] : NULL,
                            'sunday'                => !empty($row[10]) ? $row[10] : NULL,
                            'seq2'                  => !empty($row[11]) ? $row[11] : NULL,
                            'seq3'                  => !empty($row[12]) ? $row[12] : NULL,
                            'seq4'                  => !empty($row[13]) ? $row[13] : NULL,
                            'seq5'                  => !empty($row[14]) ? $row[14] : NULL,
                            'seq6'                  => !empty($row[15]) ? $row[15] : NULL,
                            'seq7'                  => !empty($row[16]) ? $row[16] : NULL,
                            'seq8'                  => !empty($row[17]) ? $row[17] : NULL,
                            'seq'                   => !empty($row[18]) ? $row[18] : NULL,
                            'start_week'            => !empty($row[19]) ? $row[19] : NULL,
                            'start_date'            => !empty($row[20]) ? $row[20] : NULL,
                            'end_date'              => !empty($row[21]) ? $row[21] : NULL,
                            'week1'                 => !empty($row[22]) ? $row[22] : NULL,
                            'week2'                 => !empty($row[23]) ? $row[23] : NULL,
                            'week3'                 => !empty($row[24]) ? $row[24] : NULL,
                            'week4'                 => !empty($row[25]) ? $row[25] : NULL,
                            'week_interval'         => !empty($row[26]) ? $row[26] : NULL,
                            'frequency'             => !empty($row[27]) ? $row[27] : NULL,
                            'last_order'            => !empty($row[28]) ? $row[28] : NULL,
                            'last_approve_order'    => !empty($row[29]) ? $row[29] : NULL,
                            'day_plan'              => !empty($row[30]) ? $row[30] : NULL,
                            'plan_date'             => !empty($row[31]) ? $row[31] : NULL,
                            'day_plan_avg'          => !empty($row[32]) ? $row[32] : NULL,
                            'plan_avg_date'         => !empty($row[33]) ? $row[33] : NULL,
                            'status'                => !empty($row[34]) ? $row[34] : NULL,
                            'create_date'           => !empty($row[35]) ? $row[35] : NULL,
                            'create_user'           => !empty($row[36]) ? $row[36] : NULL,
                            'update_date'           => !empty($row[37]) ? $row[37] : NULL,
                            'update_user'           => !empty($row[38]) ? $row[38] : NULL,
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
                // dd($valids);
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
    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }

        DB::beginTransaction();
        $queryHeader = "INSERT INTO `routing_customers` (" .
            "`routing_customer_id`, " .
            "`routing_id`, " .
            "`customer_id`, " .
            "`shop_id`, " .
            "`monday`, " .
            "`tuesday`, " .
            "`wednesday`, " .
            "`thursday`, " .
            "`friday`, " .
            "`saturday`, " .
            "`sunday`, " .
            "`seq2`, " .
            "`seq3`, " .
            "`seq4`, " .
            "`seq5`, " .
            "`seq6`, " .
            "`seq7`, " .
            "`seq8`, " .
            "`seq`, " .
            "`start_week`, " .
            "`start_date`, " .
            "`end_date`, " .
            "`week1`, " .
            "`week2`, " .
            "`week3`, " .
            "`week4`, " .
            "`week_interval`, " .
            "`frequency`, " .
            "`last_order`, " .
            "`last_approve_order`, " .
            "`day_plan`, " .
            "`plan_date`, " .
            "`day_plan_avg`, " .
            "`plan_avg_date`, " .
            "`status`, " .
            "`created_at`," .
            "`created_by`," .
            "`updated_at`," .
            "`updated_by`) VALUES ";
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {

            $start_date         = DateTime::createFromFormat('d/m/Y H.i.s', $datum['start_date'])->format('Y-m-d H:i:s');

            $end_date           = DateTime::createFromFormat('d/m/Y H.i.s', $datum['end_date'])->format('Y-m-d H:i:s');

            $last_order         = DateTime::createFromFormat('d/m/Y H.i.s', $datum['last_order'])->format('Y-m-d H:i:s');

            $last_approve_order = DateTime::createFromFormat('d/m/Y H.i.s', $datum['last_approve_order'])->format('Y-m-d H:i:s');

            $create_date        = DateTime::createFromFormat('d/m/Y H.i.s', $datum['create_date'])->format('Y-m-d H:i:s');

            $update_date        = DateTime::createFromFormat('d/m/Y H.i.s', $datum['update_date'])->format('Y-m-d H:i:s');

            $queryContent .=
                "(" .
                $pdo->quote($datum['routing_customer_id']) . "," .
                $pdo->quote($datum['routing_id']) . "," .
                $pdo->quote($datum['customer_id']) . "," .
                $pdo->quote($datum['shop_id']) . "," .
                $pdo->quote($datum['monday']) . "," .
                $pdo->quote($datum['tuesday']) . "," .
                $pdo->quote($datum['wednesday']) . "," .
                $pdo->quote($datum['thursday']) . "," .
                $pdo->quote($datum['friday']) . "," .
                $pdo->quote($datum['saturday']) . "," .
                $pdo->quote($datum['sunday']) . "," .
                $pdo->quote($datum['seq2']) . "," .
                $pdo->quote($datum['seq3']) . "," .
                $pdo->quote($datum['seq4']) . "," .
                $pdo->quote($datum['seq5']) . "," .
                $pdo->quote($datum['seq6']) . "," .
                $pdo->quote($datum['seq7']) . "," .
                $pdo->quote($datum['seq8']) . "," .
                $pdo->quote($datum['seq']) . "," .
                $pdo->quote($datum['start_week']) . "," .
                $pdo->quote($start_date) . "," .
                $pdo->quote($end_date) . "," .
                $pdo->quote($datum['week1']) . "," .
                $pdo->quote($datum['week2']) . "," .
                $pdo->quote($datum['week3']) . "," .
                $pdo->quote($datum['week4']) . "," .
                $pdo->quote($datum['week_interval']) . "," .
                $pdo->quote($datum['frequency']) . "," .
                $pdo->quote($last_order) . "," .
                $pdo->quote($last_approve_order) . "," .
                $pdo->quote($datum['day_plan']) . "," .
                $pdo->quote($datum['plan_date']) . "," .
                $pdo->quote($datum['day_plan_avg']) . "," .
                $pdo->quote($datum['plan_avg_date']) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote($create_date) . "," .
                $pdo->quote($datum['create_user']) . "," .
                $pdo->quote($update_date) . "," .
                $pdo->quote($datum['update_user']) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`routing_customer_id` = values(`routing_customer_id`) ";

            // dd($queryUpdate);
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }
}
