<?php
/**
 * User: kpistech2
 * Date: 2020-11-19
 * Time: 20:11
 */

namespace App\V1\Controllers\Import;


use App\Category;
use App\Supports\Message;
use App\TM;
use App\V1\Controllers\BaseController;
use App\V1\Models\CategoryModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductCategoryImportController extends BaseController
{
    protected $_header;
    protected $_required;
    protected $_duplicate;
    protected $_notExisted;
    protected $_number;
    protected $_date;

    /**
     * KpiDailyImportController constructor.
     */
    public function __construct()
    {
        $this->_header = [
            0 => "Code (*)",
            1 => "Name (*)",
            2 => "Parent Code",
            3 => "Order",
            4 => "Description",
            5 => "Active",
        ];

        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
        ];

        $this->_duplicate = [
            0 => trim($this->_header[0]),
        ];

        $this->_notExisted = [
        ];

        $this->_number = [
            3 => trim($this->_header[3]),
            5 => trim($this->_header[5]),
        ];
        $this->_date = [
        ];
        //parent::__construct();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function init(Request $request)
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
        $fileName = "Import-Product-Category_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                'parent_category' => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new CategoryModel(),
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

            $codeUsed = [];
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
                    if ($totalLines > 10001) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => Message::get("V013", Message::get("data_row"), 10000),
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
                        // Check Valid
                        $errorDetail = $this->checkValidRow($row, $codeUsed, $existedChk);
                        $countRow++;

                        $valids[] = [
                            'code'        => $row[0],
                            'name'        => $row[1],
                            'slug'        => string_to_slug($row[1]),
                            'parent_id'   => !empty($row[2]) ? $existedChk['parent_category'][trim(strtoupper($row[2]))]['id'] ?? "R-" . $row[2] : null,
                            'order'       => $row[3],
                            'description' => $row[4],
                            'is_active'   => $row[5],
                            'type'        => 'PRODUCT',
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
                        $codeUsed[] = $row[0];
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
//            header('Content-Type: text/csv');
//            header('Content-Disposition: attachment; filename="sample.csv"');
//
//            $fp = fopen('php://output', 'wb');
//            foreach ( $errors as $line ) {
//                $val = explode(",", $line);
//                fputcsv($fp, $val);
//            }
//            fclose($fp);
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
    }

    /**
     * @param $data
     * @return int
     */
    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }

        DB::beginTransaction();
        $queryHeader = "INSERT INTO `categories` (" .
            "`code`, " .
            "`name`, " .
            "`slug`, " .
            "`description`, " .
            "`type`, " .
            "`order`, " .
            "`parent_id`, " .
            "`store_id`, " .
            "`is_active`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();
        $store = TM::getCurrentStoreId();

        $updateParentAfter = [];

        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            if (!empty($datum['parent_id'])) {
                if (!is_numeric($datum['parent_id'])) {
                    $updateParentAfter[$datum['code']] = str_replace("R-", "", $datum['parent_id']);
                    $datum['parent_id'] = null;
                }
            }
            $queryContent .=
                "(" .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($datum['slug']) . "," .
                $pdo->quote($datum['description']) . "," .
                $pdo->quote($datum['type']) . "," .
                (int)$datum['order'] . "," .
                ($datum['parent_id'] ? $pdo->quote($datum['parent_id']) : "null") . "," .
                $pdo->quote($store) . "," .
                $pdo->quote($datum['is_active']) . "," .
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
                "`slug`= values(`slug`), " .
                "`description` = values(`description`), " .
                "`type` = values(`type`), " .
                "`order` = values(`order`), " .
                "`parent_id` = values(`parent_id`), " .
                "`store_id` = values(`store_id`), " .
                "`is_active` = values(`is_active`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }

        if ($updateParentAfter) {
            $this->updateParentCode($updateParentAfter);
        }
        // Commit transaction
        DB::commit();
    }

    /**
     * @param $row
     * @param $technicianUsed
     * @param array $existedChk
     * @return string
     */
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

        // Check Duplicate Code
        $tmp = strtoupper($row[0]);
        if (in_array($tmp, $codeUsed)) {
            $errorDetail .= "[" . Message::get("V028", $row[0]) . "]";
        }

        // Check Not Exist Parent
        $tmp = strtoupper($row[2]);
        if (!empty($tmp)) {
            if (!isset($existedChk['parent_category'][$row[2]]) && !in_array($tmp, $codeUsed)) {
                $errorDetail .= "[" . Message::get("V003", $this->_header[2]) . "]";
            }
        }

        return $errorDetail;
    }

    private function updateParentCode($updateParentAfter)
    {
        $arrIn = array_unique(array_merge(array_keys($updateParentAfter), array_values($updateParentAfter)));
        $categories = Category::model()->whereIn('code', $arrIn)->where('store_id', TM::getCurrentStoreId())
            ->get()->pluck(null, 'code')->toArray();
        $queryHeader = "INSERT INTO `categories` (" .
            "`code`, " .
            "`name`, " .
            "`store_id`, " .
            "`parent_id`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();

        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($updateParentAfter as $code => $parentCode) {
            $queryContent .=
                "(" .
                $pdo->quote($code) . "," .
                $pdo->quote($categories[$parentCode]['name']) . "," .
                $pdo->quote($categories[$parentCode]['store_id']) . "," .
                $pdo->quote($categories[$parentCode]['id']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                "`store_id` = values(`store_id`), " .
                "`parent_id` = values(`parent_id`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
    }
}