<?php



namespace App\V1\Controllers\Import;

use App\Product;
use App\ProductHub;
use App\V1\Models\ProductModel;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\User;
use App\V1\Models\CityModel;
use App\V1\Models\DistrictModel;
use App\V1\Models\WardModel;
use App\Ward;
use App\City;
use App\District;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductSaleAreaImportController
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
            0 => "Tỉnh Thành Phố",
            1 => "Mã TP",
            2 => "Quận Huyện",
            3 => "Mã QH",
            4 => "Phường Xã",
            5 => "Mã PX",
            6 => "Mã Sản Phẩm",
        ];
        $this->_required = [
            6 => trim($this->_header[6]),
        ];
        $this->_duplicate = [];
        $this->_notExisted = [];
        $this->_number = [
            1 => trim($this->_header[1]),
            3 => trim($this->_header[3]),
            5 => trim($this->_header[5]),
        ];
        $this->_date = [];
    }

    public function import(Request $request)
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
        $warnings = [];
        $errors = [];
        $valids = [];
        $countRow = 0;
        $error = false;
        $empty = true;
        $fileName = "Import-Product-Sale-Area_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        $store_id   = TM::getCurrentStoreId();
        try {
            $dataCheck = [
                'city' => [
                    'fields' => ['code', 'full_name', 'code', 'id'],
                    'model'  => new CityModel(),
                ],
                'district' => [
                    'fields' => ['code', 'full_name', 'code', 'id'],
                    'model'  => new DistrictModel(),
                ],
                'ward' => [
                    'fields' => ['code', 'full_name', 'code', 'id'],
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
            // dd($existedChk);
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
            $totalLines = 0;
            $saleArea = [
                'city'          => array(),
                'district'      => array(),
                'ward'          => array()
            ];
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
                    // if ($totalLines > 10001) {
                    //     return response()->json([
                    //         'status'  => 'error',
                    //         'message' => Message::get("V013", Message::get("data_row"), 10000),
                    //     ], 400);
                    // }
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
                        if (!isset($row[6]) || $row[6] === "" || $row[6] === null) {
                            $row[]      = "Required " . $this->_header[6];
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }
                        $err = "";
                        $errorDetail = $this->checkValidRow($row, $codeUsed, $existedChk);
                        $prod_code = explode(',',$row[6]);
                        $product  = Product::model()->whereIn('code', $prod_code)->where('store_id',$store_id)->pluck('id');
                        
                        if(count($product) < count($prod_code)){
                            $arr = array_filter($prod_code,function ($p){
                                if(!Product::model()->where('code', $p)->exists()){
                                    return $p;
                                }
                            });
                            if($arr){
                                $arr = implode(',',array_values($arr));
                                $err .= "[ Sản phẩm $arr không tồn tại ]";
                                if ($err) {
                                    $row[]      = $err;
                                    $warnings[] = array_map(function ($r) {
                                        return "\"" . $r . "\"";
                                    }, $row);
                                    continue;
                                }
                            }
                        }
                        if (count($product) >= 1) {
                            $countRow++;
                            foreach($product as $prod){
                                if(in_array($prod,array_keys($valids))){
                                    $check1 = array_pluck($valids[$prod], 'code');
                                    if(in_array($row[1],$check1)){
                                        $key1 = array_search($row[1],array_column($valids[$prod], 'code'));
                                        $check2 = array_pluck($valids[$prod][$key1]['districts'], 'code');
                                        if(in_array($row[3],$check2) && !empty($row[3])){
                                            $key2 = array_search($row[3],array_column($valids[$prod][$key1]['districts'], 'code'));
                                            $check3= array_pluck($valids[$prod][$key1]['districts'][$key2]['wards'], 'code');
                                            if(in_array($row[3],$check2) && !empty($row[5])){
                                                if(!in_array($row[5],$check3)){
                                                    if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists() && !Ward::model()->where('code',$row[5])->where('district_code',$row[3])->exists()){
                                                        continue;
                                                    }
                                                    $valids[$prod][$key1]['districts'][$key2]['wards'][] = $this->addData($row, $existedChk, 'ward'); 
                                                    $saleArea['ward'][$prod][] = $row[5];
                                                }
                                            } 
                                        }
                                        if(!in_array($row[3],$check2)){
                                            if(empty($row[5])){
                                                if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists()){
                                                    continue;
                                                }
                                                $valids[$prod][$key1]['districts'][] = $this->addData($row, $existedChk, 'district_single');
                                                $saleArea['district'][$prod][] = $row[3];  
                                            }
                                            if(!empty($row[5])){
                                                if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists() || !Ward::model()->where('code',$row[5])->where('district_code',$row[3])->exists()){
                                                    continue;
                                                }
                                                $valids[$prod][$key1]['districts'][] = $this->addData($row, $existedChk, 'district_ward');
                                                $saleArea['district'][$prod][] = $row[3]; 
                                                $saleArea['ward'][$prod][] = $row[5];
                                            }
                                        }
                                    }
                                    if(!in_array($row[1],$check1) && !empty($row[1])){
                                        if(empty($row[3])){
                                            if(!$existedChk['city'][trim(strtoupper($row[1]))]){
                                                continue;
                                            }
                                            $valids[$prod][] = $this->addData($row, $existedChk, 'city'); 
                                            $saleArea['city'][$prod][] = $row[1];
                                        }
                                        if(!empty($row[3]) && empty($row[5])){
                                            if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists()){
                                                continue;
                                            }
                                            $valids[$prod][] = $this->addData($row, $existedChk, 'district'); 
                                            $saleArea['city'][$prod][] = $row[1];  
                                            $saleArea['district'][$prod][] = $row[3];  
                                        }
                                        if(!empty($row[3]) && !empty($row[5])){
                                            if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists() || !Ward::model()->where('code',$row[5])->where('district_code',$row[3])->exists()){
                                                continue;
                                            }
                                            $valids[$prod][] = $this->addData($row, $existedChk, 'all'); 
                                            $saleArea['city'][$prod][] = $row[1]; 
                                            $saleArea['district'][$prod][] = $row[3]; 
                                            $saleArea['ward'][$prod][] = $row[5];
                                        }
                                    }
                                    if(!$row[1]){
                                        $valids[$prod][] = null; 
                                        $saleArea['city'][$prod][] = null; 
                                        $saleArea['district'][$prod][] = null; 
                                        $saleArea['ward'][$prod][] = null;
                                    }
                                }
                                if(!in_array($prod,array_keys($valids))){
                                    if(!empty($row[1]) && empty($row[3])){
                                        if(!$existedChk['city'][trim(strtoupper($row[1]))]){
                                            continue;
                                        }
                                        $valids[$prod] = array($this->addData($row, $existedChk, 'city'));  
                                        $saleArea['city'][$prod][] = $row[1]; 
                                    }
                                    if(!empty($row[3]) && empty($row[5])){
                                        if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists()){
                                            continue;
                                        }
                                        $valids[$prod] = array($this->addData($row, $existedChk, 'district'));  
                                        $saleArea['city'][$prod][] = $row[1];  
                                        $saleArea['district'][$prod][] = $row[3]; 
                                    }
                                    if(!empty($row[3]) && !empty($row[5])){
                                        if(!District::model()->where('code',$row[3])->where('city_code',$row[1])->exists() || !Ward::model()->where('code',$row[5])->where('district_code',$row[3])->exists()){
                                                continue;
                                        }
                                        $valids[$prod] = array($this->addData($row, $existedChk, 'all'));   
                                        $saleArea['city'][$prod][] = $row[1];  
                                        $saleArea['district'][$prod][] = $row[3]; 
                                        $saleArea['ward'][$prod][] = $row[5]; 
                                    }
                                    if(!$row[1]){
                                        $valids[$prod][] = null; 
                                        $saleArea['city'][$prod][] = null; 
                                        $saleArea['district'][$prod][] = null; 
                                        $saleArea['ward'][$prod][] = null;
                                    }
                                }
                            }
                        }
                        // Put error
                        if ($errorDetail) {
                            $error = true;
                        }
                        $errorDetail = trim($errorDetail);
                        $row[] = $errorDetail;
                        $errors[] = array_map(function ($r) {
                            return "\"" . $r . "\"";
                        }, $row);
                        $codeUsed[] = '';
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
            if ($error) {
                return response()->json([
                    'status'  => 'error',
                    'message' => $errors,
                ], 406);
            } else {
                try {
                    $this->startImport($valids, $saleArea, $codeUsed);
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
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
    }

    private function startImport($data, $saleArea, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }
        
        DB::beginTransaction();
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        $me = TM::getCurrentUserId();
        $store = TM::getCurrentStoreId();
        $now = date('Y-m-d H:i:s', time());
        $value = array_keys($data);
        foreach ($value as $datum) {
            $queryContent = "UPDATE products SET ";
            $queryContent .=
                'sale_area = ' . (!empty($data[$datum]) && !in_array(null,$data[$datum]) ? $pdo->quote(json_encode($data[$datum])) : "null") . "," .
                'city_area_code = ' . (!empty($saleArea['city'][$datum]) && !in_array(null,$saleArea['city'][$datum]) ? $pdo->quote(implode(',',$saleArea['city'][$datum])) : "null") . "," .
                'district_area_code = ' . (!empty($saleArea['district'][$datum]) && !in_array(null,$saleArea['district'][$datum]) ? $pdo->quote(implode(',',$saleArea['district'][$datum])) : "null") . "," .  
                'ward_area_code = ' . (!empty($saleArea['ward'][$datum]) && !in_array(null,$saleArea['ward'][$datum]) ? $pdo->quote(implode(',',$saleArea['ward'][$datum])) : "null") . "," .  
                'updated_at = ' . $pdo->quote($now) . "," .  
                'updated_by = ' . (int)$me . " WHERE " .
                'store_id = ' . (int)$store . " AND " .
                'id = ' . (int)$datum . " AND " .
                'deleted_at IS NULL';
            if (!empty($queryContent)) {
                DB::statement($queryContent);
            }
        }
        DB::commit();
    }

    private function checkValidRow(&$row, &$codeUsed, $existedChk = [])
    {
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

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-SALE-AREA-(warning).csv", 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return true;
    }

    private function addData($row, $existedChk = [], $num)
    {
        if($num == 'city'){
            return
                [
                    'code'           => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['code'] ?? null : null,
                    'name'           => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['full_name'] ?? null : null,
                    'districts'      => []
                ];
        }
        if($num == 'district'){
            return
                [
                    'code'           => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['code'] ?? null : null,
                    'name'           => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['full_name'] ?? null : null,
                    'districts'      => !empty($row[3]) ? array([
                        "name"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['full_name'] ?? null : null,
                        "code"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['code'] ?? null : null,
                        "city_code"     => !empty($row[1]) ? $row[1] : null,
                        "wards"         => []
                    ]) : [],
                ];
        }
        if($num == 'district_ward'){
            return
                [
                    "name"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['full_name'] ?? null : null,
                    "code"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['code'] ?? null : null,
                    "city_code"     => !empty($row[1]) ? $row[1] : null,
                    "wards"         => !empty($row[5]) ? array([
                        "name"          => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['full_name'] ?? null : null,
                        "code"          => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['code'] ?? null : null,
                        "city_code"     => !empty($row[1]) ? $row[1] : null,
                        "district_code" => !empty($row[3]) ? $row[3] : null
                    ]) : [],
                ];
        }
        if($num == 'district_single'){
            return
                [
                    
                    "name"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['full_name'] ?? null : null,
                    "code"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['code'] ?? null : null,
                    "city_code"     => !empty($row[1]) ? $row[1] : null,
                    "wards"         => []
                ];
        }
        if($num == 'ward'){
            return
                [
                    "name"          => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['full_name'] ?? null : null,
                    "code"          => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['code'] ?? null : null,
                    "city_code"     => !empty($row[1]) ? $row[1] : null,
                    "district_code" => !empty($row[3]) ? $row[3] : null
                ];
        }
        if($num == 'all'){
            return
                [
                    'code'           => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['code'] ?? null : null,
                    'name'           => !empty($row[1]) ? $existedChk['city'][trim(strtoupper($row[1]))]['full_name'] ?? null : null,
                    'districts'      => !empty($row[3]) ? array([
                        "name"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['full_name'] ?? null : null,
                        "code"          => !empty($row[3]) ? $existedChk['district'][trim(strtoupper($row[3]))]['code'] ?? null : null,
                        "city_code"     => !empty($row[1]) ? $row[1] : null,
                        "wards"         => !empty($row[5]) ? array([
                            "name"          => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['full_name'] ?? null : null,
                            "code"          => !empty($row[5]) ? $existedChk['ward'][trim(strtoupper($row[5]))]['code'] ?? null : null,
                            "city_code"     => !empty($row[1]) ? $row[1] : null,
                            "district_code" => !empty($row[3]) ? $row[3] : null
                        ]) : [],
                    ]) : [],
                ];
        }
    }
}
