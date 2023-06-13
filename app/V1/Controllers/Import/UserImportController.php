<?php
/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;


use App\Jobs\Import\ImportUserJob;
use App\Supports\Message;
use App\TM;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserImportController
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
            0 => "MÃ KH",
            1 => "TÊN KH",
            2 => "NHÓM KH (*)",
            3 => "PHONE",
            4 => "ADDRESS",
            5 => "MÃ NPP",
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
        ];
        $this->_duplicate = [
            0 => 'code',
            3 => 'phone',
        ];
        $this->_notExisted = [
            2 => 'groups',
            5 => 'distributors',
        ];
        $this->_number = [
        ];
        $this->_date = [
        ];
    }

    public function init(Request $request)
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
        $fileExt = end($fileName);
        if (empty($fileExt) || !in_array($fileExt, ['xlsx', 'csv', 'ods'])) {
            return response()->json([
                'status'  => 'error',
                'message' => Message::get('regex', 'File Import'),
            ], 400);
        }

        $warnings = "";
        $valids = [];
        $countRow = 0;
        $empty = true;
        $fileName = "Import-Customer_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                'code'         => [
                    'fields' => ['code', 'id', 'name', 'code', 'code'],
                    'model'  => new UserModel(),
                ],
                'phone'        => [
                    'fields' => ['phone', 'id', 'name', 'code', 'phone'],
                    'model'  => new UserModel(),
                ],
                'groups'       => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new UserGroupModel(),
                ],
                'distributors' => [
                    'fields' => ['code', 'name', 'code', 'id'],
                    'model'  => new UserModel(),
                    'where'  => ' group_code="HUB" ',
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
            $phoneUsed = [];
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
                        $row[3] = str_replace(" ", "", $row[3]);

                        foreach ($this->_required as $key => $name) {
                            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                                $err = "Required " . $this->_header[0];
                                $warnings .= "\"{$row[0]}\",\"{$row[1]}\",\"{$row[2]}\",\"{$row[3]}\",\"{$row[5]}\",\"$err\"\n";
                                continue;
                            }
                        }

                        // Check Existed
                        foreach ($this->_notExisted as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }

                            $val = trim(strtoupper($row[$key]));
                            if (!isset($existedChk[$name][$val])) {
                                $err = $this->_header[0] . "not existed in DB";
                                $warnings .= "\"{$row[0]}\",\"{$row[1]}\",\"{$row[2]}\",\"{$row[3]}\",\"{$row[5]}\",\"$err\"\n";
                                continue;
                            }
                        }

                        // Check Duplicate Code
                        $tmp = strtoupper($row[0]);
                        if (in_array($tmp, $codeUsed)) {
                            $err = "Duplicate Code " . $tmp . " in a file";
                            $warnings .= "\"{$row[0]}\",\"{$row[1]}\",\"{$row[2]}\",\"{$row[3]}\",\"{$row[5]}\",\"$err\"\n";
                            continue;
                        }

                        // Check Duplicate Code
                        $tmp = strtoupper($row[3]);
                        if (in_array($tmp, $phoneUsed)) {
                            $err = "Duplicate Phone" . $tmp . "in a file";
                            $warnings .= "\"{$row[0]}\",\"{$row[1]}\",\"{$row[2]}\",\"{$row[3]}\",\"{$row[5]}\",\"$err\"\n";
                            continue;
                        }

                        // Check Duplicated
                        $err = "";
                        foreach ($this->_duplicate as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }
                            $val = trim(strtoupper($row[$key]));
                            if (!empty($existedChk[$name][$val])) {
                                $err .= "[$val existed in DB, please input other]";
                            }
                        }
                        if ($err) {
                            $warnings .= "\"{$row[0]}\",\"{$row[1]}\",\"{$row[2]}\",\"{$row[3]}\",\"{$row[5]}\",\"$err\"\n";
                            continue;
                        }

                        $tmp = strtoupper($row[3]);
                        if (strlen($tmp) > 12 || !is_numeric($tmp)) {
                            $err = "[" . Message::get("V002", $this->_header[3]) . "]";
                            $warnings .= "\"{$row[0]}\",\"{$row[1]}\",\"{$row[2]}\",\"{$row[3]}\",\"{$row[5]}\",\"$err\"\n";
                            continue;
                        }

                        $countRow++;
                        $valids[] = [
                            'name'             => $row[1],
                            'code'             => $row[0],
                            'phone'            => $row[3],
                            'group_code'       => $row[2],
                            'group_id'         => !empty($row[2]) ? $existedChk['groups'][trim(strtoupper($row[2]))]['id'] ?? null : null,
                            'group_name'       => !empty($row[2]) ? $existedChk['groups'][trim(strtoupper($row[2]))]['name'] ?? null : null,
                            'distributor_code' => $row[5],
                            'distributor_id'   => !empty($row[5]) ? $existedChk['distributors'][trim(strtoupper($row[5]))]['id'] ?? null : null,
                            'distributor_name' => !empty($row[5]) ? $existedChk['distributors'][trim(strtoupper($row[5]))]['name'] ?? null : null,
                            'address'          => $row[4],
                        ];

                        $codeUsed[] = $row[0];
                        $phoneUsed[] = $row[3];
                        if (count($valids) >= 10000) {
                            $job = new ImportUserJob($valids, TM::getCurrentUserId(), TM::getCurrentCompanyId(),
                                TM::getCurrentStoreId());
                            dispatch($job);
                            $valids = [];
                        }
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
            if (!empty($valids)) {
                $job = new ImportUserJob($valids, TM::getCurrentUserId(), TM::getCurrentCompanyId(),
                    TM::getCurrentStoreId());
                dispatch($job);
                $valids = [];
            }
            $this->writeWarning($warnings, [
                $this->_header[0],
                $this->_header[1],
                $this->_header[2],
                $this->_header[3],
                $this->_header[5],
            ]);
            return response()->json([
                'status'  => 'success',
                'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                'warning' => !empty($warnings) ? $warnings : null,
            ], 200);
        } catch (\Exception $ex) {
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
    }

    /**
     * @param $strCSV
     * @param array $header
     * @return bool
     */
    private function writeWarning(&$strCSV, $header = [])
    {
        if (empty($strCSV)) {
            return false;
        }
        $dir = storage_path('Imports/Warnings');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $strCSV = ($header ? "\"" . implode("\",\"", $header) . "\"\n" : "") . $strCSV;

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-CUSTOMER-(warning).csv", 'w');
        fwrite($csv_handler, $strCSV);
        fclose($csv_handler);

        return true;

    }
}