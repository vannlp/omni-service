<?php


namespace App\V1\Controllers\Import;


use App\Category;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\ProductExcel;
use App\V1\Models\CategoryModel;
use App\V1\Models\CityModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\ProductExcelModel;
use App\V1\Models\ProductInfoModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use App\V1\Models\WardModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductInfoVNSImportController
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
            0  => "PRODUCT_INFO_ID",
            1  => "PRODUCT_INFO_CODE",
            2  => "PRODUCT_INFO_NAME",
            3  => "DESCRIPTION",
            4  => "STATUS",
            5  => "TYPE",
        ];

        $this->_required = [
            1   => trim($this->_header[1]),
            2   => trim($this->_header[2]),
            3   => trim($this->_header[3]),
            5   => trim($this->_header[5]),
          
        ];
        $this->_duplicate = [
            0 => trim($this->_header[0]),         
        ];
        $this->_notExisted = [

        ];
        $this->_number = [
            4  => trim($this->_header[4]),
          
        ];
        $this->_date = [
            
        ];
    }

    public function  import(Request $request)
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
        $fileName = "Import-Product_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                'PRODUCT_INFO_ID' => [
                    'fields' => ['id', 'code', 'id'],
                    'model'  => new ProductInfoModel(),
                ],
                'PRODUCT_INFO_CODE' => [
                    'fields' => ['code', 'id', 'product_info_name', 'code'],
                    'model'  => new ProductInfoModel(),
                ],
                'PRODUCT_INFO_Name' => [
                    'fields' => ['product_info_name', 'id', 'product_info_name'],
                    'model'  => new ProductInfoModel(),
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
                        0  => $row[0] ?? "",
                        1  => $row[1] ?? "",
                        2  => $row[2] ?? "",
                        3  => $row[3] ?? "",
                        4  => $row[4] ?? "",
                        5  => $row[5] ?? "",
                       
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
                            'code'                  => $row[1],
                            'product_info_name'     => $row[2],
                            'description'           => $row[3],
                            'status'                => $row[4],
                            'type'                  => $row[5],
                            // 'type'                  => !empty($row[5]) ? $existedChk['ProductExcel'][trim(strtoupper($row[5]))] : null,
                            
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
//            foreach ($errors as $line) {
//                print_r($line);die();
//                $val = explode(",", $line);
//
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

    private function checkValidRow(&$row, &$codeUsed, $existedChk = [])
    {
        // die();
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
        $queryHeader = "INSERT INTO `product_info_dms_imports` (" .
        
        "`code`," .
        "`product_info_name`," .
        "`description`," .
        "`status`," .
        "`type`," .
        

        "`created_at`, " .
        "`created_by`, " .
        "`updated_at`, " .
        "`updated_by`) VALUES ";

        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $queryContent .=
                "(" .
                $pdo->quote($datum['code']). "," .
                $pdo->quote($datum['product_info_name']). "," .
                $pdo->quote($datum['description']). "," .
                $pdo->quote($datum['status']). "," .
                $pdo->quote($datum['type']). "," .
                

                
                // $pdo->quote($datum['is_active']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
              


                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }
}