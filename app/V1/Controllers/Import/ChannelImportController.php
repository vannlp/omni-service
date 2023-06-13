<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\Area;
use App\Jobs\Import\ImportUserJob;
use App\Supports\Message;
use App\TM;
use App\V1\Models\ChannelModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChannelImportController
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
               0 => "CHANNEL_TYPE_ID",
               1 => "CHANNEL_TYPE_CODE",
               2 => "CHANNEL_TYPE_NAME",
               3 => "PARENT_CHANNEL_TYPE_ID",
               4 => "STATUS",
               5 => "TYPE",
               6 => "SKU",
               7 => "SALE_AMOUNT",
               8 => "OBJECT_TYPE",
              


          ];
          $this->_required = [
               1 => trim($this->_header[1]),
               2 => trim($this->_header[2]),
          ];
          $this->_duplicate = [
               1 => 'code',
               2 => 'name', 
          ];
          $this->_notExisted = [
          ];
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
          try {
               $dataCheck = [
                    'code'         => [
                         'fields' => ['code', 'id', 'name','code'],
                         'model'  => new ChannelModel(),
                     
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
                              6 => $row[6] ?? "",
                              7 => $row[7] ?? "",
                              8 => $row[8] ?? "",
                            
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

                            //   Check Duplicate Code
                              $tmp = strtoupper($row[2]);
                              if (in_array($tmp, $phoneUsed)) {
                                   $row[] = "Duplicate Phone" . $tmp;
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
                                   'name'                     => $row[2],
                                   'code'                     => $row[1],
                                   'status'                   => $row[4], 
                                   'parent_channel_type_id'   => $row[3],
                                   'type'                     => $row[5],  
                                   'sku'                      => $row[6],
                                   'sale_amount'              => $row[7], 
                                   'object_type'              => $row[8], 
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
          $queryHeader = "INSERT INTO `channel_types` (" .
               "`code`, " .
               "`name`, " .
               "`parent_channel_type_id`, " .
               "`status`, " .
               "`type`, " .
               "`sku`, " .
               "`sale_amount`, " .
               "`object_type`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";
          $now = date('Y-m-d H:i:s', time());
          $me = TM::getCurrentUserId();
          // $store = TM::getCurrentStoreId();
          // $company = TM::getCurrentCompanyId();
          $queryContent = "";
          
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
            $object_type = !empty($datum['object_type'])? "{$datum['object_type']}": 0;
            $sale_amount = !empty($datum['sale_amount'])? "{$datum['sale_amount']}": 0;
            $parent_channel_type_id = !empty($datum['parent_channel_type_id'])? "{$datum['parent_channel_type_id']}": 0;
            $queryContent .=
                    "(" .
                    $pdo->quote($datum['code']) . "," .
                    $pdo->quote($datum['name']) . "," .
                    $pdo->quote($parent_channel_type_id) . "," .
                    $pdo->quote($datum['status']) . "," .
                    $pdo->quote($datum['type']) . "," .
                    $pdo->quote($datum['sku']) . "," .
                    $pdo->quote($sale_amount) . "," .
                    $pdo->quote($object_type) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "),";
                    
          }
          
          if (!empty($queryContent)) {
               $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`code`                   = values(`code`), " .
                    "`name`                   = values(`name`), " .
                    "`parent_channel_type_id` = values(`parent_channel_type_id`), " .
                    "`status`                 = values(`status`), " .
                    "`type`                   = values(`type`), " .
                    "`sku`                    = values(`sku`), " .
                    "`sale_amount`            = values(`sale_amount`) , " .
                    "`object_type`            = values(`object_type`), " .
                    "updated_at='$now', updated_by=$me";

                    

               DB::statement($queryUpdate);
          }
        //   die();
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
