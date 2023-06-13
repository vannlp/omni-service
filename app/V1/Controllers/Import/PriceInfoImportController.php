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
use App\V1\Models\PriceInfoModel;
use App\V1\Models\ProductModel;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PriceInfoImportController
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
            0  => "id",
            1  => "PRODUCT_ID",
            2  => "CUSTOMER_ID",
            3  => "CUSTOMER_TYPE_ID",
            4  => "SHOP_ID",
            5  => "SHOP_TYPE_ID",
            6  => "from",
            7  => "to",
            8  => "status",
            9  => "PRICE",
            10 => "PRICE_NOT_VAT",
            11 => "PACKAGE_PRICE",
            12 => "PACKAGE_PRICE_NOT_VAT",
            13 => "VAT",
            14 => "created_at",
            15 => "CREATE_USER",
            16 => "updated_at",
            17 => "UPDATE_USER",
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1])
        ];
        $this->_duplicate = [
            4 => trim($this->_header[4]),
            0 => trim($this->_header[0])
        ];
        $this->_notExisted = [
          
        ];
        $this->_number = [
            8  => "status",
            9  => "PRICE",
        ];
        $this->_date = [
            6  => "from",
            7  => "to",
            14 => "created_at",
            16 => "updated_at",
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
                'SHOP_ID' => [
                    'fields' => ['SHOP_ID', 'id', 'SHOP_ID'],
                    'model'  => new PriceInfoModel(),
                ],
                // 'PRODUCT_ID' => [
                //     'fields' => ['id'],
                //     'model'  => new ProductModel(),
            
                // ],
                // 'Category' => [
                //     'fields' => ['code', 'id', 'code'],
                //     'model'  => new CategoryModel(),
                // ],
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
                            'PRODUCT_ID'                => $row[1],
                            'CUSTOMER_ID'               => $row[2],
                            'CUSTOMER_TYPE_ID'          => $row[3],
                            'SHOP_ID'                   => $row[4],
                            'SHOP_TYPE_ID'              => $row[5],
                            'from'                      => $row[6] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[6])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[6]))) : "",
                            'to'                        =>$row[7] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[7])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[7]))) : "",
                            'status'                    => $row[8],
                            'PRICE'                     => $row[9],
                            'PRICE_NOT_VAT'             => $row[10],
                            'PACKAGE_PRICE'             => $row[11],
                            'PACKAGE_PRICE_NOT_VAT'     => $row[12],
                            'VAT'                       => $row[13],
                            'created_at'                => $row[14] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[14])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[14]))) : "",
                            'CREATE_USER'               => $row[15],
                            'updated_at'                => $row[16] ? (DateTime::createFromFormat('d/m/Y H.i.s', $row[16])->format('Y-m-d H:i:s') ?? date("Y-m-d H:i:s", strtotime($row[16]))) : "",
                            'UPDATE_USER'               =>$row[17],
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
                if ($name == 'product_id') {
                   
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
   
        // foreach ($this->_date as $key => $name) {
        //     if (empty($row[$key])) {
        //         continue;
        //     }
           
        //     if ($row[$key] instanceof \DateTime) {
        //         $row[$key] = $row[$key]->format('Y-m-d');
        //         continue;
        //     }
          
        //     $val = strtotime(trim($row[$key]));
           
        //     if ($val > 0) {
        //         $row[$key] = date('Y-m-d H:i:s', $val);
           
        //         continue;
        //     }
        //     $errorDetail .= "[" . $this->_header[$key] . " must be a date!]";
        // }
         
        return $errorDetail;
      
    }

    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }
       
        DB::beginTransaction();
        $queryHeader = "INSERT INTO `price_info_dms_imports` (" .
            "`product_id`, " .
            "`customer_id`," .
            "`customer_type_id`," .
            "`shop_id`," .
            "`shop_type_id`," .
            "`from`," .
            "`to`," .
            "`status`," .
            "`price`," .
            "`price_not_vat`," .
            "`package_price`," .
            "`package_price_not_vat`," .
            "`vat`," .
            "`created_at`," .
            "`create_user`," .
            "`updated_at`," .
            "`update_user`, " . 
            "`created_by`, " . 
            "`updated_by`) VALUES ";
         
        $now = date('Y-m-d H:i:s', time());
        $me = TM::getCurrentUserId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $queryContent .=
            
                "(" .
                $pdo->quote($datum['PRODUCT_ID'])  . "," .
                $pdo->quote($datum['CUSTOMER_ID']) . "," .
                $pdo->quote($datum['CUSTOMER_TYPE_ID']) . "," .
                $pdo->quote($datum['SHOP_ID']) . "," .
                $pdo->quote($datum['SHOP_TYPE_ID']) . "," .
                $pdo->quote(date('Y-m-d H:i:s',strtotime(trim($datum['from'])))) . "," .
                $pdo->quote(date('Y-m-d H:i:s',strtotime(trim($datum['to'])))) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote($datum['PRICE']) . "," .
                $pdo->quote($datum['PRICE_NOT_VAT']) . "," .
                $pdo->quote($datum['PACKAGE_PRICE']) . "," .
                $pdo->quote($datum['PACKAGE_PRICE_NOT_VAT']) . "," .
                $pdo->quote($datum['VAT']) . "," .
                $pdo->quote(date('Y-m-d H:i:s',strtotime(trim($datum['created_at'])))) . "," .
                $pdo->quote($datum['CREATE_USER']) . "," .
                $pdo->quote(date('Y-m-d H:i:s',strtotime(trim($datum['updated_at'])))) . "," .
                $pdo->quote($datum['UPDATE_USER']) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($me) . "), ";

        }
       

        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "updated_at='$now', updated_by=$me";
            DB::statement($queryUpdate);
        }
        // Commit transaction
        DB::commit();
    }
}