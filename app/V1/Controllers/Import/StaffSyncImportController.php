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
use App\V1\Models\StaffSyncModel;
use App\V1\Models\UserModel;
// use App\V1\Models\UserGroupModel;
use Box\Spout\Reader\ReaderFactory;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class StaffSyncImportController
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
               0 => "STAFF_ID",
               1 => "STAFF_CODE",
               2 => "STAFF_NAME",
               3 => "SHOP_ID",
               4 => "IDNO",
               5 => "IDNO_PLACE",
               6 => "IDNO_DATE",
               7 => "EMAIL",
               8 => "AREA_ID",
               9 => "STREET",
               10 => "HOUSENUMBER",
               11 => "ADDRESS",
               12 => "PHONE",
               13 => "MOBILEPHONE",
               14 => "GENDER",
               15 => "START_WORKING_DAY",
               16 => "EDUCATION",
               17 => "POSITION",
               18 => "BIRTHDAY",
               19 => "BIRTHDAY_PLACE",
               20 => "STAFF_TYPE_ID",
               21 => "STATUS",
               22 => "LAST_APPROVED_ORDER",
               23 => "LAST_ORDER",
               24 => "CREATE_DATE",
               25 => "CREATE_USER",
               26 => "UPDATE_DATE",
               27 => "UPDATE_USER",
               28 => "PLAN",
               29 => "UPDATE_PLAN",
               30 => "SKU",
               31 => "RESET_THRESHOLD",
               32 => "SALE_GROUP",
               33 => "NAME_TEXT",
               34 => "SALE_TYPE_CODE",
               35 => "CHECK_TYPE",
               36 => "ENABLE_CLIENT_LOG",
               37 => "WORK_STATE_CODE",
               38 => "ORGANIZATION_ID",
               39 => "ORDER_VIEW",
               40 => "EQUIPMENT",
               41 => "SALE_AREA",
               42 => "ROOT_AREA",
               43 => "PERMANENT",

          ];
          $this->_required = [
               1 => trim($this->_header[1]),
               3 => trim($this->_header[3]),
               8 => trim($this->_header[8]),
               20 => trim($this->_header[20]),
               21 => trim($this->_header[21]),
               38 => trim($this->_header[38]),
          ];
          $this->_duplicate = [
               1 => "STAFF_CODE",
               2 => "STAFF_NAME",
          ];
          $this->_notExisted = [
               // 1 => "STAFF_CODE",
          ];
          $this->_number = [
               0 => trim($this->_header[0]),
               3 => trim($this->_header[3]),
               14 => trim($this->_header[14]),
               21 => trim($this->_header[21]),
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
          $fileName = "Import-Staff-Sync-By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

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
                    'STAFF_CODE'         => [
                         'fields' => [
                              'staff_code',
                              'id',
                              'staff_code',
                         ],
                         'model'  => new StaffSyncModel(),
                    ],
                    'CODE' => [
                         'fields' => [
                              'code',
                              'id',
                              'code'
                         ],
                         'model'  => new UserModel(),
                    ],


               ];
               // print_r($dataCheck);die;
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
               // die;
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
                              39 => $row[39] ?? "",
                              40 => $row[40] ?? "",
                              41 => $row[41] ?? "",
                              42 => $row[42] ?? "",
                              43 => $row[43] ?? "",
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
                         // print_r($this->_required);die;
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
                                   "staff_id"            => $row[0],
                                   "staff_code"          => $row[1],
                                   "staff_name"          => $row[2],
                                   "shop_id"             => $row[3],
                                   "idno"                => $row[4],
                                   "idno_place"          => $row[5],
                                   "idno_date"           => $row[6],
                                   "email"               => $row[7],
                                   "area_id"             => $row[8],
                                   "street"              => $row[9],
                                   "housenumber"         => $row[10],
                                   "address"             => $row[11],
                                   "phone"               => $row[12],
                                   "mobilephone"         => $row[13],
                                   "gender"              => $row[14],
                                   "start_working_day"   => $row[15],
                                   "education"           => $row[16],
                                   "position"            => $row[17],
                                   "birthday"            => $row[18] ? (\DateTime::createFromFormat('d/m/Y H.i.s', $row[18])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[18]))) : date("Y-m-d H:i:s",time()),
                                   // ($row[6]) ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[6])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[6]))) : "",
                                   "birthday_place"      => $row[19],
                                   "staff_type_id"       => $row[20],
                                   "status"              => $row[21],
                                   "last_approved_order" => $row[22],
                                   "last_order"          => $row[23],
                                   "create_date"         => $row[24] ? (\DateTime::createFromFormat('d/m/Y H.i.s', $row[24])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[24]))) : time(),
                                   "create_user"         => $row[25],
                                   "update_date"         => $row[26] ? (\DateTime::createFromFormat('d/m/Y H.i.s', $row[26])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[26]))) :  time(),
                                   "update_user"         => $row[27],
                                   "plan"                => $row[28],
                                   "update_plan"         => $row[29],
                                   "sku"                 => $row[30],
                                   "reset_threshold"     => $row[31],
                                   "sale_group"          => $row[32],
                                   "name_text"           => $row[33],
                                   "sale_type_code"      => $row[34],
                                   "check_type"          => $row[35],
                                   "enable_client_log"   => $row[36],
                                   "work_state_code"     => $row[37],
                                   "organization_id"     => $row[38],
                                   "order_view"          => $row[39],
                                   "equipment"           => $row[40],
                                   "sale_area"           => $row[41],
                                   "root_area"           => $row[42],
                                   "permanent"           => $row[43],
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
          // Staff_syncs
          $queryHeader = "INSERT INTO `staff_syncs` (" .
               "`staff_id`," .
               "`staff_code`," .
               "`staff_name`," .
               "`shop_id`," .
               "`idno`," .
               "`idno_place`," .
               "`idno_date`," .
               "`email`," .
               "`area_id`," .
               "`street`," .
               "`housenumber`," .
               "`address`," .
               "`phone`," .
               "`mobilephone`," .
               "`gender`," .
               "`start_working_day`," .
               "`education`," .
               "`position`," .
               "`birthday`," .
               "`birthday_place`," .
               "`staff_type_id`," .
               "`status`," .
               "`last_approved_order`," .
               "`last_order`," .
               "`create_date`," .
               "`create_user`," .
               "`update_date`," .
               "`update_user`," .
               "`plan`," .
               "`update_plan`," .
               "`sku`," .
               "`reset_threshold`," .
               "`sale_group`," .
               "`name_text`," .
               "`sale_type_code`," .
               "`check_type`," .
               "`enable_client_log`," .
               "`work_state_code`," .
               "`organization_id`," .
               "`order_view`," .
               "`equipment`," .
               "`sale_area`," .
               "`root_area`," .
               "`permanent`," .
               "`store_id`, " .
               "`company_id`, " .
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
               // $create_date         = date('Y-m-d', strtotime($datum['create_date']));
               // $update_date         = date('Y-m-d', strtotime($datum['update_date']));
               $idno                = !empty($datum['idno']) ? "'{$datum['idno']}'" : "NULL";
               $idno_place          = !empty($datum['idno_place']) ? "'{$datum['idno_place']}'" : "NULL";
               $idno_date           = !empty($datum['idno_date']) ? "'{$datum['idno_date']}'" : "NULL";
               $email               = !empty($datum['email']) ? "'{$datum['email']}'" : "NULL";
               $area_id             = !empty($datum['area_id']) ? "'{$datum['area_id']}'" : "NULL";
               $street              = !empty($datum['street']) ? "'{$datum['street']}'" : "NULL";
               $housenumber         = !empty($datum['housenumber']) ? "'{$datum['housenumber']}'" : "NULL";
               $address             = !empty($datum['address']) ? "'{$datum['address']}'" : "NULL";
               $phone               = !empty($datum['phone']) ? "'{$datum['phone']}'" : "NULL";
               $mobilephone         = !empty($datum['mobilephone']) ? "'{$datum['mobilephone']}'" : "NULL";
               $start_working_day   = !empty($datum['start_working_day']) ? "'{$datum['start_working_day']}'" : "NULL";
               $education           = !empty($datum['education']) ? "'{$datum['education']}'" : "NULL";
               $position            = !empty($datum['position']) ? "'{$datum['position']}'" : "NULL";
               $birthday            = !empty($datum['birthday']) ? "'{$datum['birthday']}'" : "NULL";
               $birthday_place      = !empty($datum['birthday_place']) ? "'{$datum['birthday_place']}'" : "NULL";
               $last_approved_order = !empty($datum['last_approved_order']) ? "'{$datum['last_approved_order']}'" : "NULL";
               $last_order          = !empty($datum['last_order']) ? "'{$datum['last_order']}'" : "NULL";
               $plan                = !empty($datum['plan']) ? "'{$datum['plan']}'" : "NULL";
               $update_plan         = !empty($datum['update_plan']) ? "'{$datum['update_plan']}'" : "NULL";
               $check_type          = !empty($datum['check_type']) ? "'{$datum['check_type']}'" : "NULL";
               $enable_client_log   = !empty($datum['enable_client_log']) ? "'{$datum['enable_client_log']}'" : "NULL";
               $work_state_code     = !empty($datum['work_state_code']) ? "'{$datum['work_state_code']}'" : "NULL";
               $order_view          = !empty($datum['order_view']) ? "'{$datum['order_view']}'" : "NULL";
               $equipment           = !empty($datum['equipment']) ? "'{$datum['equipment']}'" : "NULL";
               $sale_area           = !empty($datum['sale_area']) ? "'{$datum['sale_area']}'" : "NULL";
               $root_area           = !empty($datum['root_area']) ? "'{$datum['root_area']}'" : "NULL";
               $permanent           = !empty($datum['permanent']) ? "'{$datum['permanent']}'" : "NULL";

               $queryContent
                    .= "(" .
                    $pdo->quote($datum['staff_id']) . "," .
                    $pdo->quote($datum['staff_code']) . "," .
                    $pdo->quote($datum['staff_name']) . "," .
                    $pdo->quote($datum['shop_id']) . "," .
                    $idno . "," .
                    $idno_place . "," .
                    $idno_date . "," .
                    $email  . "," .
                    $area_id . "," .
                    $street . "," .
                    $housenumber . "," .
                    $address . "," .
                    $phone . "," .
                    $mobilephone . "," .
                    $pdo->quote($datum['gender']) . "," .
                    $start_working_day . "," .
                    $education . "," .
                    $position . "," .
                    $birthday . "," .
                    $birthday_place . "," .
                    $pdo->quote($datum['staff_type_id']) . "," .
                    $pdo->quote($datum['status']) . "," .
                    $last_approved_order . "," .
                    $last_order . "," .
                    $pdo->quote($datum['create_date']) . "," .
                    $pdo->quote($datum['create_user']) . "," .
                    $pdo->quote($datum['update_date']) . "," .
                    $pdo->quote($datum['update_user']) . "," .
                    $plan . "," .
                    $update_plan . "," .
                    $pdo->quote($datum['sku']) . "," .
                    $pdo->quote($datum['reset_threshold']) . "," .
                    $pdo->quote($datum['sale_group']) . "," .
                    $pdo->quote($datum['name_text']) . "," .
                    $pdo->quote($datum['sale_type_code']) . "," .
                    $check_type . "," .
                    $enable_client_log . "," .
                    $work_state_code . "," .
                    $pdo->quote($datum['organization_id']) . "," .
                    $order_view . "," .
                    $equipment . "," .
                    $sale_area . "," .
                    $root_area . "," .
                    $permanent . "," .
                    $pdo->quote($store) . "," .
                    $pdo->quote($company) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
          }
          if (!empty($queryContent)) {
               $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`staff_code`= values(`staff_code`), " .
                    "`store_id` = values(`store_id`)," .
                    "`company_id` = values(`company_id`)," .
                    "updated_at='$now', updated_by=$me"; 
               DB::statement($queryUpdate);
          }
