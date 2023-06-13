<?php


namespace App\V1\Controllers\Import;


use App\Company;
use App\Distributor;
use App\Profile;
use App\Role;
use App\Store;
use App\Supports\Message;
use App\TM;
use App\User;
use App\UserCompany;
use App\UserGroup;
use App\UserStore;
use App\V1\Models\CategoryModel;
use App\V1\Models\CityModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use App\V1\Models\WardModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CustomerImportController
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
            0  => "Mã CH (*)",
            1  => "Tên CH (*)",
            2  => "SDT (*)",
            3  => "Email (*)",
            4  => "Mã nhóm đối tượng (*)",
            5  => "Mã số thuế",
            6  => "Mật khẩu",
            7  => "Tình trạng",
            8  => "Mã Tỉnh/TP (*)",
            9  => "Tỉnh/TP",
            10  => "Mã Quận/Huyện (*)",
            11  => "Quận/Huyện",
            12  => "Mã Phường/ Xã (*)",
            13  => "Phường/Xã",
            14  => "Địa chỉ",
            15  => "Loại tài khoản (*)",
            16  => "MÃ TTPP",
        ];
        // dd($this->_header[0]);
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
            4 => trim($this->_header[4]),
            8 => trim($this->_header[8]),
            10 => trim($this->_header[10]),
            12 => trim($this->_header[12]),
            15 => trim($this->_header[15]),

        ];
