<?php


namespace App\V1\Controllers\Import;


use App\Category;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\User;
use App\V1\Models\CategoryModel;
use App\V1\Models\CityModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\ProductExcelModel;
use App\V1\Models\UnitModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\UserModel;
use App\V1\Models\WardModel;
use App\V1\Models\BrandModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductVNSImportController
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
            0  => "PRODUCT_ID",
            1  => "PRODUCT_CODE",
            2  => "PARENT_PRODUCT_CODE",
            3  => "STATUS",
            4  => "UOM1",
            5  => "UOM2",
            6  => "CAT_ID",
            7  => "SUB_CAT_ID",
            8  => "BRAND_ID",
            9  => "FLAVOUR_ID",
            10 => "PACKING_ID",
            11 => "PRODUCT_TYPE",
            12 => "EXPIRY_TYPE",
            13 => "EXPIRY_DATE",
            14 => "BARCODE",
            15 => "PRODUCT_LEVEL_ID",
            16 => "CREATE_DATE",
            17 => "CREATE_USER",
            18 => "UPDATE_DATE",
            19 => "UPDATE_USER",
            20 => "CHECK_LOT",
            21 => "SAFETY_STOCK",
            22 => "COMMISSION",
            23 => "VOLUMN",
            24 => "NET_WEIGHT",
            25 => "GROSS_WEIGHT",
            26 => "PRODUCT_NAME",
            27 => "NAME_TEXT",
            28 => "SUB_CAT_T_ID",
            29 => "IS_NOT_TRIGGER",
            30 => "IS_NOT_MIGRATE",
            31 => "SYN_ACTION",
            32 => "GROUP_CAT_ID",
            33 => "GROUP_VAT",
            34 => "SHORT_NAME",
            35 => "ORDER_INDEX",
            36 => "CONVFACT",
            37 => "REF_PRODUCT_ID",
            38 => "REF_APPLY_DATE",
            39 => "ORDER_INDEX_PO",
            40 => "PALLET",

        ];
        $this->_required = [
            1   => trim($this->_header[1]),
            2   => trim($this->_header[2]),
            6   => trim($this->_header[6]),
            7   => trim($this->_header[7]),
            8   => trim($this->_header[8]),
            9   => trim($this->_header[9]),
            10  => trim($this->_header[10]),
            15  => trim($this->_header[15]),
            28  => trim($this->_header[28]),
            32  => trim($this->_header[32]),
            37  => trim($this->_header[37]),
        ];
        $this->_duplicate = [
            0 => trim($this->_header[0]),         
        ];
        $this->_notExisted = [
        ];
        $this->_number = [
            24  => trim($this->_header[24]),
            25  => trim($this->_header[25]),
        ];
        $this->_date = [
            13 => trim($this->_header[13]),
            16 => trim($this->_header[16]),
            18 => trim($this->_header[18]),
            38 => trim($this->_header[38]),
        ];
    }

    public function  import(Request $request)
    {
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
        $valids = [];
        $valids2 = [];
        $countRow = 0;
        $error = false;
        $empty = true;
        $fileName = "Import-Product_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        try {
            $dataCheck = [
                'PRODUCT_ID' => [
                    'fields' => ['id', 'code', 'id'],
                    'model'  => new ProductExcelModel(),
                ],
                'PRODUCT_CODE' => [
                    'fields' => ['code', 'id', 'product_name', 'code'],
                    'model'  => new ProductExcelModel(),
                ],
                'PRODUCT_Name' => [
                    'fields' => ['product_name', 'id', 'product_name'],
                    'model'  => new ProductExcelModel(),
                ],
                'BRAND_ID' => [
                    'fields' => ['id', 'name', 'id'],
                    'model'  => new BrandModel(),
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
                        40 => $row[40] ?? ""
                    ];
                   
                    $totalLines++;
                    if ($totalLines > 10001) {
                        return response()->json([
                            'status'  => 'error',
                            'message' => Message::get("V013", Message::get("data_row"), 10000),
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
                        // Check Valid
                        $errorDetail = $this->checkValidRow($row, $codeUsed, $existedChk);
                        $countRow++;
                        $valids[] = [
                            'id'                    => $row[0],
                            'code'                  => $row[1],
                            'parent_product_code'   => $row[2],
                            'status'                => $row[3],
                            'uom1'                  => $row[4],
                            'uom2'                  => $row[5],
                            'cat_id'                => $row[6],
                            'sub_cat_id'            => $row[7],
                            'brand_id'              => $row[8],
                            'flavour_id'            => $row[9],
                            'packing_id'            => $row[10],
                            'product_type'          => $row[11],
                            'expiry_type'           => $row[12],
                            'expiry_date'           => $row[13],
                            'barcode'               => $row[14],
                            'product_level_id'      => $row[15],
                            'create_date'           => $row[16],
                            'create_user'           => $row[17],
                            'update_date'           => $row[18],
                            'update_user'           => $row[19],
                            'check_lot'             => $row[20],
                            'safety_stock'          => $row[21],
                            'commission'            => $row[22],
                            'volumn'                => $row[23],
                            'net_weight'            => $row[24],
                            'gross_weight'          => $row[25],
                            'product_name'          => $row[26],
                            'name_text'             => $row[27],
                            'sub_cat_t_id'          => $row[28],
                            'is_not_trigger'        => $row[29],
                            'is_not_migrate'        => $row[30],
                            'syn_action'            => $row[31],
                            'group_cat_id'          => $row[32],
                            'group_vat'             => $row[33],
                            'short_name'            => $row[34],
                            'order_index'           => $row[35],
                            'convfact'              => $row[36],                          
                            'ref_product_id'        => $row[37],       
                            'ref_apply_date'        => $row[38],
                            'order_index_po'        => $row[39],
                            'pallet'                => $row[40],
                            // 'is_active'             => 1,
                        ];

                        // $valids2[] = [
                        //     'name'                  =>$row[1],
                        //     'code'                  =>$row[2],
                        //     'slug'                  =>$row[3],
                        //     'meta_title'            =>$row[4],
                        //     'meta_description'      =>$row[5],
                        //     'meta_keyword'          =>$row[6],
                        //     'meta_robot'            =>$row[7],
                        //     'tags'                  =>$row[8],
                        //     'type'                  =>$row[9],
                        //     'shop_id'               =>$row[10],
                        //     'age_id'                =>$row[11],
                        //     'area_name'             =>$row[12],
                        //     'short_description'     =>$row[13],
                        //     'description'           =>$row[14],
                        //     'thumbnail'             =>$row[15],
                        //     'gallery_images'        =>$row[16],
                        //     'category_ids'          =>$row[17],
                        //     'store_supermarket'     =>$row[18],
                        //     'paid'                  =>$row[19],
                        //     'price'                 =>$row[20],
                        //     'discount_unit_type'    =>$row[21],
                        //     'user_group_id'         =>$row[22],
                        //     'qty_out_min'           =>$row[23],
                        //     'qty'                   =>$row[24],
                        //     'length'                =>$row[25],
                        //     'width'                 =>$row[26],
                        //     'height'                =>$row[27],
                        //     'length_class'          =>$row[28],
                        //     'weight_class'          =>$row[29],
                        //     'weight'                =>$row[30],
                        //     'status'                =>$row[31],
                        //     'order'                 =>$row[32],
                        //     'view'                  =>$row[33],
                        //     'store_id'              =>$row[34],
                        //     'brand_id'              =>$row[35],
                        //     'is_featured'           =>$row[36],
                        //     'manufacturer_id'       =>$row[37],
                        //     'sync_zalo'             =>$row[38],
                        //     'unit_id'               =>$row[39],
                        //     'order_count'           =>$row[40],
                        //     'sold_count'            =>$row[41],
                        //     'poin'                  =>$row[42],
                        //     'publish_status'        =>$row[43],
                        //     'specification_id'      =>$row[44],
                        //     'count_rate'            =>$row[45],
                        //     'gift_item'             =>$row[46],
                        //     'is_active'             =>$row[47],
                        //     'qty_flash_sale'        =>$row[48],
                        //     'product_excel'         =>$row[49],
                            
                        // ];
                   
                   
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
//            header('Content-Type: text/csv');
//            header('Content-Disposition: attachment; filename="sample.csv"');
//
//            $fp = fopen('php://output', 'wb');
//            foreach ($errors as $line) {
//                print_r($line);die();
//                $val = explode(",", $line);
//
//                fputcsv($fp, $val);
//            }
//            fclose($fp);
            return response()->json([
                'status'  => 'error',
                'message' => $errors,
            ], 406);
        } else {
            try {
                $this->startImport($valids, $codeUsed);
                return response()->json([
                    'status'  => 'success',
                    'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                ], 200);
            } catch (\Exception $ex) {
                return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
            }
        }
    }

    private function checkValidRow(&$row, &$codeUsed, $existedChk = [])
    {
        // die();

       
        $errorDetail = "";

        // Check Required
        foreach ($this->_required as $key => $name) {
            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                $errorDetail .= "[" . Message::get("V001", $this->_header[$key]) . "]";
            }
        }

        // Check Duplicated
        foreach ($this->_duplicate as $key => $name) {
            if (empty($row[$key])) {
                continue;
            }
            $val = trim(strtoupper($row[$key]));
            if (!empty($existedChk[$name][$val])) {
                $errorDetail .= "[" . Message::get("V007", $this->_header[$key]) . "]";
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
            } else {
                if ($name == 'code') {
                    $row[$key] = $existedChk[$name][$val];
                    continue;
                }
                $row[$key] = $val;
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
            if (empty($row[$key])) {
                continue;
            }

            if ($row[$key] instanceof \DateTime) {
                $row[$key] = $row[$key]->format('Y-m-d');
                continue;
            }
            $val = strtotime(trim($row[$key]));
            if ($val > 0) {
                $row[$key] = date('Y-m-d', $val);
                continue;
            }

            $errorDetail .= "[" . $this->_header[$key] . " must be a date!]";
        }

        return $errorDetail;
    }
    
    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }

        DB::beginTransaction();
        $queryHeader = "INSERT INTO `product_dms_imports` (" .
        "`id`," .
        "`code`," .
        "`parent_product_code`," .
        "`status`," .
        "`uom1`," .
        "`uom2`," .
        "`cat_id`," .
        "`sub_cat_id`," .
        "`brand_id`," .
        "`flavour_id`," .
        "`packing_id`," .
        "`product_type`," .
        "`expiry_type`," .
        "`expiry_date`," .
        "`barcode`, " .
        "`product_level_id`, " .
        "`create_date`, " .
        "`create_user`, " .
        "`update_date`, " .
        "`update_user`, " .
        "`check_lot`, " .
        "`safety_stock`, " .
        "`commission`, " .
        "`volumn`, " .
        "`net_weight`, " .
        "`gross_weight`, " .
        "`product_name`, " .
        "`name_text`, " .
        "`sub_cat_t_id`, " .
        "`is_not_trigger`, " .
        "`is_not_migrate`, " .
        "`syn_action`, " .
        "`group_cat_id`, " .
        "`group_vat`, " .
        "`short_name`, " .
        "`order_index`, " .
        "`convfact`, " .
        "`ref_product_id`, " .
        "`ref_apply_date`, " .
        "`order_index_po`, " .
        "`pallet`, " .

        "`created_at`, " .
        "`created_by`, " .
        "`updated_at`, " .
        "`updated_by`) VALUES ";

        $queryHeader2 = "INSERT INTO `products` (" .

        "`name` ," .
        "`code` ," .
        "`slug` ," .
        "`brand_id` ," .
        "`is_active` ," .
        "`store_id` ," .
        "`type` ," .
        "`product_dms_import` ," .
        "`weight` ," .
        "`category_ids` ," .
        // "`meta_title` ," .
        // "`meta_description` ," .
        // "`meta_keyword` ," .
        // "`meta_robot` ," .
        // "`tags` ," .
        // "`type` ," .
        // "`shop_id` ," .
        // "`age_id` ," .
        // "`area_name` ," .
        // "`short_description` ," .
        // "`description` ," .
        // "`thumbnail` ," .
        // "`gallery_images` ," .
        // "`category_ids` ," .
       
        // "`price` ," .
        // "`discount_unit_type` ," .
        // "`user_group_id` ," .
        // "`qty_out_min` ," .
        // "`qty` ," . //2
        // "`length` ," .
        // "`width` ," .
        // "`height` ," .
        // "`length_class` ," . //cm ,mm
        // "`weight_class` ," . //kg 
        // "`weight` ," . //so luong 70
        // "`status` ," . 
      
        // "`view` ," .
        // "`store_id` ," . //lay qua
       
        // "`is_featured` ," .
        // "`manufacturer_id` ," .
        // "`sync_zalo` ," .
        // "`unit_id` ," .  //thung or hộp
        // "`order_count` ," .
        // "`sold_count` ," .
        // "`poin` ," .
        // "`publish_status` ," .
        // "`specification_id` ," .
        // "`count_rate` ," .
        // "`gift_item` ," .
        
        // "`qty_flash_sale` ," .
       
        // "`deleted` ," .
        "`created_at` ,".
        "`created_by` ,".
        "`updated_at` ,".
        "`updated_by`) VALUES ";

        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();
        $queryContent = "";
        $queryContent2 = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $queryContent .=
                "(" .
                $pdo->quote($datum['id']). "," .
                $pdo->quote($datum['code']). "," .
                $pdo->quote($datum['parent_product_code']). "," .
                $pdo->quote($datum['status']). "," .
                $pdo->quote($datum['uom1']). "," .
                $pdo->quote($datum['uom2']). "," .
                $pdo->quote($datum['cat_id']). "," .
                $pdo->quote($datum['sub_cat_id']). "," .
                $pdo->quote($datum['brand_id']). "," .
                $pdo->quote($datum['flavour_id']). "," .
                $pdo->quote($datum['packing_id']). "," .
                $pdo->quote($datum['product_type']). "," .
                $pdo->quote($datum['expiry_type']). "," .
                $pdo->quote($datum['expiry_date']). "," .
                $pdo->quote($datum['barcode']). "," .
                $pdo->quote($datum['product_level_id']). "," .
                $pdo->quote($datum['create_date']). "," .
                $pdo->quote($datum['create_user']). "," .
                $pdo->quote($datum['update_date']). "," .
                $pdo->quote($datum['update_user']). "," .
                $pdo->quote($datum['check_lot']). "," .
                $pdo->quote($datum['safety_stock']). "," .
                $pdo->quote($datum['commission']). "," .
                $pdo->quote($datum['volumn']). "," .
                $pdo->quote($datum['net_weight']). "," .
                $pdo->quote($datum['gross_weight']). "," .
                $pdo->quote($datum['product_name']). "," .
                $pdo->quote($datum['name_text']). "," .
                $pdo->quote($datum['sub_cat_t_id']). "," .
                $pdo->quote($datum['is_not_trigger']). "," .
                $pdo->quote($datum['is_not_migrate']). "," .
                $pdo->quote($datum['syn_action']). "," .
                $pdo->quote($datum['group_cat_id']). "," .
                $pdo->quote($datum['group_vat']). "," .
                $pdo->quote($datum['short_name']). "," .
                $pdo->quote($datum['order_index']). "," .
                $pdo->quote($datum['convfact']). "," .
                $pdo->quote($datum['ref_product_id']). "," .
                $pdo->quote($datum['ref_apply_date']). "," .
                $pdo->quote($datum['order_index_po']). "," .
                $pdo->quote($datum['pallet']). "," .

                
                // $pdo->quote($datum['is_active']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
            

                $queryContent2 .=
                    "(" .

                    $pdo->quote($datum['product_name']). "," .
                    $pdo->quote($datum['code']). "," .
                    $pdo->quote(Str::slug($datum['product_name'])). "," .
                    $pdo->quote($datum['brand_id']). "," .
                    $pdo->quote($datum['status']). "," .
                    $pdo->quote(TM::getCurrentStoreId()). "," .
                    $pdo->quote('PRODUCT'). "," .
                    $pdo->quote($datum['id']). "," .
                    $pdo->quote($datum['net_weight']). "," .
                    $pdo->quote($datum['cat_id']). "," .
                    // "`brand_id` ," .
                    // "`is_active` ," .
                    // "`store_id` ," .
                    // "`product_type` ," .
                    // "`product_excel` ," .
                    // "`weight` ," .
                    // "`category_ids` ," .
                    // $pdo->quote($datum['is_active']) . "," .

                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "," .
                    $pdo->quote($now) . "," .
                    $pdo->quote($me) . "), ";
        }
        
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                "`brand_id`= values(`brand_id`), " .
         

                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        if (!empty($queryContent2)) {
            $queryUpdate2 = $queryHeader2 . (trim($queryContent2, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                
                // "`product_excel`= values(`product_excel`), " .

                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate2);
        }


        // Commit transaction
        DB::commit();
    }
}