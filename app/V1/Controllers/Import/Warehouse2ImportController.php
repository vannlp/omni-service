<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;
use App\Supports\Message;
use App\TM;
use App\V1\Models\WarehouseModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Warehouse2ImportController
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
               0 => "WAREHOUSE_ID",
               1 => "SHOP_ID",
               2 => "WAREHOUSE_CODE",
               3 => "WAREHOUSE_NAME",
               4 => "WAREHOUSE_TYPE",
               5 => "SEQ",
               6 => "STATUS",
               7 => "CREATE_DATE",
               8 => "CREATE_USER",
               9 =>"UPDATE_DATE",
               10 =>"UPDATE_USER",
              


          ];
          $this->_required = [
               1 => trim($this->_header[1]),
               2 => trim($this->_header[2]),
               3 => trim($this->_header[3]),
          ];
          $this->_duplicate = [
               2 => 'code',
          ];
          $this->_notExisted = [];
          $this->_number = [];
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
          $fileName = "Import-Warehouse_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

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
                    'code'=> [
                         'fields' => ['code', 'id', 'name','code'],
                         'model'  => new WarehouseModel(),
                         'where' => ' `company_id` = ' . $company_id . ' and `store_id` = ' . $store_id,
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
            //    $phoneUsed = [];
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
                              6 => $row[6] ?? "",
                              7 => $row[7] ?? "",
                              8 => $row[8] ?? "",
                              9 => $row[9] ?? "",
                             10 => $row[10] ??"",
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
                              $tmp = strtoupper($row[2]);
                              if (in_array($tmp, $codeUsed)) {
                                   $row[] = "Duplicate Code " . $tmp;
                                   $warnings[] = array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                   }, $row);
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
                                       $err .= "[$val đã tồn tại, Vui lòng nhập khác]";
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
                                   'name'             => $row[3],
                                   'code'             => $row[2],
                                   'address'          => "",
                                   'shop_id'          => $row[1], 
                                   'warehouse_type'   => $row[4],
                                   'seq'              => $row[5],  
                                   'company_id'       => TM::getCurrentCompanyId(), 
                                   'store_id'         => TM::getCurrentStoreId(), 
                                   'description'      => "", 
                                   'is_active'        => $row[6], 
                                   'created_at'       => date('Y-m-d H:i:s',strtotime($row[7])), 
                                   'created_by'       => $row[8], 
                                   'updated_at'       => date('Y-m-d H:i:s',strtotime($row[9])),
                                   'updated_by'       => $row[10]  
                                   
                              ];
                              $codeUsed[]=$row[2];
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
          $queryHeader = "INSERT INTO `warehouses` (" .
               "`code`, " .
               "`name`, " .
               "`shop_id`, " .
               "`warehouse_type`, " .
               "`seq`, " .
               "`company_id`, " .
               "`store_id`, " .
               "`is_active`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";
          $now = date('Y-m-d H:i:s', time());
          $me = TM::getCurrentUserId();
          $store = TM::getCurrentStoreId();
          $company = TM::getCurrentCompanyId();
          $queryContent = "";
          
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
            $description = !empty($datum['description']) ? "'{$datum['description']}'":"";
            $address = !empty($datum['address'])? "'{$datum['address']}'": "NULL";
            $updated_at = $datum['updated_at'];
            $updated_by = $datum['updated_by'];
            $queryContent .=
                    "(" .
                    $pdo->quote($datum['code']) . "," .
                    $pdo->quote($datum['name']) . "," .
                    $pdo->quote($datum['shop_id']) . "," .
                    $pdo->quote($datum['seq']) . "," .
                    $pdo->quote($datum['warehouse_type']) . "," .
                    $pdo->quote($datum['company_id']) . "," .
                    $pdo->quote($datum['store_id']) . "," .
                    $pdo->quote($datum['is_active']) . "," .
                    $pdo->quote($datum['created_at']) . "," .
                    $pdo->quote($datum['created_by']) . "," .
                    $pdo->quote($datum['updated_at']) . "," .
                    $pdo->quote($datum['updated_by']) . "),";
          }
          if (!empty($queryContent)) {
               $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`code`= values(`code`), " .
                    "`name`= values(`name`), " .
                    "`shop_id` = values(`shop_id`), " .
                    "`seq` = values(`seq`), " .
                    "`warehouse_type` = values(`warehouse_type`), " .
                    "`is_active` = values(`is_active`), " .
                    "`company_id` = values(`company_id`), " .
                    "`store_id` = values(`store_id`), " .
                    "updated_at='$updated_at', updated_by=$updated_by";
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
