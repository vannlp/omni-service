<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\UserGroup;
use App\Supports\Message;
use App\TM;
use App\V1\Models\AreaModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use Box\Spout\Reader\ReaderFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserGroupImportController
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
               0 => "Mã đối tượng (*)",
               1 => "Tên đối tượng (*)",
               2 => "Mô tả",
               3 => "Là mặc định",
               4 => "Chỉ xem và mua hàng theo ngành đăng ký",
               5 => "Cho phép đăng ký trên ứng dụng",
          ];
          $this->_required = [
               0 => trim($this->_header[0]),
               1 => trim($this->_header[1]),
               // 2 => trim($this->_header[2]),
               // 3 => trim($this->_header[3]),
          ];
          $this->_duplicate = [
               // 0 => 'image_id',
               0 => 'code',
               // 2 => 'name',
               // 3 => 'description',
          ];
          $this->_notExisted = [
               // 1 => 'code',
               // 2 => 'name',
          ];
          $this->_number = [
               3 => 'is_default',
               4 => 'is_view',
               5 => 'is_view_app',
          ];
          $this->_date = [];
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
          $fileExt = end($fileName);
          if (empty($fileExt) || !in_array($fileExt, ['xlsx', 'csv', 'ods'])) {
               return response()->json([
                    'status'  => 'error',
                    'message' => Message::get('regex', 'File Import'),
               ], 400);
          }

          $warnings = [];
          $valids = [];
          $countRow = 0;
          $empty = true;
          $fileName = "Import-UserGroup_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

          $file = $input['file'];
          $path = storage_path("Imports");
          if (!file_exists($path)) {
               mkdir($path, 0777, true);
          }

          $existedChk = [];
          $company_id = TM::getCurrentCompanyId();
          // $store_id = TM::getCurrentStoreId();

          try {
               $dataCheck = [
                    'code'         => [
                         'fields' => ['code', 'id', 'name', 'code'],
                         'model'  => new UserGroupModel(),
                         'where' => ' `company_id` = ' . $company_id,
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
                              $tmp = strtoupper($row[0]);
                              if (in_array($tmp, $codeUsed)) {
                                   $row[] = "Duplicate Code " . $tmp;
                                   $warnings[] = array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                   }, $row);
                                   continue;
                              }

                              $err = "";
                              foreach ($this->_number as $key => $name) {

                                   if (empty($row[$key])) {
                                        continue;
                                   }
                         
                                   $val = trim($row[$key]);

                                   if (!is_numeric($val)) {
                                        $err   .= "[" . $this->_header[$key] . " phải là số!]";
                                   }
                              }
                              if ($err) {
                                   $row[] = $err;
                                   $warnings[] = array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                   }, $row);
                                   continue;
                              }
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
                                        $err .= "[$val existed, please input other]";
                                   }
                              }

                              if ($err) {
                                   $row[] = $err;
                                   $warnings[] = array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                   }, $row);
                                   continue;
                              }

                              $countRow++;
                              $valids[] = [
                                   'name'             => $row[1],
                                   'code'             => $row[0],
                                   'description'      => $row[2],
                                   'is_default'      => $row[3],
                                   'is_view_app'      => $row[5],
                                   'is_view'      => $row[4],
                              ];
                         }
                         // print_r($valids);
                         // die;
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
          $queryHeader = "INSERT INTO `user_groups` (" .
               "`code`, " .
               "`name`, " .
               "`company_id`, " .
               "`is_default`, " .
               "`description`, " .
               "`is_view_app`, " .
               "`is_view`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";
          $now = date('Y-m-d H:i:s', time());
          $me = TM::getCurrentUserId();
          // $store = TM::getCurrentStoreId();
          $company = TM::getCurrentCompanyId();
          $queryContent = "";
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
               $queryContent .=
                    "(" .
                    $pdo->quote($datum['code']) . "," .
                    $pdo->quote($datum['name']) . "," .
                    $pdo->quote($company) . "," .
                    $pdo->quote($datum['is_default']) . "," .
                    $pdo->quote($datum['description']) . "," .
                    $pdo->quote($datum['is_view_app']) . "," .
                    $pdo->quote($datum['is_view']) . "," .
                    // $pdo->quote($store) . "," .
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
                    "`is_default`= values(`is_default`), " .
                    "`description`= values(`description`), " .
                    "`company_id` = values(`company_id`), " .
                    "`is_view_app` = values(`is_view_app`), " .
                    "`is_view` = values(`is_view`), " .
                    "updated_at='$now', updated_by=$me";
               // print_r($queryUpdate);die;
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
