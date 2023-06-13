<?php


namespace App\V1\Controllers\Import;


use App\Company;
use App\Role;
use App\Supports\Message;
use App\TM;
use App\User;
use App\UserCustomer;
use App\V1\Models\UserCustomerModel;
use App\V1\Models\UserModel;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Customer3ImportController
{
    protected $_header;
    protected $_required;
    protected $_duplicate;
    protected $_notExisted;
    protected $_number;
    protected $_date;

    /*lam xong check ton tai banf shop_id*/
    public function __construct()
    {
        $this->_header     = [
            0  => "CUSTOMER_ID",
            1  => "SHOP_ID",
            2  => "CUSTOMER_CODE",
            3  => "SHORT_CODE",
            4  => "FIRST_CODE",
            5  => "CUSTOMER_NAME",
            6  => "CONTACT_NAME",
            7  => "PHONE",
            8  => "MOBIPHONE",
            9  => "EMAIL",
            10 => "CHANNEL_TYPE_ID",
            11 => "MAX_DEBIT_AMOUNT",
            12 => "MAX_DEBIT_DATE",
            13 => "AREA_ID",
            14 => "HOUSENUMBER",
            15 => "STREET",
            16 => "ADDRESS",
            17 => "REGION",
            18 => "IMAGE_URL",
            19 => "LAST_APPROVE_ORDER",
            20 => "LAST_ORDER",
            21 => "STATUS",
            22 => "CREATE_DATE",
            23 => "CREATE_USER",
            24 => "UPDATE_DATE",
            25 => "UPDATE_USER",
            26 => "INVOICE_CONPANY_NAME",
            27 => "INVOICE_OUTLET_NAME",
            28 => "INVOICE_TAX",
            29 => "INVOICE_PAYMENT_TYPE",
            30 => "INVOICE_NUMBER_ACCOUNT",
            31 => "INVOICE_NAME_BANK",
            32 => "DELIVERY_ADDRESS",
            33 => "LAT",
            34 => "NAME_TEXT",
            35 => "APPLY_DEBIT_LIMITED",
            36 => "IDNO",
            37 => "LNG",
            38 => "FAX",
            39 => "BIRTHDAY",
            40 => "FREQUENCY",
            41 => "INVOICE_NAME_BRANCH_BANK",
            42 => "BANK_ACCOUNT_OWNER",
            43 => "SALE_POSITION_ID",
            44 => "SALE_STATUS",
            45 => "ORDER_VIEW",
        ];
        $this->_required   = [
            0 => trim($this->_header[0]), //"CUSTOMER_ID",
            1 => trim($this->_header[1]), //"SHOP_ID",
            2 => trim($this->_header[2]), //"CUSTOMER_CODE",
            //            3 => trim($this->_header[3]), //"SHORT_CODE",
            //            4 => trim($this->_header[4]), //"FIRST_CODE"
            5 => trim($this->_header[5]), //"CUSTOMER_NAME",
            //            6  => trim($this->_header[6]), //"CONTACT_NAME",
            //            7  => trim($this->_header[7]), //"PHONE",
            //            8  => trim($this->_header[8]), //"MOBIPHONE",
            //            9  => trim($this->_header[9]), //"EMAIL",
            //            10 => trim($this->_header[10]), //"CHANNEL_TYPE_ID",
            //            11 => trim($this->_header[11]), //"MAX_DEBIT_AMOUNT",
            //            12 => trim($this->_header[12]), //"MAX_DEBIT_DATE",
            //            13 => trim($this->_header[13]), //"AREA_ID",
            //            14 => trim($this->_header[14]), //"HOUSENUMBER",
            //            15 => trim($this->_header[15]), //"STREET",
            //            16 => trim($this->_header[16]), //"ADDRESS",
            //            17 => trim($this->_header[17]), //"REGION",
            //            18 => trim($this->_header[18]), //"IMAGE_URL",
            //            19 => trim($this->_header[19]), //"LAST_APPROVE_ORDER",
            //            20 => trim($this->_header[20]), //"LAST_ORDER",
            //            21 => trim($this->_header[21]), //"STATUS",
            //            22 => trim($this->_header[22]), //"CREATE_DATE",
            //            23 => trim($this->_header[23]), //"CREATE_USER",
            //            24 => trim($this->_header[24]), //"UPDATE_DATE",
            //            25 => trim($this->_header[25]), //"UPDATE_USER",
            //            26 => trim($this->_header[26]), //"INVOICE_CONPANY_NAME",
            //            27 => trim($this->_header[27]), //"INVOICE_OUTLET_NAME",
            //            28 => trim($this->_header[28]), //"INVOICE_TAX",
            //            29 => trim($this->_header[29]), //"INVOICE_PAYMENT_TYPE",
            //            30 => trim($this->_header[30]), //"INVOICE_NUMBER_ACCOUNT",
            //            31 => trim($this->_header[31]), //"INVOICE_NAME_BANK",
            //            32 => trim($this->_header[32]), //"DELIVERY_ADDRESS",
            //            33 => trim($this->_header[33]), //"LAT",
            //            34 => trim($this->_header[34]), //"NAME_TEXT",
            //            35 => trim($this->_header[35]), //"APPLY_DEBIT_LIMITED",
            //            36 => trim($this->_header[36]), //"IDNO",
            //            37 => trim($this->_header[37]), //"LNG",
            //            38 => trim($this->_header[38]), //"FAX",
            //            39 => trim($this->_header[39]), //"BIRTHDAY",
            //            40 => trim($this->_header[40]), //"FREQUENCY",
            //            41 => trim($this->_header[41]), //"INVOICE_NAME_BRANCH_BANK",
            //            42 => trim($this->_header[42]), //"BANK_ACCOUNT_OWNER",
            //            43 => trim($this->_header[43]), //"SALE_POSITION_ID",
            //            44 => trim($this->_header[44]), //"SALE_STATUS",
            //            45 => trim($this->_header[45]), //"ORDER_VIEW",
        ];
        $this->_duplicate  = [
            0 => 'customer_id',
            2 => 'code',
            3 => 'short_code',
            7 => 'phone',
            8 => 'mobiphone',
            9 => 'email',
        ];
        $this->_notExisted = [
            1 => 'shop_id',
        ];
        $this->_number     = [];
        $this->_date       = [];


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
        $empty    = true;
        $fileName = "Import-User-Customers_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");

        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                //Check in UserCustomerModel
                'customer_id' => [
                    'fields' => ['customer_id', 'id', 'name', 'code', 'customer_id'],
                    'model'  => new UserCustomerModel(),
                ],

                'code'       => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new UserCustomerModel(),
                ],
                'short_code' => [
                    'fields' => ['short_code', 'id', 'name', 'code', 'short_code'],
                    'model'  => new UserCustomerModel(),
                ],
                'phone'      => [
                    'fields' => ['phone', 'id', 'name', 'code', 'phone'],
                    'model'  => new UserCustomerModel(),
                ],
                'mobiphone'  => [
                    'fields' => ['mobiphone', 'id', 'name', 'code', 'phone', 'mobiphone'],
                    'model'  => new UserCustomerModel(),
                ],
                'email'      => [
                    'fields' => ['email', 'id', 'name', 'code', 'email'],
                    'model'  => new UserCustomerModel(),
                ],

                //Check in UserModel
                'short_code' => [
                    'fields' => ['code', 'id', 'name', 'code'],
                    'model'  => new UserModel(),
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
            $idUsed   = [];

            $shortCodeUsed = [];
            $emailCodeUsed = [];
            $phoneUsed     = [];
            $phoneMobiUsed = [];
            $totalLines    = 0;

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
                        44 => $row[44] ?? "",
                        45 => $row[45] ?? "",
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
                                $err .= "Required " . $this->_header[$key] ."\"";
                                continue;
                            }
                        }

                        // Check Duplicate ID
                        if (!empty($row[0])) {
                            $tmp = strtoupper($row[0]);
                            if (in_array($tmp, $idUsed)) {
                                $err .= "Duplicate ID " . $tmp ."\"";
                            } else array_push($idUsed, $tmp);
                        }
                        // Check Duplicate Code
                        if (!empty($row[2])) {
                            $tmp = strtoupper($row[2]);
                            if (in_array($tmp, $codeUsed)) {
                                $err .= "Duplicate Code " . $tmp ."\"";
                            } else array_push($codeUsed, $tmp);
                        }

                        // Check Duplicate Short Code
                        if (!empty($row[3])) {
                            $tmp = strtoupper($row[3]);
                            if (in_array($tmp, $shortCodeUsed)) {
                                $err = "Duplicate Short Code " . $tmp ."\"";
                            } else array_push($shortCodeUsed, $tmp);
                        }

                        // Check Duplicate Phone
                        if (!empty($row[7])) {
                            $tmp = strtoupper($row[7]);
                            if (in_array($tmp, $phoneUsed)) {
                                $err .= "Duplicate Phone " . $tmp ."\"";
                            } else array_push($phoneUsed, $tmp);
                        }
                        // Check Duplicate Phone Mobi
                        if (!empty($row[8])) {
                            $tmp = strtoupper($row[8]);
                            if (in_array($tmp, $phoneMobiUsed)) {
                                $err .= "Duplicate Phone Mobi " . $tmp ."\"";
                            } else array_push($phoneMobiUsed, $tmp);
                        }

                        // Check Duplicate Email
                        if (!empty($row[9])) {
                            $tmp = strtoupper($row[9]);
                            if (in_array($tmp, $emailCodeUsed)) {
                                $err .= "Duplicate Email " . $tmp ."\"";
                            } else array_push($emailCodeUsed, $tmp);
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
                        foreach ($this->_duplicate as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }
                            $val = trim(strtoupper($row[$key]));
                            if (!empty($existedChk[$name][$val])) {
                                $err .= "[$name : $val existed, please input other]" ."\"";
                            }
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }
                        // print_r( unlink($filePath));die;
                        $countRow++;
                        $valids[] = [
                            'customer_id'              => $row[0] ?? null,
                            'shop_id'                  => $row[1] ?? null,
                            'code'                     => $row[2] ?? "",
                            'short_code'               => $row[3] ?? "",
                            'first_code'               => $row[4] ?? "",
                            'name'                     => $row[5] ?? "",
                            'contact_name'             => $row[6] ?? "",
                            'phone'                    => $row[7] ?? "",
                            'mobiphone'                => $row[8] ?? "",
                            'email'                    => $row[9] ?? "",
                            'channel_type_id'          => $row[10] ?? null,
                            'max_debit_amount'         => $row[11] ?? "",
                            'max_debit_date'           => ($row[12]) ? date("Y-m-d", strtotime($row[12])) : "",
                            'area_id'                  => $row[13] ?? null,
                            'house_number'             => $row[14] ?? "",
                            'street'                   => $row[15] ?? "",
                            'address'                  => $row[16] ?? "",
                            'region'                   => $row[17] ?? "",
                            'image_url'                => $row[18] ?? "",
                            'last_approve_order'       => $row[19] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[19])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[19]))) : "",
                            'last_order'               => $row[20] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[20])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[20]))) : "",
                            'status'                   => $row[21] ?? null,
                            'invoice_company_name'     => $row[26] ?? "",
                            'invoice_outlet_name'      => $row[27] ?? "",
                            'invoice_tax'              => $row[28] ?? "",
                            'invoice_payment_type'     => $row[29] ?? "",
                            'invoice_number_account'   => $row[30] ?? "",
                            'invoice_name_bank'        => $row[31] ?? "",
                            'delivery_address'         => $row[32] ?? "",
                            'lat'                      => $row[33] ?? null,
                            'name_text'                => $row[34] ?? "",
                            'apply_debit_limited'      => $row[35] ?? "",
                            'idno'                     => $row[36] ?? "",
                            'lng'                      => $row[37] ?? null,
                            'fax'                      => $row[38] ?? "",
                            'birthday'                 => ($row[39]) ? date("Y-m-d", strtotime($row[35])) : "",
                            'frequency'                => $row[40] ?? null,
                            'invoice_name_branch_bank' => $row[41] ?? "",
                            'bank_account_owner'       => $row[42] ?? "",
                            'sale_position_id'         => $row[43] ?? "",
                            'sale_status'              => $row[44] ?? null,
                            'order_view'               => $row[45] ?? null,
                            'created_at'               => $row[22] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[22])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[22]))) : "",
                            'created_by'               => $row[23] ?? "",
                            'updated_at'               => $row[24] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[24])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[24]))) : "",
                            'updated_by'               => $row[25] ?? "",

                        ];
                    }
                }
                // Only process one sheet
