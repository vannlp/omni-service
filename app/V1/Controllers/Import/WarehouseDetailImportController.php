<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;
use App\Product;
use App\Supports\Message;
use App\TM;
use App\V1\Models\WarehouseDetailModeL;
use App\Warehouse;
use App\WarehouseDetail;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseDetailImportController
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
               0 => "STOCK_TOTAL_ID",
               1=>  "WAREHOUSE_ID",
               2 => "OBJECT_ID",
               3 => "OBJECT_TYPE",
               4 => "PRODUCT_ID",
               5 => "QUANTITY",
               6 => "AVAILABLE_QUANTITY",
               7 => "APPROVED_QUANTITY",
               8 => "UPLOAD_DATE",
               9 => "SEQ",
               10 => "WAREHOUSE_TYPE",
               11 => "DESCR",
               12 => "CREATE_USER",
               13 => "UPDATE_USER",
               14 => "CREATE_DATE",
               15 => "UPDATE_DATE",
          ];
          $this->_required = [
               0 => trim($this->_header[0]),
               1 => trim($this->_header[1]),
               2 => trim($this->_header[2]),
               3 => trim($this->_header[3]),
               4 => trim($this->_header[4]),
         
              

          ];
          $this->_duplicate = [];
          $this->_notExisted = [
               1 => 'warehouse_id',
               2 => 'object_id',
               3 => 'object_type',
               4 => 'product_id',
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
          $company_id = TM::getCurrentCompanyId();
          $store_id = TM::getCurrentStoreId();
        
          try {
               $dataCheck = [
                    'warehouse_id'=> [
                         'fields' => ['warehouse_id','warehouse_code' ,'warehouse_id'],
                         'model'  => new WarehouseDetailModel(),
                         // 'where' => ' `company_id` = ' . $company_id ,
                    ],
                    'product_id'=> [
                         'fields' => ['product_id','product_code' ,'product_id'],
                         'model'  => new WarehouseDetailModel(),
                         // 'where' => ' `company_id` = ' . $company_id ,
                    ],
                    'object_id'=> [
                         'fields' => ['object_id','object_type' ,'object_id'],
                         'model'  => new WarehouseDetailModel(),
                         // 'where' => ' `company_id` = ' . $company_id ,
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
               $code = []; 
               // dd($reader->getSheetIterator());
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
                              9 => $row[9] ?? "",
                             10 => $row[10] ??"",
                             11 => $row[11] ??"",
                             12 => $row[12] ??"",
                             13 => $row[13] ??"",
                             14 => $row[14] ??"",
                             15 => $row[15] ??"",
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
                              $err = "";
                               // Check Existed
                              foreach ($this->_notExisted as $key => $name) {
                                   if (empty($row[$key])) {
                                       continue;
                                   }
       
                                   $val = trim(strtoupper($row[$key]));
                                   if (!isset($existedChk[$name][$val])) {
                                       $err .= $val . " Not Existed " . $this->_header[$key];
                                   }
                               }
                    
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
                            $product = Product::model()->where('id', $row[4])->first();
                            $warehouse = Warehouse::model()->where('id', $row[1])->first();
                         //    print_r($product->unit->name);die;
                            $valids[] = [
                                   'product_id'         => $row[4],
                                   'product_code'       => $product->code,
                                   'product_name'       => $product->name,
                                   'warehouse_id'       => $row[1],
                                   'warehouse_code'     => $warehouse->code,
                                   'warehouse_name'     => $warehouse->name,
                                   'unit_id'            => $product->unit_id,
                                   'unit_code'          => $product->unit->code,
                                   'unit_name'          => $product->unit->name,
                                   'batch_id'           => "",
                                   'batch_name'         => "",
                                   'batch_code'         => "",
                                   'object_id'          => $row[2], 
                                   'object_type'        => $row[3], 
                                   'appoved_quantity'   => $row[7], 
                                   'available_quantity' => $row[6], 
                                   'seq'                => $row[9], 
                                   'warehouse_type'     => $row[10],
                                   'descr'              => $row[11], 
                                   'upload_date'        => $row[8],
                                   'company_id'         => TM::getCurrentCompanyId(),  
                                   'quantity'           => $row[5], 
                                   'product_type'       =>"",
                                   'exp'                =>"",
                                   'price'              =>"",
                                   'variant_id'         =>"", 
                                   'created_at'         => date('Y-m-d H:i:s',strtotime($row[14])), 
                                   'created_by'         => $row[12], 
                                   'updated_at'         => date('Y-m-d H:i:s',strtotime($row[15])),
                                   'updated_by'         => $row[13]                            
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
          $queryHeader = "INSERT INTO `warehouse_details` (" .
               "`product_id`, " .
               "`product_code`, " .
               "`product_name`, " .
               "`warehouse_id`, " .
               "`warehouse_code`, " .
               "`warehouse_name`, " .
               "`unit_id`, " .
               "`unit_code`, " .
               "`unit_name`, " .
               "`object_id`, " .
               "`object_type`, " .
               "`appoved_quantity`, " .
               "`available_quantity`, " .
               "`seq`, " .
               "`warehouse_type`, " .
               "`descr`, " .
               "`upload_date`, " .
               "`company_id`, " .
               "`quantity`, " .
               "`price`, " .
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
          //   $description = !empty($datum['description']) ? "'{$datum['description']}'":"";
          //   $address = !empty($datum['address'])? "'{$datum['address']}'": "NULL";
            $updated_at = $datum['updated_at'];
            $updated_by = $datum['updated_by'];
            $queryContent .=
                    "(" .
                    $pdo->quote($datum['product_id']) . "," .
                    $pdo->quote($datum['product_code']) . "," .
                    $pdo->quote($datum['product_name']) . "," .
                    $pdo->quote($datum['warehouse_id']) . "," .
                    $pdo->quote($datum['warehouse_code']) . "," .
                    $pdo->quote($datum['warehouse_name']) . "," .
                    $pdo->quote($datum['unit_id']) . "," .
                    $pdo->quote($datum['unit_code']) . "," .
                    $pdo->quote($datum['unit_name']) . "," .
                    $pdo->quote($datum['object_id']) . "," .
                    $pdo->quote($datum['object_type']) . "," .
                    $pdo->quote($datum['appoved_quantity']) . "," .
                    $pdo->quote($datum['available_quantity']) . ",".
                    $pdo->quote($datum['seq']) . "," .
                    $pdo->quote($datum['warehouse_type']) . ",".
                    $pdo->quote($datum['descr']) . "," .
                    $pdo->quote($datum['upload_date']) . ",".
                    $pdo->quote($datum['company_id']) . "," .
                    $pdo->quote($datum['quantity']) . "," .
                    $pdo->quote(0) . "," .
                    $pdo->quote($datum['created_at']) . "," .
                    $pdo->quote($datum['created_by']) . "," .
                    $pdo->quote($datum['updated_at']) . "," .
                    $pdo->quote($datum['updated_by']) . "),";
          }
          if (!empty($queryContent)) {
               $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`product_id`       = values(`product_id`), " .
                    "`product_code`     = values(`product_code`), " .
                    "`product_name`     = values(`product_name`), " .
                    "`warehouse_id`     = values(`warehouse_id`), " .
                    "`warehouse_code`   = values(`warehouse_code`), " .
                    "`warehouse_name`   = values(`warehouse_name`), " .
                    "`unit_id`          = values(`unit_id`), " .
                    "`unit_code`        = values(`unit_code`), " .
                    "`unit_name`        = values(`unit_name`), " .
                    "`object_id`        = values(`object_id`), " .
                    "`object_type`      = values(`object_type`), " .
                    "`appoved_quantity` = values(`appoved_quantity`), " .
                   "`available_quantity`= values(`available_quantity`), " .
                    "`seq`              = values(`seq`), " .
                    "`warehouse_type`   = values(`warehouse_type`), " .
                    "`descr`            = values(`descr`), " .
                    "`upload_date`      = values(`upload_date`), " .
                    "`company_id`       = values(`company_id`), " .
                    "`quantity`         = values(`quantity`), " .
                    "`price`         = values(`price`), " .
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
