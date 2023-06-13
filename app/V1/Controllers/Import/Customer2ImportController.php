<?php


namespace App\V1\Controllers\Import;


use App\Company;
use App\CustomerInformation;
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
use App\V1\Transformers\User\UserCustomerProfileTransformer;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Customer2ImportController
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
            0  => "Mã khách hàng (*)",
            1  => "Tên khách hàng (*)",
            2  => "SDT (*)",
            3  => "Email (*)",
            4  => "Giới tính",
            5  => "CMT/ Hộ chiếu",
            6  => "Nghề nghiệp",
            7  => "Tình trạng hôn nhân",
            8  => "Trình độ học vấn",
            9  => "Ngày sinh",
            10 => "Ngày đăng ký thành viên",
            11 => "Hạng thành viên",
            12 => "Điểm tích lũy",
            13 => "Tổng doanh thu",
            14 => "Tổng đơn hàng",
            15 => "Tổng tiền tiết kiệm",
            16 => "Mã nhóm đối tượng (*)",
            17 => "Đối tượng",
            18  => "Mã số thuế",
            19  => "Mật khẩu",
            20  => "Tình trạng",
            21  => "Địa chỉ (*)",
            22  => "Tỉnh/TP (*)",
            23  => "Quận/Huyện (*)",
            24  => "Phường/ Xã (*)",
            25  => "Loại tài khoản (*)",
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
        ];
        $this->_duplicate = [
//            2 => 'phone',
        ];
        $this->_notExisted = [
            22 => 'city',
            23 => 'district',
            24 => 'ward',
            16 => 'groups',
        ];
        $this->_number = [

        ];
        $this->_date = [
            10 => trim($this->_header[10]),
            9 => trim($this->_header[9]),
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
//                'code'         => [
//                    'fields' => ['code', 'id', 'name', 'code', 'code'],
//                    'model'  => new UserModel(),
//                ],
                'phone'        => [
                    'fields' => ['phone', 'id', 'name', 'code', 'phone'],
                    'model'  => new UserModel(),
                ],
                'groups'       => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new UserGroupModel(),
                    'where'  => 'is_view=1'
                ],
