<?php


namespace App\V1\Controllers\Import;


use App\Category;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\User;
use App\File;
use App\Area;
use App\V1\Models\CategoryModel;
use App\V1\Models\ProductModel;
use App\V1\Models\AreaModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class ImportProductCategoryController
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
            0  => "Mã nhóm ngành nghề (*)",
            1  => "Mã danh mục (*)",
            2  => "Tên danh mục (*)",
            3  => "Thứ tự (*)",
            4  => "Mô tả",
            5  => "Hiển thị lên web/app",
            6  => "Hiển thị sản phẩm của mục này lên web/app",
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
        ];
        $this->_duplicate = [
            1 => trim($this->_header[1]),
        ];
        $this->_notExisted = [
        ];
        $this->_number = [
            3  => trim($this->_header[3]),
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
                'code' => [
                    'fields' => ['code', 'id', 'code'],
                    'model'  => new CategoryModel(),
                ],            
                'area' => [
                    'fields' => ['code', 'id', 'code'],
                    'model'  => new AreaModel(),
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
                        6  => $row[6] ?? ""

                    ];

                    $totalLines++;
                    // if ($totalLines > 10001) {
                    //     return response()->json([
                    //         'status'  => 'error',
                    //         'message' => Message::get("V013", Message::get("data_row"), 10000),
                    //     ], 400);
                    // }
                    $empty = false;

                    if ($index === 1) {
                        // print_r(array_diff($this->_header,$row));die;
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
                                        $row[] = "Required " . $this->_header[0];
                                        $warnings[] = array_map(function ($r) {
                                             return "\"" . $r . "\"";
                                        }, $row);
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
                                        $row[] = "Required " . $this->_header[0];
                                        $warnings[] = array_map(function ($r) {
                                             return "\"" . $r . "\"";
                                        }, $row);
                                        continue;
                                   }
                              }

                              // Check Duplicate Code
                              $tmp = strtoupper($row[1]);
                              if (in_array($tmp, $codeUsed)) {
                                   $row[] = "Duplicate Code " . $tmp;
                                   $warnings[] = array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                   }, $row);
                                   continue;
                              }

                              // // Check Duplicate Code
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
                                        $err .= "[$val existed, please input other]";
                                   }
                              }
                              
                              if ($err) {
                                   $row[] = $err;
                                   $warnings[]= array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                   }, $row);
                                   continue;
                              }


                              $countRow++;
                              $valids[] = [
                            'area_id'           => !empty($row[0]) ? $existedChk['area'][trim(strtoupper($row[0]))] ?? null : null,
                            'code'              => $row[1],
                            'name'              => $row[2],
                            'order'             => !empty($row[3]) ? $row[3] : null,
                            'description'       => !empty($row[4]) ? $row[4] : null,
                            'product_publish'   => 0,
                            'category_publish'  => 0
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

    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }
        DB::beginTransaction();
        $queryHeader = "INSERT INTO `categories` (" .
            "`area_id`," .
            "`code`," .
            "`name`, " .
            "`order`," .
            "`description`," .
            "`product_publish`," .
            "`category_publish`," .    
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
                $pdo->quote($datum['area_id']) . "," .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                ($datum['order'] ? $pdo->quote($datum['order']) : "null") . "," . 
                ($datum['description'] ? $pdo->quote($datum['description']) : "null") . "," .
                $pdo->quote($datum['product_publish']) . "," .
                $pdo->quote($datum['category_publish']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        // print_r($queryContent);die;
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                "`name`= values(`name`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }
}