//         $this->_duplicate = [
// //            0 => 'code',
// //            2 => 'phone',
//         ];
        $this->_notExisted = [
            // 4 => 'groups',
            8 => 'city',
            10 => 'district',
            12 => 'ward',
//            23 => 'distributor_center_code',
        ];
        $this->_number = [
    
        ];
    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 5000);
        ini_set('memory_limit', '300M');

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
        $warnings = [];
        $valids = [];
        $countRow = 0;
        $error = false;
        $empty = true;
        $fileName = "Import-Customer_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
     
        $existedChk = [];
        try {
            $dataCheck = [
                'code'         => [
                    'fields' => ['code', 'id', 'name', 'code', 'code'],
                    'model'  => new UserModel(),
                ],
                'phone'        => [
                    'fields' => ['phone', 'id', 'name', 'code', 'phone'],
                    'model'  => new UserModel(),
                ],
                'groups'       => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new UserGroupModel(),
                ],
                'distributors' => [
                    'fields' => ['code', 'name', 'code', 'id'],
                    'model'  => new UserModel(),
                    'where'  => ' group_code="HUB" ',
                ],
                'distributor_center_code' => [
                    'fields' => ['code', 'name', 'code', 'id'],
                    'model'  => new UserModel(),
                    'where'  => ' group_code="TTPP"',
                ],
                'city' => [
                    'fields' => ['code', 'name', 'code', 'id' , 'full_name'],
                    'model'  => new CityModel(),
                ],
                'district' => [
                    'fields' => ['code', 'name', 'code', 'id', 'full_name'],
                    'model'  => new DistrictModel(),
                ],
                'ward' => [
                    'fields' => ['code', 'name', 'code', 'id' , 'full_name'],
                    'model'  => new WardModel(),
                ],
            ];
           
       
            foreach ($dataCheck as $key => $option) {
                // dd($option);
            
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
                        10 => $row[10] ?? "",
                        11 => $row[11] ?? "",
                        12 => $row[12] ?? "",
                        13 => $row[13] ?? "",
                        14 => $row[14] ?? "",
                        15 => $row[15] ?? "",
                        16 => $row[16] ?? "",
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
                       
                        $row[2] = str_replace(" ", "", $row[2]);

                        $checkGroupCode = UserGroup::where('code', $row[4]) // where lower case
                        ->where('company_id',TM::getCurrentCompanyId())->first();
                        // dd($checkGroupCode);
                        if(empty($checkGroupCode)){
                            $row[] = "Required " . $this->_header[4];
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        // Check Valid
                        if (empty($row[2])) {
                            // Except Temporarily
                            $row[] = "Empty " . $this->_header[2];
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }
//                        if (empty($existedChk['distributors'][trim(strtoupper($row[5]))])) {
//                            // Except Temporarily
//                            continue;
//                        }

                        // Check Duplicate Code
                        if (!empty($row[0])) {
                            $tmp = strtoupper($row[0]);
                            if (in_array($tmp, $codeUsed)) {
                                $row[] = "Duplicate " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }

                        // Check Duplicate Code
                        if (!empty($row[2])) {
                            $tmp = strtoupper($row[2]);
                            if (in_array($tmp, $phoneUsed)) {
                                $row[] = "Duplicate " . $this->_header[2];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }

                        // Check Duplicated
                        $err = "";
                        if ($err) {
                            $row[] = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        $tmp = strtoupper($row[2]);
                        if (strlen($tmp) > 12 || !is_numeric($tmp)) {
                            $row[] = "[" . Message::get("V002", $this->_header[2]) . "]";
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }
                     
                        $errorDetail = $this->checkValidRow($row, $codeUsed, $phoneUsed, $existedChk);
                        $countRow++;
                        $valids[] = [
                            'code'             => $row[0],
                            'name'             => $row[1],
                            'phone'            => $row[2],
                            'email'            => $row[3],
                            'group_code'       => !empty($row[4]) ? $checkGroupCode->code : null,
                            // 'gender'           => $row[4],
                            'birthday'         => null,
                            'tax'              => $row[5],
                            'password'         => !empty($row[6]) ? $row[6] : "12345678",
                            'status'           => !empty($row[7]) ? (int)$row[7] : 0,
                            // 'group_code'       => $row[7],
                            'group_id'         => !empty($row[4]) ? $checkGroupCode->id : null,
                            'group_name'       => $row[4] ?? null,
                            'address'          => $row[14],
                            'distributor_center_id'     => !empty($row[16]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[16]))]['id'] ?? null : null,
                            'distributor_center_code'   => !empty($row[16]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[16]))]['code'] ?? null : null,
                            'distributor_center_name'   => !empty($row[16]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[16]))]['name'] ?? null : null,
                            'city'              =>  !empty($row[8]) ? $existedChk['city'][trim(strtoupper($row[8]))]['code'] ?? null : null,
                            'city_full_name'              =>  !empty($row[8]) ? $existedChk['city'][trim(strtoupper($row[8]))]['full_name'] ?? null : null,
                            'district'          =>  !empty($row[10]) ? $existedChk['district'][trim(strtoupper($row[10]))]['code'] ?? null : null,
                            'district_full_name'          =>  !empty($row[10]) ? $existedChk['district'][trim(strtoupper($row[10]))]['full_name'] ?? null : null,
                            'ward'              =>  !empty($row[12]) ? $existedChk['ward'][trim(strtoupper($row[12]))]['code'] ?? null : null,
                            'ward_full_name'              =>  !empty($row[12]) ? $existedChk['ward'][trim(strtoupper($row[12]))]['full_name'] ?? null : null,
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
                        $phoneUsed[] = $row[2];
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
            return response()->json([
                'status'  => 'error',
                'message' => $errors,
            ], 201);
        } else {
            try {
                // dd($valids);
                $this->startImport($valids);
                header('Content-Type: application/json');
                return response()->json([
                    'status'  => 'success',
                    'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                    'warning' => !empty($warnings) ? $warnings : null,
                ], 200);
            } catch (\Exception $ex) {
                return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
            }
        }
    }

    private function checkValidRow(&$row, &$codeUsed, &$phoneUsed, &$existedChk)
    {
        $errorDetail = "";

        // Check Required
        foreach ($this->_required as $key => $name) {
            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                $errorDetail .= "[" . Message::get("V001", $this->_header[$key]) . "]";
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
        // foreach ($this->_date as $key => $name) {
        //     if ($row[$key] instanceof \DateTime) {

        //         $row[$key] = $row[$key]->format('Y-m-d 00:00:00');
        //         continue;
        //     }
        //     $val = strtotime(trim($row[$key]));
        //     if(empty($row[$key])){
        //        continue;
        //     }
        //     if ($val > 0) {
        //         $row[$key] = date('Y-m-d', $val);
        //         continue;
        //     }
        //     $errorDetail .= "[" . $this->_header[$key] . " must be a date!]";
        // }

        return $errorDetail;
    }

    private function startImport($data)
    {
        // insert Users
        if (empty($data)) {
            return 0;
        }
        $role = null;
        DB::beginTransaction();
        $queryHeader = "INSERT INTO `users` (" .
            "`id`, " .
            "`code`, " .
            "`password`, " .
            "`email`, " .
            "`phone`, " .
            "`name`, " .
            "`company_id`, " .
            "`store_id`, " .
            "`group_id`, " .
            "`group_code`, " .
            "`group_name`, " .
            "`distributor_id`, " .
            "`distributor_code`, " .
            "`distributor_name`, " .
            "`role_id`, " .
            "`distributor_center_id`, " .
            "`distributor_center_code`, " .
            "`distributor_center_name`, " .
            "`tax`, " .
            "`is_active`, " .
            "`type_delivery_hub` ," .
            "`is_transport` ," .
            "`min_amt` ," .
            "`max_amt` ," .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();
        $store = TM::getCurrentStoreId();
        $company = TM::getCurrentCompanyId();
        $userImported = [];
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (!empty($userImported[$phone])) {
                continue;
            }
            if(!empty($datum['group_code'])){
                if($datum['group_code'] == USER_GROUP_DISTRIBUTOR) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_GROUP_DISTRIBUTOR)->first()->toArray();
                }
                if($datum['group_code'] == USER_GROUP_HUB) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_GROUP_HUB)->first()->toArray();
                }
                if($datum['group_code'] == USER_GROUP_DISTRIBUTOR_CENTER) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_ROLE_GROUP_DISTRIBUTION)->first()->toArray();
                }

            }
            $checkUser = User::model()->where(['code'=>$datum['code'],'company_id'=>TM::getCurrentCompanyId()])->first();
            $idUser    = !empty($checkUser) ? (int)$checkUser->id : "null";
            $distributor_id = !empty($datum['distributor_id']) ? "'{$datum['distributor_id']}'" : "null";
            $distributor_code = !empty($datum['distributor_code']) ? "'{$datum['distributor_code']}'" : "null";
            $distributor_name = !empty($datum['distributor_name']) ? "'{$datum['distributor_name']}'" : "null";
            $distributor_center_id = !empty($datum['distributor_center_id']) ? "'{$datum['distributor_center_id']}'" : "null";
            $distributor_center_name = !empty($datum['distributor_center_name']) ? "'{$datum['distributor_center_name']}'" : "null";
            $distributor_center_code = !empty($datum['distributor_center_code']) ? "'{$datum['distributor_center_code']}'" : "null";
            $userImported[$phone] = $phone;
            $queryContent .=
                "(" .
                $idUser . "," .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote(password_hash($datum['password'], PASSWORD_BCRYPT)) . "," .
                $pdo->quote($datum['email']) . "," .
                $pdo->quote($datum['phone']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($company) . "," .
                $pdo->quote($store) . "," .
                (int)$datum['group_id'] . "," .
                $pdo->quote($datum['group_code']) . "," .
                $pdo->quote($datum['group_name']) . "," .
                $distributor_id . "," .
                $distributor_code . "," .
                $distributor_name . "," .
                $role['id'] . "," .
                $distributor_center_id . "," .
                $distributor_center_code . "," .
                $distributor_center_name . "," .
                $pdo->quote($datum['tax']) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote("GRAB") . "," .
                $pdo->quote(1) . "," .
                $pdo->quote(300000) . "," .
                $pdo->quote(3000000) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                "`password`= values(`password`), " .
                "`email`= values(`email`), " .
                "`phone`= values(`phone`), " .
                "`name`= values(`name`), " .
                "`card_ids`= values(`card_ids`), " .
                "`is_active`= values(`is_active`), " .
                "`tax`= values(`tax`), " .
                "`group_id` = values(`group_id`), " .
                "`group_code` = values(`group_code`), " .
                "`group_name` = values(`group_name`), " .
                "`distributor_id` = values(`distributor_id`), " .
                "`distributor_code` = values(`distributor_code`), " .
                "`distributor_name` = values(`distributor_name`), " .
                "`distributor_center_id` = values(`distributor_center_id`), " .
                "`distributor_center_code` = values(`distributor_center_code`), " .
                "`distributor_center_name` = values(`distributor_center_name`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        
        // Profile
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();
           
        $queryHeader = "INSERT INTO `profiles` (" .
            "`id`, " .
            "`user_id`, " .
            "`email`, " .
            "`first_name`, " .
            "`last_name`, " .
            "`short_name`, " .
            "`full_name`, " .
            "`address`, " .
            "`phone`, " .
            "`city_code`, " .
            "`district_code`, " .
            "`ward_code`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now    = date('Y-m-d H:i:s', time());
        $me     = TM::getCurrentUserId();
        $store  = TM::getCurrentStoreId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId = $allUserId[$phone];
            $checkProfile = Profile::model()->where('user_id',$userId)->first();
            $id_profile = !empty($checkProfile) ? (int)$checkProfile->id : "null";
            $name = explode(" ", trim($datum['name']));
            $first_name = $name[0];
            unset($name[0]);
            $last = !empty($name) ? implode(" ", $name) : null;
            $full_name = $datum['name'];
            $city_code = !empty($datum['city']) ? "{$datum['city']}" : "null";
            $district_code = !empty($datum['district']) ? "{$datum['district']}" : "null";
            $ward_code = !empty($datum['ward']) ? "{$datum['ward']}" : "null";
            $birthDay = !empty($datum['birthday']) ? "{$datum['birthday']}" : "null";
            $email = !empty($datum['email']) ? "{$datum['email']}" : "null";
            $queryContent .=
                "(" .
                $id_profile . "," .
                $pdo->quote($userId) . "," .
                $pdo->quote($email) . "," .
                $pdo->quote($first_name) . "," .
                $pdo->quote($last) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($city_code) . "," .
                $pdo->quote($district_code) . "," .
                $pdo->quote($ward_code) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_id`= values(`user_id`), " .
                "`email`= values(`email`), " .
                "`gender`= values(`gender`), " .
                "`birthday`= values(`birthday`), " .
                "`first_name`= values(`first_name`), " .
                "`last_name`= values(`last_name`), " .
                "`short_name`= values(`short_name`), " .
                "`full_name`= values(`full_name`), " .
                "`email`= values(`email`), " .
                "`address` = values(`address`), " .
                "`phone` = values(`phone`), " .
                "`city_code` = values(`city_code`), " .
                "`district_code` = values(`district_code`), " .
                "`ward_code` = values(`ward_code`), " .
                "`is_active` = values(`is_active`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }

         // insert distributors
         DB::beginTransaction();
         $queryHeader = "INSERT INTO `distributors` (" .
             "`code`," .
             "`name`, " .
             "`city_code`," .
             "`city_full_name`," .
             "`district_code`," .
             "`district_full_name`," .
             "`ward_code`," .
             "`ward_full_name`," .
             "`company_id`," .
             "`store_id`," .
             "`is_active`, " .
             "`created_at`, " .
             "`created_by` ) VALUES  " ;   
         $now = date('Y-m-d H:i:s', time());
         $me = TM::getCurrentUserId();
         $company      = TM::getCurrentCompanyId();
         $store        = TM::getCurrentStoreId();
         $queryContent = "";
         /** @var \PDO $pdo */
         $pdo = DB::getPdo();
         foreach ($data as $datum) {
             $queryContent .=
                 "(" .
                 $pdo->quote($datum['code']) . "," .
                 $pdo->quote($datum['name']) . "," .
                 $pdo->quote($datum['city']) . "," .
                 $pdo->quote($datum['city_full_name']) . "," .
                 $pdo->quote($datum['district']) . "," .
                 $pdo->quote($datum['district_full_name']) . "," .
                 $pdo->quote($datum['ward']) . "," .
                 $pdo->quote($datum['ward_full_name']) . "," .
                 $pdo->quote($company) . "," .
                 $pdo->quote($store) . "," .
                 1 . "," .       
                 $pdo->quote($now) . "," .
                 $pdo->quote($me) . "), ";
         }
         if (!empty($queryContent)) {
             $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                 " ON DUPLICATE KEY UPDATE " .
                 "`code`= values(`code`), " .
                 "`name`= values(`name`), " .
                 "`city_code`= values(`city_code`), " .
                 "`city_full_name`= values(`city_full_name`), " .
                 "`district_code`= values(`district_code`), " .
                 "`district_full_name`= values(`district_full_name`), " .
                 "`ward_code`= values(`ward_code`), " .
                 "`ward_full_name`= values(`ward_full_name`), " .
                 "`company_id`= values(`company_id`), " .
                 "`store_id`= values(`store_id`), " .
                 "`is_active` = values(`is_active`), " .
                 "updated_at='$now', updated_by=$me";
             DB::statement($queryUpdate);
         }
         // Commit transaction
         DB::commit();

        //User Company
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();
        $company = Company::model()->select(['id', 'code', 'name'])->where('id', TM::getCurrentCompanyId())->first()->toArray();
        $queryHeader = "INSERT INTO `user_companies` (" .
            "`id`, " .
            "`user_id`, " .
            "`role_id`, " .
            "`company_id`, " .
            "`user_code`, " .
            "`user_name`, " .
            "`role_code`, " .
            "`role_name`, " .
            "`company_code`, " .
            "`company_name`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();
        $store = TM::getCurrentStoreId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            if(!empty($datum['group_code'])){
                if($datum['group_code'] == USER_GROUP_DISTRIBUTOR) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_GROUP_DISTRIBUTOR)->first()->toArray();
                }
                if($datum['group_code'] == USER_GROUP_HUB) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_GROUP_HUB)->first()->toArray();
                }
                if($datum['group_code'] == USER_ROLE_GROUP_DISTRIBUTION) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_ROLE_GROUP_DISTRIBUTION)->first()->toArray();
                }
            }
            $userId = $allUserId[$phone];
            $checkCompany = UserCompany::model()->where('user_id',$userId)->first();
            $idCompany = !empty($checkCompany) ? (int)$checkCompany->id :  "null";
            $phone = str_replace(" ", "", $datum['phone']);
            $phone = str_replace(".", "", $phone);
            $queryContent .=
                "(" .
                $idCompany . "," .
                $pdo->quote($userId) . "," .
                $pdo->quote($role['id']) . "," .
                $pdo->quote($company['id']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($role['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($company['code']) . "," .
                $pdo->quote($company['name']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_id`= values(`user_id`), " .
                "`role_id`= values(`role_id`), " .
                "`user_name`= values(`user_name`), " .
                "`role_code`= values(`role_code`), " .
                "`role_name`= values(`role_name`), " .
                "`company_id` = values(`company_id`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }

        $store = Store::model()->select(['id', 'code', 'name'])->where('id', TM::getCurrentStoreId())->first()->toArray();
        $queryHeader = "INSERT INTO `user_stores` (" .
                       "`id`, " .
                       "`user_id`, " .
                       "`role_id`, " .
                       "`company_id`, " .
                       "`store_id`, " .
                       "`user_code`, " .
                       "`user_name`, " .
                       "`store_code`, " .
                       "`store_name`, " .
                       "`company_code`, " .
                       "`company_name`, " .
                       "`role_code`, " .
                       "`role_name`, " .
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
            $phone = $datum['phone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId = $allUserId[$phone];
            $checkStoreId = UserStore::model()->where('user_id',$userId)->first();
            $idStore = !empty($checkStoreId) ? (int)$checkStoreId->id : "null";
            if(!empty($datum['group_code'])){
                if($datum['group_code'] == USER_GROUP_DISTRIBUTOR) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_GROUP_DISTRIBUTOR)->first()->toArray();
                }
                if($datum['group_code'] == USER_GROUP_HUB) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_GROUP_HUB)->first()->toArray();
                }
                if($datum['group_code'] == USER_ROLE_GROUP_DISTRIBUTION) {
                    $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_ROLE_GROUP_DISTRIBUTION)->first()->toArray();
                }

            }
            $queryContent .=
                "(" .
                $idStore . "," .
                $pdo->quote($userId) . "," .
                $pdo->quote($role['id']) . "," .
                $pdo->quote($company['id']) . "," .
                $pdo->quote($store['id']) . "," .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($store['code']) . "," .
                $pdo->quote($store['name']) . "," .
                $pdo->quote($company['code']) . "," .
                $pdo->quote($company['name']) . "," .
                $pdo->quote($role['code']) . "," .
                $pdo->quote($role['name']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                           " ON DUPLICATE KEY UPDATE " .
                           "`user_id`= values(`user_id`), " .
                           "`role_id`= values(`role_id`), " .
                           "`user_name`= values(`user_name`), " .
                           "`role_id`= values(`role_id`), " .
                           "`user_code`= values(`user_code`), " .
                           "`store_id` = values(`store_id`), " .
                           "`company_id` = values(`company_id`), " .
                           "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }
}