//                'distributors' => [
//                    'fields' => ['code', 'name', 'code', 'id'],
//                    'model'  => new UserModel(),
//                    'where'  => ' group_code="HUB" ',
//                ],
//                'distributor_center_code' => [
//                    'fields' => ['code', 'name', 'code', 'id'],
//                    'model'  => new UserModel(),
//                    'where'  => ' group_code="TTPP"',
//                ],
                'city' => [
                    'fields' => ['code', 'name', 'code', 'id'],
                    'model'  => new CityModel(),
                ],
                'district' => [
                    'fields' => ['code', 'name', 'code', 'id'],
                    'model'  => new DistrictModel(),
                ],
                'ward' => [
                    'fields' => ['code', 'name', 'code', 'id'],
                    'model'  => new WardModel(),
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
                        17 => $row[17] ?? "",
                        18 => $row[18] ?? "",
                        19 => $row[19] ?? "",
                        20 => $row[20] ?? "",
                        21 => $row[21] ?? "",
                        22 => $row[22] ?? "",
                        23 => $row[23] ?? "",
                        24 => $row[24] ?? "",
                        25 => $row[25] ?? "",
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

                        // Check Valid
                        if (empty($row[2])) {
                            // Except Temporarily
                            $row[] = "Empty " . $this->_header[0];
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
                        if (!empty($row[2])) {
                            $tmp = strtoupper($row[2]);
                            if (in_array($tmp, $codeUsed)) {
                                $row[] = "Duplicate " . $this->_header[2];
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
                            'code'             => $row[2],
                            'name'             => $row[1],
                            'phone'            => $row[2],
                            'email'            => $row[3],
                            'gender'           => $row[4],
                            'card_ids'         => $row[5],
                            'occupation'       => $row[6],
                            'marital_status'       => $row[7],
                            'education'        => $row[8],
                            'birthday'         => $row[9],
                            'register_at'      => $row[10],
                            'tax'              => $row[10],
                            'password'         => !empty($row[19]) ? $row[19] : "12345678",
                            'status'           => !empty($row[20]) ? (int)$row[20] : 0,
                            'group_code'       => !empty($row[16]) ? $existedChk['groups'][trim(strtoupper($row[16]))]['code'] ?? null : null,
//                            'group_id'         => !empty($row[11]) ? $existedChk['groups'][trim(strtoupper($row[11]))]['id'] ?? null : null,
//                            'group_name'       => !empty($row[11]) ? $existedChk['groups'][trim(strtoupper($row[11]))]['name'] ?? null : null,
                            //                            'distributor_code' => $row[20],
                            //                            'distributor_id'   => !empty($row[20]) ? $existedChk['distributors'][trim(strtoupper($row[20]))]['id'] ?? null : null,
                            //                            'distributor_name' => !empty($row[20]) ? $existedChk['distributors'][trim(strtoupper($row[20]))]['name'] ?? null : null,
                            'address'          => $row[21],
//                            'distributor_center_id'     => !empty($row[20]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[20]))]['id'] ?? null : null,
//                            'distributor_center_code'   => !empty($row[20]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[20]))]['code'] ?? null : null,
//                            'distributor_center_name'   => !empty($row[20]) ? $existedChk['distributor_center_code'][trim(strtoupper($row[20]))]['name'] ?? null : null,
                            'city'              =>  !empty($row[22]) ? $existedChk['city'][trim(strtoupper($row[22]))]['code'] ?? null : null,
                            'district'          =>  !empty($row[23]) ? $existedChk['district'][trim(strtoupper($row[23]))]['code'] ?? null : null,
                            'ward'              =>  !empty($row[24]) ? $existedChk['ward'][trim(strtoupper($row[24]))]['code'] ?? null : null,
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

//                        $codeUsed[] = $row[0];
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
        die();
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

        // Check Duplicated
//        foreach ($this->_duplicate as $key => $name) {
//            if (empty($row[$key])) {
//                continue;
//            }
//            $val = trim(strtoupper($row[$key]));
//            if (!empty($existedChk[$name][$val])) {
//                $errorDetail .= "[" . Message::get("V007", $this->_header[$key]) . "]";
//            }
//        }

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
        foreach ($this->_date as $key => $name) {
            if ($row[$key] instanceof \DateTime) {
                $row[$key] = $row[$key]->format('Y-m-d 00:00:00');
                continue;
            }
            $val = strtotime(trim($row[$key]));
            if ($val > 0) {
                $row[$key] = date('Y-m-d', $val);
                continue;
            }
            $errorDetail .= "[" . $this->_header[$key] . " must be a date!]";
        }

        // Check Duplicate Code
//        $tmp = strtoupper($row[0]);
//        if (in_array($tmp, $codeUsed)) {
//            $errorDetail .= "[" . Message::get("V028", $this->_header[0]) . "]";
//        }

        // Check Duplicate Code
//        $tmp = strtoupper($row[3]);
//        if (in_array($tmp, $phoneUsed)) {
//            $errorDetail .= "[" . Message::get("V028", $this->_header[3]) . "]";
//        }

        return $errorDetail;
    }

    private function startImport($data)
    {
        if (empty($data)) {
            return 0;
        }
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
                       "`role_id`, " .
//                       "`card_ids`, " .
                       "`register_at`, " .
                       "`tax`, " .
                       "`is_active`, " .
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
            $user = User::model()->where('code',$phone)->where('company_id',TM::getCurrentCompanyId())->first();
            $id_user = !empty($user) ? (int)$user->id : "null";
            $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_ROLE_GUEST)->first()->toArray();
            $group = UserGroup::model()->select(['id', 'code', 'name'])->where('code',$datum['group_code'])->where('company_id',TM::getCurrentCompanyId())->first();
            $group_id = $group->id;
            $group_name = $group->name;
//            $distributor_id = !empty($datum['distributor_id']) ? "'{$datum['distributor_id']}'" : "null";
//            $distributor_code = !empty($datum['distributor_code']) ? "'{$datum['distributor_code']}'" : "null";
//            $distributor_name = !empty($datum['distributor_name']) ? "'{$datum['distributor_name']}'" : "null";
//            $distributor_center_id = !empty($datum['distributor_center_id']) ? "'{$datum['distributor_center_id']}'" : "null";
//            $distributor_center_name = !empty($datum['distributor_center_name']) ? "'{$datum['distributor_center_name']}'" : "null";
//            $distributor_center_code = !empty($datum['distributor_center_code']) ? "'{$datum['distributor_center_code']}'" : "null";
            $userImported[$phone] = $phone;
            $queryContent .=
                "(" .
                $id_user . "," .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote(password_hash($datum['password'], PASSWORD_BCRYPT)) . "," .
                $pdo->quote($datum['email']) . "," .
                $pdo->quote($datum['phone']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($company) . "," .
                $pdo->quote($store) . "," .
                (int)$group_id . "," .
                $pdo->quote($datum['group_code']) . "," .
                $pdo->quote($group_name) . "," .
                $role['id'] . "," .
//                $pdo->quote($datum['card_ids']) . "," .
                $pdo->quote($datum['register_at']) . "," .
                $pdo->quote($datum['tax']) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                           " ON DUPLICATE KEY UPDATE" .
                           "`code`= values(`code`), " .
//                           "`card_ids`= values(`card_ids`), " .
                           "`email`= values(`email`), " .
                           "`is_active`= values(`is_active`), " .
                           "`password`= values(`password`), " .
                           "`tax`= values(`tax`), " .
                           "`name`= values(`name`), " .
                           "`group_id` = values(`group_id`), " .
                           "`group_code` = values(`group_code`), " .
                           "`group_name` = values(`group_name`), " .
                           "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        // Profile
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();

        $queryHeader = "INSERT INTO `profiles` (" .
                       "`id`, " .
                       "`user_id`, " .
                       "`gender`, " .
                       "`marital_status`, " .
                       "`occupation`, " .
                       "`education`, " .
                       "`birthday`, " .
                       "`first_name`, " .
                       "`last_name`, " .
                       "`short_name`, " .
                       "`full_name`, " .
                       "`address`, " .
                       "`phone`, " .
                       "`city_code`, " .
                       "`district_code`, " .
                       "`ward_code`, " .
                       "`indentity_card`, " .
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
            $queryContent .=
                "(" .
                $id_profile . "," .
                $pdo->quote($userId) . "," .
                $pdo->quote($datum['gender']) . "," .
                $pdo->quote($datum['marital_status']) . "," .
                $pdo->quote($datum['occupation']) . "," .
                $pdo->quote($datum['education']) . "," .
                $pdo->quote($datum['birthday']) . "," .
                $pdo->quote($first_name) . "," .
                $pdo->quote($last) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($city_code) . "," .
                $pdo->quote($district_code) . "," .
                $pdo->quote($ward_code) . "," .
                $pdo->quote($datum['card_ids']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                           " ON DUPLICATE KEY UPDATE " .
                           "`user_id`= values(`user_id`), " .
                           "`gender`= values(`gender`), " .
                           "`occupation`= values(`occupation`), " .
                           "`marital_status`= values(`marital_status`), " .
                           "`education`= values(`education`), " .
                           "`birthday`= values(`birthday`), " .
                           "`first_name`= values(`first_name`), " .
                           "`last_name`= values(`last_name`), " .
                           "`indentity_card`= values(`indentity_card`), " .
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
//
//
//
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();
        $queryHeader = "INSERT INTO `customer_information` (" .
                       "`id`, " .
                       "`gender`, " .
                       "`email`, " .
                       "`name`, " .
                       "`street_address`, " .
                       "`address`, " .
                       "`phone`, " .
                       "`city_code`, " .
                       "`district_code`, " .
                       "`ward_code`, " .
                       "`store_id`) VALUES ";
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
            $checkCustomerInformation = CustomerInformation::where(['phone' => $phone,'store_id'=>TM::getCurrentStoreId()])->first();
            $idCustomerInformation = !empty($checkCustomerInformation) ? (int)$checkCustomerInformation->id : "null";
            $userId = $allUserId[$phone];
            $name = explode(" ", trim($datum['name']));
            $first_name = $name[0];
            unset($name[0]);
            $last = !empty($name) ? implode(" ", $name) : null;
            $full_name = $datum['name'];
            $city_code = !empty($datum['city']) ? "{$datum['city']}" : "null";
            $district_code = !empty($datum['district']) ? "{$datum['district']}" : "null";
            $ward_code = !empty($datum['ward']) ? "{$datum['ward']}" : "null";
            $queryContent .=
                "(" .
                $idCustomerInformation . "," .
                $pdo->quote($datum['gender']) . "," .
                $pdo->quote($datum['email']) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($city_code) . "," .
                $pdo->quote($district_code) . "," .
                $pdo->quote($ward_code) . "," .
                $pdo->quote($store) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                           " ON DUPLICATE KEY UPDATE " .
                           "`gender`= values(`gender`), " .
                           "`email`= values(`email`), " .
                           "`name`= values(`name`), " .
                           "`street_address`= values(`street_address`), " .
                           "`address`= values(`address`), " .
                           "`phone`= values(`phone`), " .
                           "`city_code` = values(`city_code`), " .
                           "`district_code` = values(`district_code`), " .
                           "`ward_code` = values(`ward_code`)";
            DB::statement($queryUpdate);
        }
//
//
//
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
            $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_ROLE_GUEST)->first()->toArray();
            $userId = $allUserId[$phone];
            $checkCompany = UserCompany::model()->where('user_id',$userId)->first();
            $idCompany = !empty($checkCompany) ? (int)$checkCompany->id : "null";
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
//
//
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
        $id_store = UserStore::max('id');
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $phone = $datum['phone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId = $allUserId[$phone];
            $checkStoreId = UserStore::model()->where('user_id')->first();
            $idStore = !empty($checkStoreId) ? (int)$checkStoreId->id : "null";
            $role = Role::model()->select(['id', 'code', 'name'])->where('code', USER_ROLE_GUEST)->first()->toArray();
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
//         Commit transaction
        DB::commit();
    }
}