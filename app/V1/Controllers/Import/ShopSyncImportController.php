<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\User;
use App\Jobs\Import\ImportUserJob;
use App\Supports\Message;
use App\TM;
use App\V1\Models\ShopSyncModel;
use App\V1\Models\UserModel;
// use App\V1\Models\UserGroupModel;
use Box\Spout\Reader\ReaderFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ShopSyncImportController
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
               0 => "SHOP_ID",
               1 => "SHOP_CODE",
               2 => "SHOP_NAME",
               3 => "FULL_NAME",
               4 => "PARENT_SHOP_ID",
               5 => "AREA_CODE",
               6 => "PHONE",
               7 => "MOBIPHONE",
               8 => "SHOP_TYPE_ID",
               9 => "SHOP_TYPE",
               10 => "SHOP_CHANNEL",
               11 => "EMAIL",
               12 => "ADDRESS",
               13 => "TAX_NUM",
               14 => "INVOICE_NUMBER_ACCOUNT",
               15 => "INVOICE_BANK_NAME",
               16 => "STATUS",
               17 => "LAT",
               18 => "LNG",
               19 => "FAX",
               20 => "AREA_ID",
               21 => "BILL_TO",
               22 => "SHIP_TO",
               23 => "CONTACT_NAME",
               24 => "DISTANCE_ORDER",
               25 => "DISTANCE_TRAINING",
               26 => "SHOP_LOCATION",
               27 => "UNDER_SHOP",
               28 => "PRICE_TYPE",
               29 => "ORGANIZATION_ID",
               30 => "ABBREVIATION",
               31 => "DEBIT_DATE_LIMIT",
               32 => "ORDER_VIEW",
               33 => "SALE_AREA",
               34 => "NAME_TEXT",
               35 => "CREATE_DATE",
               36 => "CREATE_USER",
               37 => "UPDATE_DATE",
               38 => "UPDATE_USER",
          ];
          $this->_required = [
               0 => trim($this->_header[0]),
               4 => trim($this->_header[4]),
               8 => trim($this->_header[8]),
               20 => trim($this->_header[20]),
               29 => trim($this->_header[29]),
          ];
          $this->_duplicate = [
               1 => trim($this->_header[1]),
          ];
          $this->_notExisted = [];
          $this->_number = [
               4 => trim($this->_header[4]),
               5 => trim($this->_header[5]),
               7 => trim($this->_header[7]),
               8 => trim($this->_header[8]),
               13 => trim($this->_header[13]),
               16 => trim($this->_header[16]),
               20 => trim($this->_header[20]),
               24 => trim($this->_header[24]),
               25 => trim($this->_header[25]),
               29 => trim($this->_header[29]),
               32 => trim($this->_header[32]),
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
          $fileName = "Import-Shop-Sync-By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

          $file = $input['file'];
          $path = storage_path("Imports");
          if (!file_exists($path)) {
               mkdir($path, 0777, true);
          }

          $existedChk = [];
          $company_id = TM::getCurrentCompanyId();
          $company_code = TM::getCurrentCompanyCode();
          $store_id = TM::getCurrentStoreId();
          try {
               $dataCheck = [
                    'SHOP_CODE'         => [
                         'fields' => [
                              'shop_code',
                              'id',
                              'shop_code',
                         ],
                         'model'  => new ShopSyncModel(),
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
                              0  => $row[0] ?? "",
                              1  => $row[1] ?? "",
                              2  => $row[2] ?? "",
                              3  => $row[3] ?? "",
                              4  => $row[4] ?? "",
                              5  => $row[5] ?? "",
                              6  => $row[6] ?? "",
                              7  => $row[7] ?? "",
                              8  => $row[8] ?? "",
                              9  => $row[9] ?? "",
                              10 => $row[10] ?? "",
                              11 => $row[11] ?? "",
                              12 => $row[12] ?? "",
                              13 => $row[13] ?? "",
                              14 => $row[14] ?? "",
                              15 => $row[15] ?? "",
                              16 => $row[16] ?? "",
                              17 => $row[17] ?? "",
                              18 => $row[18] ?? "",
                              19 => $row[19] ?? "",
                              20 => $row[20] ?? "",
                              21 => $row[21] ?? "",
                              22 => $row[22] ?? "",
                              23 => $row[23] ?? "",
                              24 => $row[24] ?? "",
                              25 => $row[25] ?? "",
                              26 => $row[26] ?? "",
                              27 => $row[27] ?? "",
                              28 => $row[28] ?? "",
                              29 => $row[29] ?? "",
                              30 => $row[30] ?? "",
                              31 => $row[31] ?? "",
                              32 => $row[32] ?? "",
                              33 => $row[33] ?? "",
                              34 => $row[34] ?? "",
                              35 => $row[35] ?? "",
                              36 => $row[36] ?? "",
                              37 => $row[37] ?? "",
                              38 => $row[38] ?? "",
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
                                        $row[] = "Required " . $this->_header[1];
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
                                   "shop_id"                => $row[0],
                                   "shop_code"              => $row[1],
                                   "shop_name"              => $row[2],
                                   "full_name"              => $row[3],
                                   "parent_shop_id"         => $row[4],
                                   "area_code"              => $row[5],
                                   "phone"                  => $row[6],
                                   "mobiphone"              => $row[7],
                                   "shop_type_id"           => $row[8],
                                   "shop_type"              => $row[9],
                                   "shop_channel"           => $row[10],
                                   "email"                  => $row[11],
                                   "address"                => $row[12],
                                   "tax_num"                => $row[13],
                                   "invoice_number_account" => $row[14],
                                   "invoice_bank_name"      => $row[15],
                                   "status"                 => $row[16],
                                   "lat"                    => $row[17],
                                   "lng"                    => $row[18],
                                   "fax"                    => $row[19],
                                   "area_id"                => $row[20],
                                   "bill_to"                => $row[21],
                                   "ship_to"                => $row[22],
                                   "contact_name"           => $row[23],
                                   "distance_order"         => $row[24],
                                   "distance_training"      => $row[25],
                                   "shop_location"          => $row[26],
                                   "under_shop"             => $row[27],
                                   "price_type"             => $row[28],
                                   "organization_id"        => $row[29],
                                   "abbreviation"           => $row[30],
                                   "debit_date_limit"       => $row[31],
                                   "order_view"             => $row[32],
                                   "sale_area"              => $row[33],
                                   "name_text"              => $row[34],
                                   "create_date"            => $row[35] ? (\DateTime::createFromFormat('d/m/Y H.i.s', $row[35])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[35]))) : "",
                                   "create_user"            => $row[36],
                                   "update_date"            => $row[37] ? (\DateTime::createFromFormat('d/m/Y H.i.s', $row[37])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[37]))) : "",
                                   "update_user"            => $row[38],

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
          // Shop_syncs
          $queryHeader = "INSERT INTO `shop_syncs` (" .
               "`shop_id`, " .
               "`shop_code`, " .
               "`shop_name`, " .
               "`full_name`, " .
               "`parent_shop_id`, " .
               "`area_code`, " .
               "`phone`, " .
               "`mobiphone`, " .
               "`shop_type_id`, " .
               "`shop_type`, " .
               "`shop_channel`, " .
               "`email`, " .
               "`address`, " .
               "`tax_num`, " .
               "`invoice_number_account`, " .
               "`invoice_bank_name`, " .
               "`status`, " .
               "`lat`, " .
               "`lng`, " .
               "`fax`, " .
               "`area_id`, " .
               "`bill_to`, " .
               "`ship_to`, " .
               "`contact_name`, " .
               "`distance_order`, " .
               "`distance_training`, " .
               "`shop_location`, " .
               "`under_shop`, " .
               "`price_type`, " .
               "`organization_id`, " .
               "`abbreviation`, " .
               "`debit_date_limit`, " .
               "`order_view`, " .
               "`sale_area`, " .
               "`name_text`, " .
               "`create_date`, " .
               "`create_user`, " .
               "`update_date`, " .
               "`update_user`, " .
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
               $full_name               = !empty($datum['full_name']) ? "'{$datum['full_name']}'" : "NULL";
               $phone                   = !empty($datum['phone']) ? "'{$datum['phone']}'" : "NULL";
               $mobilephone             = !empty($datum['mobilephone']) ? "'{$datum['mobilephone']}'" : "NULL";
               $shop_type               = !empty($datum['shop_type']) ? "'{$datum['shop_type']}'" : "NULL";
               $shop_channel            = !empty($datum['shop_channel']) ? "'{$datum['shop_channel']}'" : "NULL";
               $email                   = !empty($datum['email']) ? "'{$datum['email']}'" : "NULL";
               $address                 = !empty($datum['address']) ? "'{$datum['address']}'" : "NULL";
               $tax_number              = !empty($datum['tax_number']) ? "'{$datum['tax_number']}'" : "NULL";
               $invoice_number_account  = !empty($datum['invoice_number_account']) ? "'{$datum['invoice_number_account']}'" : "NULL";
               $invoice_bank_name       = !empty($datum['invoice_bank_name']) ? "'{$datum['invoice_bank_name']}'" : "NULL";
               $lat                     = !empty($datum['lat']) ? "'{$datum['lat']}'" : "NULL";
               $lng                     = !empty($datum['lng']) ? "'{$datum['lng']}'" : "NULL";
               $fax                     = !empty($datum['fax']) ? "'{$datum['fax']}'" : "NULL";
               $bill_to                 = !empty($datum['bill_to']) ? "'{$datum['bill_to']}'" : "NULL";
               $ship_to                 = !empty($datum['ship_to']) ? "'{$datum['ship_to']}'" : "NULL";
               $contact_name            = !empty($datum['contact_name']) ? "'{$datum['contact_name']}'" : "NULL";
               $shop_location           = !empty($datum['shop_location']) ? "'{$datum['shop_location']}'" : "NULL";
               $under_shop              = !empty($datum['under_shop']) ? "'{$datum['under_shop']}'" : "NULL";
               $price_type              = !empty($datum['price_type']) ? "'{$datum['price_type']}'" : "NULL";
               $abbreviation            = !empty($datum['abbreviation']) ? "'{$datum['abbreviation']}'" : "NULL";
               $debit_date_limit        = !empty($datum['debit_date_limit']) ? "'{$datum['debit_date_limit']}'" : "NULL";
               $sale_area               = !empty($datum['sale_area']) ? "'{$datum['sale_area']}'" : "NULL";

               $queryContent
                    .= "(" .
                    $pdo->quote($datum['shop_id']) . "," .
                    $pdo->quote($datum['shop_code']) . "," .
                    $pdo->quote($datum['shop_name']) . "," .
                    $full_name . "," .
                    $pdo->quote($datum['parent_shop_id']) . "," .
                    $pdo->quote($datum['area_code']) . "," .
                    $phone . "," .
                    $mobilephone  . "," .
                    $pdo->quote($datum['shop_type_id']) . "," .
                    $shop_type . "," .
                    $shop_channel . "," .
                    $email . "," .
                    $address . "," .
                    $tax_number . "," .
                    $invoice_number_account . "," .
                    $invoice_bank_name . "," .
                    $pdo->quote($datum['status']) . "," .
                    $lat . "," .
                    $lng . "," .
                    $fax . "," .
                    $pdo->quote($datum['area_id']) . "," .
                    $bill_to . "," .
                    $ship_to . "," .
                    $contact_name . "," .
                    $pdo->quote($datum['distance_order']) . "," .
                    $pdo->quote($datum['distance_training']) . "," .
                    $shop_location . "," .
                    $under_shop . "," .
                    $price_type . "," .
                    $pdo->quote($datum['organization_id']) . "," .
                    $abbreviation . "," .
                    $debit_date_limit . "," .
                    $pdo->quote($datum['order_view']) . "," .
                    $sale_area . "," .
                    $pdo->quote($datum['name_text']) . "," .
                    $pdo->quote($datum['create_date']) . "," .
                    $pdo->quote($datum['create_user']) . "," .
                    $pdo->quote($datum['update_date']) . "," .
                    $pdo->quote($datum['update_user']) . "," .

                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
          }
          if (!empty($queryContent)) {
               $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`shop_id`= values(`shop_id`), " .
                    "updated_at='$now', updated_by=$me";
               DB::statement($queryUpdate);
          }
          // print_r($queryUpdate);die;
          //=========================================================================================
          // Stores
          $queryHeaderStore = "INSERT INTO `stores` (" .
               "`code`," .
               "`name`," .
               "`contact_phone`," .
               "`lat`," .
               "`long`," .
               "`address`, " .
               "`company_id`, " .
               "`email`, " .
               "`is_active`, " .
               "`deleted`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";
          $now = date('Y-m-d H:i:s', time());
          $me = TM::getCurrentUserId();
          $store = TM::getCurrentStoreId();
          $company = TM::getCurrentCompanyId();
          $queryContentStore = "";
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
               $email               = !empty($datum['email']) ? "'{$datum['email']}'" : "NULL";
               $address             = !empty($datum['address']) ? "'{$datum['address']}'" : "NULL";
               $phone               = !empty($datum['phone']) ? "'{$datum['phone']}'" : 0;
               $mobilephone         = !empty($datum['mobilephone']) ? "'{$datum['mobilephone']}'" : "NULL";

               $queryContentStore
                    .= "(" .
                    $pdo->quote($datum['shop_code']) . "," .
                    $pdo->quote($datum['shop_name']) . "," .
                    $phone  . "," .
                    $lat . "," .
                    $lng . "," .
                    $address . "," .
                    $pdo->quote($company) . "," .
                    $email . "," .
                    $pdo->quote(1) . "," .
                    $pdo->quote(0) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
          }
          if (!empty($queryContentStore)) {
               $queryUpdateStore = $queryHeaderStore . (trim($queryContentStore, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`code`= values(`code`), " .
                    "`name`= values(`name`), " .
                    "`lat`= values(`lat`), " .
                    "`long` = values(`long`)," .
                    "`address` = values(`address`)," .
                    "`company_id` = values(`company_id`)," .
                    "`email` = values(`email`)," .
                    "updated_at='$now', updated_by=$me";
               DB::statement($queryUpdateStore);
          }
          //=========================================================================================
          //Profile

          // $allUserId = User::select(['id', 'code'])
          //      ->whereIn('code', array_values($userImported))
          //      ->pluck('id', 'code')
          //      ->toArray();
          // $queryHeaderProfile = "INSERT INTO `profiles` (" .
          //      "`user_id`, " .
          //      "`email`, " .
          //      "`first_name`, " .
          //      "`last_name`, " .
          //      "`full_name`, " .
          //      "`address`, " .
          //      "`phone`, " .
          //      "`birthday`, " .
          //      "`gender`, " .
          //      "`is_active`, " .
          //      "`deleted`, " .
          //      "`created_at`, " .
          //      "`created_by`, " .
          //      "`updated_at`, " .
          //      "`updated_by`) VALUES ";
          // $now = date('Y-m-d H:i:s', time());
          // $me = TM::getCurrentUserId();
          // $store = TM::getCurrentStoreId();
          // $queryContentProfile = "";
          // /** @var \PDO $pdo */
          // $pdo = DB::getPdo();
          // foreach ($data as $datum) {
          //      $idno                = !empty($datum['idno']) ? "'{$datum['idno']}'" : "NULL";
          //      $idno_place          = !empty($datum['idno_place']) ? "'{$datum['idno_place']}'" : "NULL";
          //      $idno_date           = !empty($datum['idno_date']) ? "'{$datum['idno_date']}'" : "NULL";
          //      $email               = !empty($datum['email']) ? "{$datum['email']}" : "NULL";
          //      $area_id             = !empty($datum['area_id']) ? "'{$datum['area_id']}'" : "NULL";
          //      $street              = !empty($datum['street']) ? "'{$datum['street']}'" : "NULL";
          //      $housenumber         = !empty($datum['housenumber']) ? "'{$datum['housenumber']}'" : "NULL";
          //      $address             = !empty($datum['address']) ? "'{$datum['address']}'" : "NULL";
          //      $phone               = !empty($datum['phone']) ? "'{$datum['phone']}'" : "NULL";
          //      $mobilephone         = !empty($datum['mobilephone']) ? "'{$datum['mobilephone']}'" : "NULL";
          //      $start_working_day   = !empty($datum['start_working_day']) ? "'{$datum['start_working_day']}'" : "NULL";
          //      $education           = !empty($datum['education']) ? "'{$datum['education']}'" : "NULL";
          //      $position            = !empty($datum['position']) ? "'{$datum['position']}'" : "NULL";
          //      $birthday            = !empty($datum['birthday']) ? "'{$datum['birthday']}'" : "NULL";
          //      $birthday_place      = !empty($datum['birthday_place']) ? "'{$datum['birthday_place']}'" : "NULL";
          //      $temp                = $datum['staff_code'];
          //      $userId              = $allUserId[$temp];
          //      // dd($userId);
          //      if (empty($allUserId[$temp])) {
          //           continue;
          //      }
          //      $queryContentProfile
          //           .= "(" .
          //           $pdo->quote($userId) . "," .
          //           $pdo->quote($email) . "," .
          //           $pdo->quote($datum['staff_name']) . "," . //full_name
          //           $pdo->quote($datum['staff_name']) . "," . //full_name
          //           $pdo->quote($datum['staff_name']) . "," . //full_name
          //           $pdo->quote($address) . "," .
          //           $pdo->quote($phone) . "," .
          //           $pdo->quote($datum['birthday']) . "," . //birthday
          //           $pdo->quote($datum['gender']) . "," .
          //           $pdo->quote(1) . "," .
          //           $pdo->quote(0) . "," .
          //           $pdo->quote($now) . "," .
          //           $pdo->quote($me) . "," .
          //           $pdo->quote($now) . "," .
          //           $pdo->quote($me) . "), ";
          // }
          // if (!empty($queryContentProfile)) {
          //      $queryUpdateProfile = $queryHeaderProfile . (trim($queryContentProfile, ", ")) .
          //           " ON DUPLICATE KEY UPDATE " .
          //           "`user_id`= values(`user_id`), " .
          //           "`email`= values(`email`), " .
          //           "`address` = values(`address`), " .
          //           "`phone` = values(`phone`), " .
          //           "`is_active` = values(`is_active`), " .
          //           "`deleted` = values(`deleted`), " .
          //           "updated_at='$now', updated_by=$me";
          //      DB::statement($queryUpdateProfile);
          // }
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

          $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-STAFF-SYNC-(warning).csv", 'w');
          fwrite($csv_handler, $csv);
          fclose($csv_handler);

          return true;
     }
}
