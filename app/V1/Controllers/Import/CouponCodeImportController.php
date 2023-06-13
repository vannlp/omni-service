<?php

namespace App\V1\Controllers\Import;

use App\CouponCode;
use App\CouponCodes;
use App\Supports\Message;
use App\TM;
use App\V1\Models\CouponCodeModel;
use App\V1\Models\CouponCodesModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponCodeImportController
{
     protected $_header;
     protected $_required;
     protected $_duplicate;
     protected $_notExisted;
     protected $_number;
     protected $_date;

     public function __construct()
     {
          $this->_header     = [
               0  => "Mã giảm giá",
               1  => "Số tiền giảm",
               2  => "Loại giảm giá",
               3  => "Giới hạn giảm",
               4  => "Mã khách hàng",
               5  => "Từ ngày",
               6  => "Đến ngày"
          ];
          $this->_required   = [
               0  => trim($this->_header[0]),
               1  => trim($this->_header[1]),
               2  => trim($this->_header[2]),
          ];
          $this->_duplicate  = [
               // 0 => 'code',
               // 3 => 'phone',
          ];
          $this->_notExisted = [];
          $this->_number     = [];
          $this->_date       = [
               5  => trim($this->_header[5]),
               6  => trim($this->_header[6]),
          ];
     }


     public function import($id, Request $request)
     {
          ini_set('max_execution_time', 6000);
          ini_set('memory_limit', '300M');

          $input = $request->all();
          $input['id'] = $id;

          if (empty($input['file'])) {
               return response()->json([
                    'status'  => 'error',
                    'message' => Message::get("V001", 'File Import')
               ], 400);
          }

          $fileName = explode(".", $_FILES["file"]["name"]);
          $fileExt  = end($fileName);
          if (empty($fileExt) || !in_array($fileExt, ['xlsx', 'csv', 'ods'])) {
               return response()->json([
                    'status'  => 'error',
                    'message' => Message::get('regex', 'File Import'),
               ], 400);
          }


          $errors   = [];
          $warnings = [];
          $valids   = [];
          $countRow = 0;
          $error    = false;
          $empty    = true;
          $fileName = "Import-Coupon-By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

          $file = $input['file'];
          $path = storage_path("Imports");

          if (!file_exists($path)) {
               mkdir($path, 0777, true);
          }

          $existedChk = [];
          try {
               $dataCheck = [
                    'code'      => [
                         'fields' => ['code', 'id', 'code'],
                         'model'  => new CouponCodeModel(),
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
               $codeUsed   = [];
               $phoneUsed  = [];
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
                         ];

                         $totalLines++;
                         if ($totalLines > 30001) {
                              return response()->json([
                                   'status'  => 'error',
                                   'message' => Message::get("V013", Message::get("data_row"), 30000),
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
                              $err = "";

                              // Check Required
                              foreach ($this->_required as $key => $name) {
                                   if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                                        $err .= "Required " . $this->_header[$key] . "\"";
                                        continue;
                                   }
                              }
                              
                              //   Check Not Existed
                              foreach ($this->_notExisted as $key => $name) {
                                   if (empty($row[$key])) {
                                        continue;
                                   }

                                   $val = trim(strtoupper($row[$key]));
                                   if (!isset($existedChk[$name][$val])) {
                                        $err .= $val . " Not Existed " . $this->_header[$key];
                                   }
                              }

                              // Check Duplicated
                              // foreach ($this->_duplicate as $key => $name) {
                              //      if (empty($row[$key])) {
                              //           continue;
                              //      }
                              //      $val = trim(strtoupper($row[$key]));
                              //      if (!empty($existedChk[$name][$val])) { 
                              //           $err .= "[$name : $val đã tồn tại, vui lòng chọn mã khác]";
                              //      }
                              // }

                              foreach ($this->_date as $key => $name) {
                                   if (empty($row[$key])) {
                                       continue;
                                   }
                     
                                   if (gettype( $row[$key] ) == 'object') {
                                        $row[$key] = date_format($row[$key],"Y-m-d H:i:s");
                                        continue;
                                   
                                   }
                                   if (gettype( $row[$key] ) == 'string') {
                                        $row[$key] = date('Y-m-d H:i:s', strtotime(strtr($row[$key], '/', '-')));
                                        continue;
                                   }
                                   $err .= "[" . $this->_header[$key] . " must be a date!]";
                              }

                              if ($err) {
                                   $row[]      = $err;
                                   $warnings[] = array_map(function ($r) {
                                        return $r ;
                                   }, $row);
                                   continue;
                              }

                              if(!empty($row[0])){

                                   $check_coupon = CouponCodes::model()->where('code', $row[0])
                                   ->where('is_active',1)
                                   ->whereNotNull('order_used')
                                   ->exists();

                                   if($check_coupon){
                                        continue;
                                   }
                              }

                              $valids[] = [
                                   'coupon_id'          => $input['id'],
                                   'code'               => $row[0],
                                   'discount'           => $row[1],
                                   'type'               => COUPON_CODE_TYPE[$row[2]],
                                   'limit_discount'     => $row[3],
                                   'user_code'      => $row[4] ?? NULL,
                                   'start_date'     => $row[5] ?? NULL,
                                   'end_date'       => $row[6] ?? NULL,
                              ];

                              $countRow++;
                         }
                    }
                    // print_r($valids);die;
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
          $now       = date('Y-m-d H:i:s', time());
          $me        = TM::getCurrentUserId();
          $companyId = TM::getIDP()->company_id;
          $storeId   = TM::getIDP()->store_id;

          DB::beginTransaction();
          $queryHeader = "INSERT INTO `coupon_codes` (" .
               "`coupon_id`, " .
               "`code`, " .
               "`discount`, " .
               "`type`, " .
               "`limit_discount`, " .
               "`user_code`, " .
               "`start_date`, " .
               "`end_date`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";

          $userImported = [];
          $queryContent = "";
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
               $queryContent        .= "(" .
                    $pdo->quote($datum['coupon_id']) . "," .
                    $pdo->quote($datum['code']) . "," .
                    $pdo->quote($datum['discount']) . "," .
                    $pdo->quote($datum['type']) . "," .
                    $pdo->quote($datum['limit_discount']) . "," .
                    (!empty($datum['user_code']) ? $pdo->quote($datum['user_code']) : "NULL") . "," .
                    (!empty($datum['start_date']) ? $pdo->quote($datum['start_date']) : "NULL"). "," .
                    (!empty($datum['end_date']) ? $pdo->quote($datum['end_date']) : "NULL") . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
          }
          if (!empty($queryContent)) {
               $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`code`= values(`code`), " .
                    "`discount`= values(`discount`), " .
                    "`type`= values(`type`), " .
                    "`limit_discount`= values(`limit_discount`), " .
                    "updated_at='$now', updated_by=$me";
               DB::statement($queryUpdate);
          }
          DB::commit();
     }

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

          $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-COUPON-(warning).csv", 'w');
          fwrite($csv_handler, $csv);
          fclose($csv_handler);

          return true;
     }
}