//=========================================================================================
          // User
          $queryHeaderUser = "INSERT INTO `users` (" .
               "`code`," .
               "`name`," .
               "`password`," .
               "`email`," .
               "`phone`," .
               "`store_id`, " .
               "`company_id`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";
          $now = date('Y-m-d H:i:s', time());
          $me = TM::getCurrentUserId();
          $store = TM::getCurrentStoreId();
          $company = TM::getCurrentCompanyId();
          $queryContentUser = "";
          $userImported = [];
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
               $create_date         = date('Y-m-d', strtotime($datum['create_date']));
               $update_date         = date('Y-m-d', strtotime($datum['update_date']));
               $idno                = !empty($datum['idno']) ? "'{$datum['idno']}'" : "NULL";
               $idno_place          = !empty($datum['idno_place']) ? "'{$datum['idno_place']}'" : "NULL";
               $idno_date           = !empty($datum['idno_date']) ? "'{$datum['idno_date']}'" : "NULL";
               $email               = !empty($datum['email']) ? "'{$datum['email']}'" : "NULL";
               $area_id             = !empty($datum['area_id']) ? "'{$datum['area_id']}'" : "NULL";
               $street              = !empty($datum['street']) ? "'{$datum['street']}'" : "NULL";
               $housenumber         = !empty($datum['housenumber']) ? "'{$datum['housenumber']}'" : "NULL";
               $address             = !empty($datum['address']) ? "'{$datum['address']}'" : "NULL";
               $phone               = !empty($datum['phone']) ? "'{$datum['phone']}'" : "NULL";
               $mobilephone         = !empty($datum['mobilephone']) ? "'{$datum['mobilephone']}'" : "NULL";
               $start_working_day   = !empty($datum['start_working_day']) ? "'{$datum['start_working_day']}'" : "NULL";
               $education           = !empty($datum['education']) ? "'{$datum['education']}'" : "NULL";
               $position            = !empty($datum['position']) ? "'{$datum['position']}'" : "NULL";
               $birthday            = !empty($datum['birthday']) ? "'{$datum['birthday']}'" : "NULL";
               // $birthday1= (\DateTime::createFromFormat('d/m/Y H.i.s', $datum['birthday'])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($datum['birthday']))) : "NULL";
               $birthday_place      = !empty($datum['birthday_place']) ? "'{$datum['birthday_place']}'" : "NULL";
               $last_approved_order = !empty($datum['last_approved_order']) ? "'{$datum['last_approved_order']}'" : "NULL";
               $last_order          = !empty($datum['last_order']) ? "'{$datum['last_order']}'" : "NULL";
               $plan                = !empty($datum['plan']) ? "'{$datum['plan']}'" : "NULL";
               $update_plan         = !empty($datum['update_plan']) ? "'{$datum['update_plan']}'" : "NULL";
               $check_type          = !empty($datum['check_type']) ? "'{$datum['check_type']}'" : "NULL";
               $enable_client_log   = !empty($datum['enable_client_log']) ? "'{$datum['enable_client_log']}'" : "NULL";
               $work_state_code     = !empty($datum['work_state_code']) ? "'{$datum['work_state_code']}'" : "NULL";
               $order_view          = !empty($datum['order_view']) ? "'{$datum['order_view']}'" : "NULL";
               $equipment           = !empty($datum['equipment']) ? "'{$datum['equipment']}'" : "NULL";
               $sale_area           = !empty($datum['sale_area']) ? "'{$datum['sale_area']}'" : "NULL";
               $root_area           = !empty($datum['root_area']) ? "'{$datum['root_area']}'" : "NULL";
               $permanent           = !empty($datum['permanent']) ? "'{$datum['permanent']}'" : "NULL";
               $temp = $datum['staff_code'];
               $userImported[$temp] = $temp;
          $queryContentUser
               .= "(" .
               $pdo->quote($datum['staff_code']) . "," .
               $pdo->quote($datum['staff_name']) . "," .
               $pdo->quote('FROM-IMPORT') . "," .
               $pdo->quote($datum['email']) . "," .
               $pdo->quote($datum['phone']) . "," .
               $pdo->quote($store) . "," .
               $pdo->quote($company) . "," .
               $pdo->quote($now) . "," .
               $pdo->quote($me) . "," .
               $pdo->quote($now) . "," .
               $pdo->quote($me) . "), ";
          }
          if (!empty($queryContentUser)) {
               $queryUpdateUser = $queryHeaderUser . (trim($queryContentUser, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`code`= values(`code`), " .
                    "`store_id` = values(`store_id`)," .
                    "`company_id` = values(`company_id`)," .
                    "updated_at='$now', updated_by=$me";
               DB::statement($queryUpdateUser);
          }
//=========================================================================================
          //Profile
          
          $allUserId = User::select(['id', 'code'])
               ->whereIn('code', array_values($userImported))
               ->pluck('id', 'code')
               ->toArray();
          $queryHeaderProfile = "INSERT INTO `profiles` (" .
               "`user_id`, " .
               "`email`, " .
               "`first_name`, " .
               "`last_name`, " .
               "`full_name`, " .
               "`address`, " .
               "`phone`, " .
               "`birthday`, " .
               "`gender`, " .
               "`is_active`, " .
               "`deleted`, " .
               "`created_at`, " .
               "`created_by`, " .
               "`updated_at`, " .
               "`updated_by`) VALUES ";
          $now = date('Y-m-d H:i:s', time());
          $me = TM::getCurrentUserId();
          $store = TM::getCurrentStoreId();
          $queryContentProfile = "";
          /** @var \PDO $pdo */
          $pdo = DB::getPdo();
          foreach ($data as $datum) {
               $idno                = !empty($datum['idno']) ? "'{$datum['idno']}'" : "NULL";
               $idno_place          = !empty($datum['idno_place']) ? "'{$datum['idno_place']}'" : "NULL";
               $idno_date           = !empty($datum['idno_date']) ? "'{$datum['idno_date']}'" : "NULL";
               $email               = !empty($datum['email']) ? "{$datum['email']}" : "NULL";
               $area_id             = !empty($datum['area_id']) ? "'{$datum['area_id']}'" : "NULL";
               $street              = !empty($datum['street']) ? "'{$datum['street']}'" : "NULL";
               $housenumber         = !empty($datum['housenumber']) ? "'{$datum['housenumber']}'" : "NULL";
               $address             = !empty($datum['address']) ? "'{$datum['address']}'" : "NULL";
               $phone               = !empty($datum['phone']) ? "'{$datum['phone']}'" : "NULL";
               $mobilephone         = !empty($datum['mobilephone']) ? "'{$datum['mobilephone']}'" : "NULL";
               $start_working_day   = !empty($datum['start_working_day']) ? "'{$datum['start_working_day']}'" : "NULL";
               $education           = !empty($datum['education']) ? "'{$datum['education']}'" : "NULL";
               $position            = !empty($datum['position']) ? "'{$datum['position']}'" : "NULL";
               $birthday            = !empty($datum['birthday']) ? "'{$datum['birthday']}'" : "NULL";
               $birthday_place      = !empty($datum['birthday_place']) ? "'{$datum['birthday_place']}'" : "NULL";
               $temp                = $datum['staff_code'];
               $userId              = $allUserId[$temp];
               // dd($userId);
               if (empty($allUserId[$temp])) {
                    continue;
               }
               $queryContentProfile
                    .= "(" .
                    $pdo->quote($userId) . "," .
                    $pdo->quote($email) . "," .
                    $pdo->quote($datum['staff_name']) . "," . //full_name
                    $pdo->quote($datum['staff_name']) . "," . //full_name
                    $pdo->quote($datum['staff_name']) . "," . //full_name
                    $pdo->quote($address) . "," .
                    $pdo->quote($phone) . "," .
                    $pdo->quote($datum['birthday']) . "," .//birthday
                    $pdo->quote($datum['gender']) . "," .
                    $pdo->quote(1) . "," .
                    $pdo->quote(0) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
          }
          if (!empty($queryContentProfile)) {
               $queryUpdateProfile = $queryHeaderProfile . (trim($queryContentProfile, ", ")) .
                    " ON DUPLICATE KEY UPDATE " .
                    "`user_id`= values(`user_id`), " .
                    "`email`= values(`email`), " .
                    "`address` = values(`address`), " .
                    "`phone` = values(`phone`), " .
                    "`is_active` = values(`is_active`), " .
                    "`deleted` = values(`deleted`), " .
                    "updated_at='$now', updated_by=$me";
               DB::statement($queryUpdateProfile);
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

          $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-STAFF-SYNC-(warning).csv", 'w');
          fwrite($csv_handler, $csv);
          fclose($csv_handler);

          return true;
     }
}
