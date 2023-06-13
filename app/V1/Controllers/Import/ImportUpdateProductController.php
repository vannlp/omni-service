<?php


namespace App\V1\Controllers\Import;


use App\Category;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\User;
use App\File;
use App\Property;
use App\PropertyVariant;
use App\UserGroup;
use App\V1\Models\CategoryModel;
use App\V1\Models\ManufactureModel;
use App\V1\Models\AgeModel;
use App\V1\Models\ProductModel;
use App\V1\Models\AreaModel;
use App\V1\Models\UnitModel;
use App\V1\Models\BrandModel;
use App\V1\Models\SpecificationModel;
use App\V1\Models\UserGroupModel;
use App\V1\Models\PropertyModel;
use App\V1\Models\PropertyVariantModel;
use App\V1\Models\UserModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use GuzzleHttp\Client;

class ImportUpdateProductController
{
    protected $_header;
    protected $_required;
    protected $_duplicate;
    protected $_notExisted;
    protected $_number;
    protected $_date;
    protected $_dbExisted;

    public function __construct()
    {
        $this->_header = [
            0  => "Mã sản phẩm (*)",
            1  => "Tên sản phẩm (*)",
            2  => "Giá bán (*)",
            3  => "Mã đơn vị tính (*)",
            4  => "Mã quy cách (*)",
            5  => "% thuế suất GTGT",
            6  => "Tình trạng (*)",
            7  => "Loại sản phẩm (*)",
            8  => "Sản phẩm lạnh (*)",
            9  => "Mô tả ngắn (*)",
            10 => "Mô tả (*)",
            11 => "Khối lượng (*)",
            12 => "Đơn vị khối lượng (*)",
            13 => "Chiều rộng (*)",
            14 => "Chiều cao",
            15 => "Chiều dài (*)",
            16 => "Đơn vị độ dài (*)",
            17 => "Tag sản phẩm",
            18 => "Mã danh mục sản phẩm (*)",
            19 => "Ảnh đại diện (*)",
            20 => "Bộ sưu tập ảnh",
            21 => "Ngày tạo",
            22 => "Mã độ tuổi",
            23 => "Hạn sử dụng",
            24 => "CADCODE",
            25 => "Manufacture code",
            26 => "Industry code",
            27 => "Dung tích",
            28 => "Brand code",
            29 => "Brandy code",
            30 => "Cat",
            31 => "Subcat",
            32 => "Division",
            33 => "Source",
            34 => "Packing",
            35 => "SKU",
            36 => "CadcodeSubcat",
            37 => "SKU Name",
            38 => "SKU Standard",
            39 => "Type",
            40 => "Attribute",
            41 => "Variant",       
            42 => "CadcodeBrand",
            43 => "CadcodeBrandy",
            44 => "Mã nhóm đối tượng",
            45 => "Mã biến thể cha (bộ lọc)",
            46 => "Mã biến thể liên quan (PDP)",
            47 => "Meta Title",
            48 => "Meta Description",
            49 => "Meta Keyword",
            50 => "Meta Robot",
            51 => "Slug",
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
        ];
        $this->_duplicate = [
            0 => trim($this->_header[0]),
        ];
        $this->_notExisted = [];
        $this->_dbExisted = [
            0 => trim($this->_header[0]),
            3 => trim($this->_header[3]),
            4 => trim($this->_header[4]),
            18 => trim($this->_header[18]),
            22 => trim($this->_header[22]),
            25 => trim($this->_header[25]),
            26 => trim($this->_header[26]),
            28 => trim($this->_header[28]),
            29 => trim($this->_header[29]),
            44 => trim($this->_header[44]),
            45 => trim($this->_header[45]),
            46 => trim($this->_header[46]),
        ];
        $this->_number = [];
        $this->_date = [];
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
        $warnings = [];
        $valids = [];
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
                'Mã sản phẩm (*)' => [
                    'fields' => ['code', 'id', 'code', 'name'],
                    'model'  => new ProductModel(),
                    'where' => ' `store_id` = ' . TM::getCurrentStoreId(),
                ],
                'Mã đơn vị tính (*)' => [
                    'fields' => ['code', 'id', 'code', 'name'],
                    'model'  => new UnitModel(),
                    'where' => ' `company_id` = ' . TM::getCurrentCompanyId(),
                ],
                'Mã quy cách (*)' => [
                    'fields' => ['code', 'id', 'code', 'value'],
                    'model'  => new SpecificationModel(),
                    'where' => ' `store_id` = ' . TM::getCurrentStoreId(),
                ],
                'Industry code' => [
                    'fields' => ['code', 'id', 'code', 'name'],
                    'model'  => new AreaModel(),
                    'where' => ' `company_id` = ' . TM::getCurrentCompanyId(),
                ],
                'brand' => [
                    'fields' => ['id', 'id', 'name', 'slug'],
                    'model'  => new BrandModel(),
                    'where' => ' `store_id` = ' . TM::getCurrentStoreId(),
                ],
                'Manufacture code' => [
                    'fields' => ['code', 'id', 'code', 'name'],
                    'model'  => new ManufactureModel(),
                    'where' => ' `company_id` = ' . TM::getCurrentCompanyId(),

                ],
                'Mã độ tuổi' => [
                    'fields' => ['code', 'id', 'code', 'name'],
                    'model'  => new AgeModel(),
                    'where' => ' `company_id` = ' . TM::getCurrentCompanyId(),

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
                        13  => $row[13] ?? "",
                        14  => $row[14] ?? "",
                        15  => $row[15] ?? "",
                        16  => $row[16] ?? "",
                        17  => $row[17] ?? "",
                        18  => $row[18] ?? "",
                        19  => $row[19] ?? "",
                        20  => $row[20] ?? "",
                        21  => $row[21] ?? "",
                        22  => $row[22] ?? "",
                        23  => $row[23] ?? "",
                        24  => $row[24] ?? "",
                        25  => $row[25] ?? "",
                        26  => $row[26] ?? "",
                        27  => $row[27] ?? "",
                        28  => $row[28] ?? "",
                        29  => $row[29] ?? "",
                        30  => $row[30] ?? "",
                        31  => $row[31] ?? "",
                        32  => $row[32] ?? "",
                        33  => $row[33] ?? "",
                        34  => $row[34] ?? "",
                        35  => $row[35] ?? "",
                        36  => $row[36] ?? "",
                        37  => $row[37] ?? "",
                        38  => $row[38] ?? "",
                        39  => $row[39] ?? "",
                        40  => $row[40] ?? "",
                        41  => $row[41] ?? "",
                        42  => $row[42] ?? "",
                        43  => $row[43] ?? "",
                        44  => $row[44] ?? "",
                        45  => $row[45] ?? "",
                        46  => $row[46] ?? "",
                        47  => $row[47] ?? "",
                        48  => $row[48] ?? "",
                        49  => $row[49] ?? "",
                        50  => $row[50] ?? "",
                        51  => $row[51] ?? ""
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
                        //Check Duplicate Code
                        $tmp = strtoupper($row[0]);
                        if (in_array($tmp, $codeUsed)) {
                            $err .= "[$tmp duplicate code]";
                        }
                        if (!in_array($tmp, $codeUsed)) {
                            $codeUsed[] = $row[0];
                        }
                        foreach ($this->_dbExisted as $key => $name) {
                            if (empty($row[$key])) {
                                continue;
                            }
                            $val = trim(strtoupper($row[$key]));
                            if ($key == 0 && empty($existedChk[$name][$val])) {
                                $err .= "[$val not exist, please input other]";
                            }
                            if ($key > 0){
                                if($key == 18){
                                    $cateCode = explode(',',$row[$key]);
                                    $cate_check = Category::model()->whereIn('code',$cateCode)->first();
                                    if(empty($cate_check)){
                                        $err .= "[$val not exist, please input other]";
                                    }
                                }
                                if($key == 44){
                                    $groupCode = explode(',',$row[$key]);
                                    $group_check = UserGroup::model()->whereIn('code',$groupCode)->where('company_id',TM::getCurrentCompanyId())->first();
                                    if(empty($group_check)){
                                        $err .= "[$val not exist, please input other]";
                                    }
                                }
                                if($key == 45 || $key == 46){
                                    $variantCode = explode(',',$row[$key]);
                                    $variant_check = PropertyVariant::model()->whereIn('code',$variantCode)->where('company_id',TM::getCurrentCompanyId())->first();
                                    if(empty($variant_check)){
                                        $err .= "[$val not exist, please input other]";
                                    }
                                }
                                if($key == 28 || $key == 29){
                                    if (empty($existedChk['brand'][$val])) {
                                        $err .= "[$val not exist, please input other]";
                                    }
                                }
                                if (!in_array($key, [18,44,45,46,28,29]) &&empty($existedChk[$name][$val])) {
                                    $err .= "[$val not exist, please input other]";
                                }
                            }
                            
                        }

                        if ($err) {
                            $row[] = $err;
                            $warnings[] = array_map(function ($r) {
                                if(gettype($r) == 'object'){
                                    return  "\"" . date_format($r,"d/m/Y") . "\"";
                                }
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        // if (!empty($row[24])) {
                        //     $image_cut = explode(";base64,", $row[24]);
                        //     $imgData = base64_decode($image_cut[1]);
                        //     $uniqId = strtoupper(uniqid());
                        //     $fileName = $uniqId . ".png";
                        //     $filePath = storage_path() . "/$fileName";
                        //     file_put_contents($filePath, $imgData);

                        //     $client = new Client([
                        //         'headers'      => [
                        //             'Content-Type'        => 'image/png, image/jpeg, image/webp',
                        //             'Content-Disposition' => 'attachment; form-data; filename=' . $fileName,
                        //         ],
                        //         'verify' => false
                        //     ]);

                        //     $response = $client->post(env("UPLOAD_URL") . "/upload/" . TM::getCurrentCompanyCode(), [
                        //         'multipart' => [
                        //             [
                        //                 'name'         => $fileName,
                        //                 'contents'     => fopen($filePath, 'r')
                        //             ]
                        //         ],
                        //     ]);
                        //     $result = json_decode($response->getBody()->getContents(), true);
                        //     $param     = [
                        //         'code'       => $result['id'],
                        //         'file_name'  => $fileName,
                        //         'title'      => $uniqId,
                        //         'extension'  => "png",
                        //         'size'       => $result['size'],
                        //         'version'    => 1,
                        //         'is_active'  => 1,
                        //         'company_id' => TM::getCurrentCompanyId(),
                        //     ];
                        //     $r = File::create($param);
                        //     $row[24] = $r->id;
                        //     // unlink($filePath);

                        // }
                        if(!empty($row[18])){
                            $cate_code = explode(',',$row[18]);
                            $cate_id = Category::model()->whereIn('code',$cate_code)->pluck('id')->toArray();
                            $cate = !empty($cate_id) ? implode(',',$cate_id) : null;
                        }
                        if(!empty($row[19])){
                            $file_name = substr($row[19],strpos($row[19],"file/")+5);
                            $file_thumbnail = File::model()->where('code',$file_name)->first();
                        }
                        if(!empty($row[20])){
                            $gallery_link = explode(',',$row[20]);
                            $gallery_code = array_map(function($a){return substr($a,strpos($a,"file/")+5);},$gallery_link);
                            $gallery_id = File::model()->whereIn('code',$gallery_code)->pluck('id')->toArray();
                            $gallery = !empty($gallery_id) ? implode(',',$gallery_id) : null;
                        }
                        if(!empty($row[44])){
                            $list_code = explode(',',$row[44]);
                            $list = UserGroup::model()->whereIn('code',$list_code)->where('company_id',TM::getCurrentCompanyId())->select('id','code','name')->get();
                            $list_group = !empty($list) ? $list->toArray() : [];
                            $list_id = !empty($list) ? implode(',',$list->pluck('id')->toArray()) : null;
                        }
                        if(!empty($row[45])){
                            $variant_code = explode(',',$row[45]);
                            $list_variant = PropertyVariant::model()->whereIn('code',$variant_code)->where('company_id',TM::getCurrentCompanyId())->where('store_id',TM::getCurrentStoreId())->select('id','code','name')->get();
                            $variant = !empty($list_variant) ? array_map(function($a){$a['checked'] = 1; return $a;},$list_variant->toArray()) : [];
                            $variant_id = !empty($list_variant) ? implode(',',$list_variant->pluck('id')->toArray()) : null;
                        }
                        if(!empty($row[46])){
                            $child_code = explode(',',$row[46]);
                            $list_child = PropertyVariant::model()->whereIn('code',$child_code)->where('company_id',TM::getCurrentCompanyId())->where('store_id',TM::getCurrentStoreId())->select('id','code','name')->get();
                            $child = !empty($list_child) ? $list_child->toArray() : [];
                            $child_id = !empty($list_child) ? implode(',',$list_child->pluck('id')->toArray()) : null;
                        }
                        if(!empty($row[49])){
                            $list_keyword = explode(',',$row[49]);
                            $keyword = array_map(function($a){$a = ["name" => $a, "id" => $a]; return $a;},$list_keyword);
                        }
                        $countRow++;
                        $valids[] = [
                            'code'                      => $row[0],
                            'name'                      => $row[1] ?? null,
                            'price'                     => $row[2] ?? null,
                            // 'is_active'                 => 1,
                            'unit_id'                   => !empty($row[3]) ? $existedChk['Mã đơn vị tính (*)'][trim(strtoupper($row[3]))]['id'] ?? null : null,
                            'specification_id'          => !empty($row[4]) ? $existedChk['Mã quy cách (*)'][trim(strtoupper($row[4]))]['id'] ?? null : null,
                            'tax'                       => !empty($row[5]) ? $row[5] : null,
                            'status'                    => !empty($row[6]) ? $row[6] : null,
                            'type'                      => !empty($row[7]) ? $row[7] : null,
                            'is_cool'                   => !empty($row[8]) ? $row[8] : null,
                            'short_description'         => !empty($row[9]) ? $row[9] : null,
                            'description'               => !empty($row[10]) ? $row[10] : null,
                            'weight'                    => !empty($row[11]) ? $row[11] : null,
                            'weight_class'              => !empty($row[12]) ? $row[12] : null,
                            'width'                     => !empty($row[13]) ? $row[13] : null,
                            'height'                    => !empty($row[14]) ? $row[14] : null,
                            'length'                    => !empty($row[15]) ? $row[15] : null,
                            'length_class'              => !empty($row[16]) ? $row[16] : null,
                            'tags'                      => !empty($row[17]) ? $row[17] : null,
                            'category_ids'              => !empty($cate) ? $cate : null,
                            'thumbnail'                 => !empty($file_thumbnail) ? $file_thumbnail->id : null,
                            'gallery_images'            => !empty($gallery) ? $gallery : null,
                            'p_sku'                     => !empty($row[35]) ? $row[35] : null,
                            'sku_name'                  => !empty($row[37]) ? $row[37] : null,
                            'sku_standard'              => !empty($row[38]) ? $row[38] : null,
                            'custom_date_updated'       => !empty($row[21]) ? date_format($row[21],"Y-m-d H:i:s") : null,
                            'age_id'                    => !empty($row[22]) ? $existedChk['Mã độ tuổi'][trim(strtoupper($row[22]))]['id'] ?? null : null,
                            'expiry_date'               => !empty($row[23]) ? $row[23] : null,
                            'manufacture_id'            => !empty($row[25]) ? $existedChk['Manufacture code'][trim(strtoupper($row[25]))]['id'] ?? null : null,
                            'area_id'                   => !empty($row[26]) ? $existedChk['Industry code'][trim(strtoupper($row[26]))]['id'] ?? null : null,
                            'area_name'                 => !empty($row[26]) ? $existedChk['Industry code'][trim(strtoupper($row[26]))]['name'] ?? null : null,
                            'capacity'                  => !empty($row[27]) ? $row[27] : null,
                            'brand_id'                  => !empty($row[28]) ? $existedChk['brand'][trim(strtoupper($row[28]))]['id'] ?? null : null,
                            'child_brand_id'            => !empty($row[29]) ? $existedChk['brand'][trim(strtoupper($row[29]))]['id'] ?? null : null,
                            'child_brand_name'          => !empty($row[29]) ? $existedChk['brand'][trim(strtoupper($row[29]))]['name'] ?? null : null,
                            'cat'                       => !empty($row[30]) ? $row[30] : null,
                            'subcat'                    => !empty($row[31]) ? $row[31] : null,
                            'cadcode'                   => !empty($row[24]) ? $row[24] : null,
                            'cad_code_subcat'           => !empty($row[36]) ? $row[36] : null,
                            'cad_code_brand'            => !empty($row[42]) ? $row[42] : null,
                            'cad_code_brandy'           => !empty($row[43]) ? $row[43] : null,
                            'division'                  => !empty($row[32]) ? $row[32] : null,
                            'source'                    => !empty($row[33]) ? $row[33] : null,
                            'packing'                   => !empty($row[34]) ? $row[34] : null,
                            'p_type'                    => !empty($row[39]) ? $row[39] : null,
                            'p_attribute'               => !empty($row[40]) ? $row[40] : null,
                            'p_variant'                 => !empty($row[41]) ? $row[41] : null,
                            'group_code'                => !empty($list_group) ? json_encode($list_group) : null,
                            'group_code_ids'            => !empty($list_id) ? $list_id : null,
                            'property_variant_root'     => !empty($variant) ? json_encode($variant) : null,
                            'property_variant_root_ids' => !empty($variant_id) ? $variant_id : null,
                            'property_variant_ids'      => !empty($child_id) ? $child_id : null,
                            'meta_title'                => !empty($row[47]) ? $row[47] : null,
                            'meta_description'          => !empty($row[48]) ? $row[48] : null,
                            'meta_keyword'              => !empty($keyword) ? json_encode($keyword) : null,
                            'meta_robot'                => !empty($row[50]) ? $row[50] : null,
                            'slug'                      => !empty($row[51]) ? $row[51] : null
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
    }

    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        $me = TM::getCurrentUserId();
        $store = TM::getCurrentStoreId();
        $now = date('Y-m-d H:i:s', time());
        foreach ($data as $key_num => $datum) {
            $queryContent = "UPDATE products SET ";
            foreach($datum as $num => $value){
                if($num == 'code'){
                    continue;
                }
                if(!is_null($value)){
                    $queryContent .=
                        $num. ' = ' . ($pdo->quote($value) ?? "null") . ",";
                }
                
            }
            $queryContent .= 
                'updated_at = ' . $pdo->quote($now) . "," .  
                'updated_by = ' . (int)$me . " WHERE " .
                'store_id = ' . (int)$store . " AND " .
                'code = ' . "'" .$datum['code']. "'" . " AND " .
                'deleted_at IS NULL';
            if (!empty($queryContent)) {
                DB::statement($queryContent);
            }
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

        $csv_handler = fopen($dir . "/" . (date('YmdHis')) . "-IMPORT-PRODUCT-(warning).csv", 'w');
        fwrite($csv_handler, $csv);
        fclose($csv_handler);

        return true;
    }

    
}
