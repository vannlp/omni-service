<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\Category;
use App\Product;
use App\Supports\Message;
use App\TM;
use App\V1\Models\CouponModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponImportController
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
            0 => "Mã giảm giá",
            1 => "Tên mã giảm giá",
            2 => "Loại giảm giá",
            3 => "Nội dung",
            4 => "Loại phiếu giảm giá",
            5 => "Giá giảm",
            6 => "Tổng",
            7 => "Miễn phí ship",
            8 => "Mã sản phẩm",
            9 => "Tên sản phẩm",
            10 => "Mã danh mục",
            11 => "Tên danh mục",
            12 => "Mã sản phẩm loại trừ",
            13 => "Tên sản phẩm loại trừ",
            14 => "Mã danh mục loại trừ",
            15 => "Tên danh mục loại trừ",
            16 => "Ngày bắt đầu",
            17 => "Ngày kết thúc",
            18 => "Tổng số sử dụng",
            19 => "Số khách hàng được sử dụng",
            20 => "Trạng thái"
        ];
        $this->_required   = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
            3 => trim($this->_header[3]),
            5 => trim($this->_header[5]),
            6 => trim($this->_header[6]),
        ];
        $this->_duplicate  = [
            0 => 'Mã giảm giá',
        ];
        $this->_notExisted = [];
        $this->_number     = [
            20 => 'Trạng thái',
        ];
        $this->_date       = [
            16 => 'Ngày bắt đầu',
            17 => 'Ngày kết thúc',
        ];
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
        $fileExt  = end($fileName);
        if (empty($fileExt) || !in_array($fileExt, ['xlsx', 'csv', 'ods'])) {
            return response()->json([
                'status'  => 'error',
                'message' => Message::get('regex', 'File Import'),
            ], 400);
        }

        $warnings = [];
        $valids   = [];
        $countRow = 0;
        $empty    = true;
        $fileName = "Import-Coupon_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";
        // print_r($fileName);die;
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
                'code'         => [
                    'fields' => ['code', 'name', 'type', 'discount', 'free_shipping', 'date_start', 'date_end', 'uses_total', 'total', 'uses_customer', 'status', 'product_ids', 'product_codes', 'product_names', 'category_ids', 'category_codes', 'category_names', 'product_except_ids', 'product_except_codes', 'product_except_names', 'category_except_ids', 'category_except_codes', 'category_except_names', 'created_at', 'created_by', 'updated_at', 'updated_by', 'store_id', 'company_id'],
                    'model'  => new CouponModel(),
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

            $codeUsed   = [];
            $totalLines = 0;
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $index => $row) {
                    $row = [
                        0 => $row[0] ?? null,
                        1 => $row[1] ?? null,
                        2 => $row[2] ?? null,
                        3 => $row[3] ?? null,
                        4 => $row[4] ?? null,
                        5 => $row[5] ?? null,
                        6 => $row[6] ?? null,
                        7 => $row[7] ?? null,
                        8 => $row[8] ?? null,
                        9 => $row[9] ?? null,
                        10 => $row[10] ?? null,
                        11 => $row[11] ?? null,
                        12 => $row[12] ?? null,
                        13 => $row[13] ?? null,
                        14 => $row[14] ?? null,
                        15 => $row[15] ?? null,
                        16 => $row[16] ?? null,
                        17 => $row[17] ?? null,
                        18 => $row[18] ?? null,
                        19 => $row[19] ?? null,
                        20 => $row[20] ?? null,
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
                                $row[]      = "Required " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }
                        $err = "";

                        // Check Date
                        foreach ($this->_date as $key => $name) {
                            //                            if (empty($row[$key])) {
                            //                                continue;
                            //                            }

                            if ($row[$key] instanceof \DateTime) {
                                $row[$key] = $row[$key]->format('Y-m-d');
                                continue;
                            }
                            $val = strtotime(trim($row[$key]));
                            if ($val > 0) {
                                $row[$key] = date('Y-m-d', $val);
                                continue;
                            }

                            $err .= "[" . $this->_header[$key] . " Bắt buộc phải là ngày!]";
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }



                        // Check Existed
                        foreach ($this->_notExisted as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }

                            $val = trim(strtoupper($row[$key]));
                            if (!isset($existedChk[$name][$val])) {
                                $row[]      = "Required " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                                continue;
                            }
                        }

                        $err = "";
                        // Check Number
                        foreach ($this->_number as $key => $name) {

                            if (empty($row[$key])) {
                                continue;
                            }

                            $val = trim($row[$key]);

                            if (!is_numeric($val)) {
                                $err .= "[" . $this->_header[$key] . " Bắt buộc phải là số!]";
                            }
                        }

                        // Check Duplicate Code
                        $tmp = strtoupper($row[1]);
                        if (in_array($tmp, $codeUsed)) {
                            $row[]      = "Duplicate Code " . $tmp;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        // Check Duplicate Code
                        // $tmp = strtoupper($row[3]);
                        // if (in_array($tmp, $phoneUsed)) {
                        //      $row[] = "Duplicate Phone" . $tmp;
                        //      $warnings[] = array_map(function ($r) {
                        //           return "\"" . $r . "\"";
                        //      }, $row);
                        //      continue;
                        // }

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
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        $countRow++;
                        //get id product
                        $product=[];
                        if(!empty($row[8])){
                            $ids = explode(", ", $row[8]);
                            foreach($ids as $proid){
                               $pid = Product::model()->where('code', $proid)->get();
                               foreach($pid as $pids)
                                array_push($product, $pids['id']);
                            }
                            $productId = implode(", ",$product);
                        }
                        else{
                            $productId = NULL;
                        }
                        //get id not product
                        $nproduct=[];
                        if(!empty($row[12])){
                            $nids = explode(", ", $row[12]);
                            foreach($nids as $nproid){
                               $npid = Product::model()->where('code', $nproid)->get();
                               foreach($npid as $npids)
                                array_push($nproduct, $pids['id']);
                            }
                            $nproductId = implode(", ",$nproduct);
                        }
                        else{
                            $nproductId = NULL;
                        }
                        //get id category
                        $category=[];
                        if(!empty($row[10])){
                            $ids = explode(", ", $row[10]);
                            foreach($ids as $cateid){
                               $cid = Category::model()->where('code', $cateid)->get();
                               foreach($cid as $cateids)
                                array_push($category, $cateids['id']);
                            }
                            $categoryId = implode(", ",$category);
                        }
                        else{
                            $categoryId = NULL;
                        }
                        //get id not category
                        $ncategory=[];
                        if(!empty($row[14])){
                            $ids = explode(", ", $row[14]);
                            foreach($ids as $ncateid){
                               $ncid = Category::model()->where('code', $ncateid)->get();
                               foreach($ncid as $ncateids)
                                array_push($ncategory, $ncateids['id']);
                            }
                            $ncategoryId = implode(", ",$ncategory);
                        }
                        else{
                            $ncategoryId = NULL;
                        }
                        $valids[] = [
                            'code'                         => $row[0],
                            'name'                         => $row[1],
                            'type'                         => $row[2],
                            'content'                      => $row[3],
                            'type_discount'                => $row[4],
                            'discount'                     => $row[5],
                            'total'                        => $row[6],
                            'free_shipping'                => $row[7],
                            'product_codes'                => $row[8],
                            'product_names'                => $row[9],
                            'category_codes'               => $row[10],
                            'category_names'               => $row[11],
                            'product_except_codes'         => $row[12],
                            'product_except_names'         => $row[13],
                            'category_except_codes'        => $row[14],
                            'category_except_names'        => $row[15],
                            'date_start'                   => $row[16],
                            'date_end'                     => $row[17],
                            'uses_total'                   => $row[18],
                            'uses_customer'                => $row[19],
                            'status'                       => $row[20],
                            'productIds'                   => $productId,
                            'not_product_ids'              => $nproductId,
                            'categoryIds'                  => $categoryId,
                            'not_category_ids'             => $ncategoryId,
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
        $queryHeader  = "INSERT INTO `coupons` (" .
            "`code`, " .
            "`name`, " .
            "`type`, " .
            "`content`, " .
            "`delivery`, " .
            "`discount`, " .
            "`total`, " .
            "`free_shipping`, " .
            "`product_ids`, " .
            "`product_codes`, " .
            "`product_names`, " .
            "`category_ids`, " .
            "`category_codes`, " .
            "`category_names`, " .
            "`product_except_ids`, " .
            "`product_except_codes`, " .
            "`product_except_names`, " .
            "`category_except_ids`, " .
            "`category_except_codes`, " .
            "`category_except_names`, " .
            "`date_start`, " .
            "`date_end`, " .
            "`uses_total`, " .
            "`uses_customer`, " .
            "`status`, " .
            "`company_id`, " .
            "`store_id`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now          = date('Y-m-d H:i:s', time());
        $me           = TM::getCurrentUserId();
        $company      = TM::getCurrentCompanyId();
        $store        = TM::getCurrentStoreId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            //product
            $productCodes = !empty($datum['product_codes'])? "'{$datum['product_codes']}'": "NULL";
            $productIDs   = !empty($datum['productIds'])? "'{$datum['productIds']}'": "NULL";
            $productNames = !empty($datum['product_names'])? "'{$datum['product_names']}'": "NULL";
            //nproduct
            $nproductCodes = !empty($datum['product_except_codes'])? "'{$datum['product_except_codes']}'": "NULL";
            $nproductIDs   = !empty($datum['not_product_ids'])? "'{$datum['not_product_ids']}'": "NULL";
            $nproductNames = !empty($datum['product_except_names'])? "'{$datum['product_except_names']}'": "NULL";
            //category
            $categoryIDs   = !empty($datum['categoryIds'])? "'{$datum['categoryIds']}'": "NULL";
            $categoryCodes = !empty($datum['category_codes'])? "'{$datum['category_codes']}'": "NULL";
            $categoryNames = !empty($datum['category_names'])? "'{$datum['category_names']}'": "NULL";
            //ncategory
            $ncategoryIDs   = !empty($datum['not_category_ids'])? "'{$datum['not_category_ids']}'": "NULL";
            $ncategoryCodes = !empty($datum['category_except_codes'])? "'{$datum['category_except_codes']}'": "NULL";
            $ncategoryNames = !empty($datum['category_except_names'])? "'{$datum['category_except_names']}'": "NULL";
            $content        = !empty($datum['content'])? "'{$datum['content']}'": "NULL";
            $queryContent
                .= "(" .
                $pdo->quote($datum['code']) . "," .
                $pdo->quote($datum['name']) . "," .
                $pdo->quote($datum['type']) . "," .
                $content . "," .
                $pdo->quote($datum['type_discount']) . "," .
                $pdo->quote($datum['discount']) . "," .
                $pdo->quote($datum['total']) . "," .
                $pdo->quote($datum['free_shipping']) . "," .
                $productIDs . "," .
                $productCodes . "," .
                $productNames . "," .
                $categoryIDs . "," .
                $categoryCodes . "," .
                $categoryNames . "," .
                $nproductIDs . "," .
                $nproductCodes . "," .
                $nproductNames . "," .
                $ncategoryIDs   . "," .
                $ncategoryCodes . "," .
                $ncategoryNames . "," .
                $pdo->quote($datum['date_start']) . "," .
                $pdo->quote($datum['date_end']) . "," .
                $pdo->quote($datum['uses_total']) . "," .
                $pdo->quote($datum['uses_customer']) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote($company) . "," .
                $pdo->quote($store) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
                
            if (!empty($queryContent)) {
                $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`code`= values(`code`), " .
                "`name`= values(`name`), " .
                "`type`= values(`type`), " .
                "`delivery`= values(`delivery`), " .
                "`discount`= values(`discount`), " .
                "`total`= values(`total`), " .
                "`free_shipping`= values(`free_shipping`), " .
                "`product_ids`= values(`product_ids`), " .
                "`product_codes`= values(`product_codes`), " .
                "`product_names`= values(`product_names`), " .
                "`category_codes`= values(`category_codes`), " .
                "`category_names`= values(`category_names`), " .
                "`product_except_ids`= values(`product_except_ids`), " .
                "`product_except_codes`= values(`product_except_codes`), " .
                "`product_except_names`= values(`product_except_names`), " .
                "`category_except_ids`= values(`category_except_ids`), " .
                "`category_except_codes`= values(`category_except_codes`), " .
                "`category_except_names`= values(`category_except_names`), " .
                "`date_start`= values(`date_start`), " .
                "`date_end`= values(`date_end`), " .
                "`uses_total`= values(`uses_total`), " .
                "`uses_customer`= values(`uses_customer`), " .
                "`status`= values(`status`), " .
                "`company_id` = values(`company_id`), " .
                "`store_id` = values(`store_id`), " .
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

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-CUSTOMER-(warning).csv", 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return true;
    }
}