//                print_r($emailCodeUsed); die();
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
        /** @var \PDO $pdo */
        $pdo                      = DB::getPdo();
        $now                      = date('Y-m-d H:i:s', time());
        $me                       = TM::getCurrentUserId();
        $store                    = TM::getCurrentStoreId();
        $company                  = TM::getCurrentCompanyId();
        $userImported             = [];
        $queryContentUserCustomer = "";
        $queryContentUser         = "";


        //User Customer
        $queryHeaderUserCustomer = "INSERT INTO `user_customers` (" .
            "`customer_id`, " .
            "`shop_id`, " .
            "`code`, " .
            "`short_code`, " .
            "`first_code`, " .
            "`name`, " .
            "`contact_name`, " .
            "`phone`, " .
            "`mobiphone`, " .
            "`email`, " .
            "`channel_type_id`, " .
            "`max_debit_amount`, " .
            "`max_debit_date`, " .
            "`area_id`, " .
            "`house_number`, " .
            "`street`, " .
            "`address`, " .
            "`region`, " .
            "`image_url`, " .
            "`last_approve_order`, " .
            "`last_order`, " .
            "`status`, " .
            "`invoice_company_name`, " .
            "`invoice_outlet_name`, " .
            "`invoice_tax`, " .
            "`invoice_payment_type`, " .
            "`invoice_number_account`, " .
            "`invoice_name_bank`, " .
            "`delivery_address`, " .
            "`lat`, " .
            "`name_text`, " .
            "`apply_debit_limited`, " .
            "`idno`, " .
            "`lng`, " .
            "`fax`, " .
            "`birthday`, " .
            "`frequency`, " .
            "`invoice_name_branch_bank`, " .
            "`bank_account_owner`, " .
            "`sale_position_id`, " .
            "`sale_status`, " .
            "`order_view`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";

        foreach ($data as $datum) {
            $shortCode = $datum['short_code'];
            if (!empty($userImported[$shortCode])) {
                continue;
            }
            $userImported[$shortCode] = $shortCode;

            $customer_id          = !empty($datum['customer_id']) ? "'{$datum['customer_id']}'" : "NULL";
            $shop_id              = !empty($datum['shop_id']) ? "'{$datum['shop_id']}'" : "NULL";
            $channel_type_id      = !empty($datum['channel_type_id']) ? "'{$datum['channel_type_id']}'" : "NULL";
            $max_debit_date       = !empty($datum['max_debit_date']) ? "'{$datum['max_debit_date']}'" : "NULL";
            $area_id              = !empty($datum['area_id']) ? "'{$datum['area_id']}'" : "NULL";
            $status               = !empty($datum['status']) ? "'{$datum['status']}'" : "NULL";
            $invoice_payment_type = !empty($datum['invoice_payment_type']) ? "'{$datum['invoice_payment_type']}'" : "NULL";
            $lat                  = !empty($datum['lat']) ? "'{$datum['lat']}'" : "NULL";
            $lng                  = !empty($datum['lng']) ? "'{$datum['lng']}'" : "NULL";
            $birthday             = !empty($datum['birthday']) ? "'{$datum['birthday']}'" : "NULL";
            $frequency            = !empty($datum['frequency']) ? "'{$datum['frequency']}'" : "NULL";
            $sale_position_id     = !empty($datum['sale_position_id']) ? "'{$datum['sale_position_id']}'" : "NULL";
            $sale_status          = !empty($datum['sale_status']) ? "'{$datum['sale_status']}'" : "NULL";
            $order_view           = !empty($datum['order_view']) ? "'{$datum['order_view']}'" : "NULL";

            $queryContentUserCustomer .=
                "(" .
                $customer_id . "," .
                $shop_id . "," .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote($datum['short_code']) . "," .
                $pdo->quote($datum['first_code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($datum['contact_name']) . "," .
                $pdo->quote($datum['phone']) . "," .
                $pdo->quote($datum['mobiphone']) . "," .
                $pdo->quote($datum['email']) . "," .
                $channel_type_id . "," .
                $pdo->quote($datum['max_debit_amount']) . "," .
                $max_debit_date . "," .
                $area_id . "," .
                $pdo->quote($datum['house_number']) . "," .
                $pdo->quote($datum['street']) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($datum['region']) . "," .
                $pdo->quote($datum['image_url']) . "," .
                $pdo->quote($datum['last_approve_order']) . "," .
                $pdo->quote($datum['last_order']) . "," .
                $status . "," .
                $pdo->quote($datum['invoice_company_name']) . "," .
                $pdo->quote($datum['invoice_outlet_name']) . "," .
                $pdo->quote($datum['invoice_tax']) . "," .
                $invoice_payment_type . "," .
                $pdo->quote($datum['invoice_number_account']) . "," .
                $pdo->quote($datum['invoice_name_bank']) . "," .
                $pdo->quote($datum['delivery_address']) . "," .
                $lat . "," .
                $pdo->quote($datum['name_text']) . "," .
                $pdo->quote($datum['apply_debit_limited']) . "," .
                $pdo->quote($datum['idno']) . "," .
                $lng . "," .
                $pdo->quote($datum['fax']) . "," .
                $birthday . "," .
                $frequency . "," .
                $pdo->quote($datum['invoice_name_branch_bank']) . "," .
                $pdo->quote($datum['bank_account_owner']) . "," .
                $sale_position_id . "," .
                $sale_status . "," .
                $order_view . "," .
                ($datum['created_at'] ? $pdo->quote($datum['created_at']) : "null") . "," .
                ($datum['created_by'] ? $pdo->quote($datum['created_by']) : "null") . "," .
                ($datum['updated_at'] ? $pdo->quote($datum['updated_at']) : "null") . "," .
                ($datum['updated_by'] ? $pdo->quote($datum['updated_by']) : "null") . "),";
        }

        if (!empty($queryContentUserCustomer)) {
            $queryUpdate = $queryHeaderUserCustomer . (trim($queryContentUserCustomer, ", "));
            //print_r($queryUpdate);die;
            DB::statement($queryUpdate);
        }
        //User Company
        $allUserId    = UserCustomer::model()->select(['id', 'short_code'])->whereIn('short_code',
            array_values($userImported))->get()->pluck('id', 'short_code')->toArray();
        $userImported = [];

        $queryHeaderUser = "INSERT INTO `users` (" .
            "`user_customer_id`, " .
            "`code`, " .
            "`password`, " .
            "`phone`, " .
            "`name`, " .
            "`email`, " .
            "`company_id`, " .
            "`store_id`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        foreach ($data as $datum) {
            $phone = $datum['phone'] ? $datum['phone'] : $datum['mobiphone'];
            if (!empty($userImported[$phone])) {
                continue;
            }
            $userImported[$phone] = $phone;
            //$shortCode
            $shortCode = $datum['short_code'];
            if (empty($allUserId[$shortCode])) {
                continue;
            }
            $user_customer_id = $allUserId[$shortCode];
            $queryContentUser .=
                "(" .
                $user_customer_id . "," .
                $pdo->quote($shortCode) . "," .
                $pdo->quote('FROM-IMPORT') . "," .
                $phone . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote(($datum['email'] ?? "")) . "," .
                $pdo->quote($company) . "," .
                $pdo->quote($store) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContentUser)) {
            $queryUpdate = $queryHeaderUser . (trim($queryContentUser, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_customer_id`= values(`user_customer_id`), " .
                "`code`= values(`code`), " .
                "`name`= values(`name`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }

        // Profile
        $allUserId = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();

        $queryHeaderProfile  = "INSERT INTO `profiles` (" .
            "`user_id`, " .
            "`first_name`, " .
            "`last_name`, " .
            "`short_name`, " .
            "`full_name`, " .
            "`address`, " .
            "`phone`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $queryContentProfile = "";
        foreach ($data as $datum) {
            $phone = $datum['phone'] ? $datum['phone'] : $datum['mobiphone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId     = $allUserId[$phone];
            $name       = explode(" ", trim($datum['name']));
            $first_name = $name[0];
            unset($name[0]);
            $last      = !empty($name) ? implode(" ", $name) : null;
            $full_name = $datum['name'];

            $queryContentProfile .=
                "(" .
                $pdo->quote($userId) . "," .
                $pdo->quote($first_name) . "," .
                $pdo->quote($last) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($full_name) . "," .
                $pdo->quote($datum['address']) . "," .
                $pdo->quote($phone) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContentProfile)) {
            $queryUpdate = $queryHeaderProfile . (trim($queryContentProfile, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_id`= values(`user_id`), " .
                "`email`= values(`email`), " .
                "`address` = values(`address`), " .
                "`phone` = values(`phone`), " .
                "`is_active` = values(`is_active`), " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }

        //User Company
        $allUserId               = User::model()->select(['id', 'phone'])->whereIn('phone',
            array_values($userImported))->get()->pluck('id', 'phone')->toArray();
        $role                    = Role::model()->select(['id', 'code', 'name'])->where('code', 'GUEST')->first()->toArray();
        $company                 = Company::model()->select(['id', 'code', 'name'])->where('id',
            TM::getCurrentCompanyId())->first()->toArray();
        $queryHeaderUserCompany  = "INSERT INTO `user_companies` (" .
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
        $queryContentUserCompany = "";
        foreach ($data as $datum) {
            $phone = $datum['phone'] ? $datum['phone'] : $datum['mobiphone'];
            if (empty($allUserId[$phone])) {
                continue;
            }
            $userId                  = $allUserId[$phone];
            $phone                   = str_replace(" ", "", $datum['phone']);
            $phone                   = str_replace(".", "", $phone);
            $queryContentUserCompany .=
                "(" .
                $pdo->quote($userId) . "," .
                $pdo->quote($role['id']) . "," .
                $pdo->quote($company['id']) . "," .
                $pdo->quote($datum['short_code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($role['code']) . "," .
                $pdo->quote($role['name']) . "," .
                $pdo->quote($company['code']) . "," .
                $pdo->quote($company['name']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContentUserCompany)) {
            $queryUpdate = $queryHeaderUserCompany . (trim($queryContentUserCompany, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`user_id`= values(`user_id`), " .
                "`company_id` = values(`company_id`), " .
                "updated_at='$now', updated_by=$me";
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

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-USER-CUSTOMERS-(warning).csv", 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return true;

    }

}