<?php



namespace App\V1\Controllers\Import;

use App\Product;
use App\ProductHub;
use App\V1\Models\ProductModel;
use App\Supports\Message;
use App\TM;
use App\Unit;
use App\User;
use App\V1\Models\ProductHubModel;
use App\V1\Models\UserModel;
use App\Ward;
use Box\Spout\Reader\ReaderFactory;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class ProductHubImportController
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
            0 => "Mã NPP",
            1 => "Mã sản phẩm",
            2 => "SL giới hạn/ngày",
        ];
        $this->_required = [
            0 => trim($this->_header[0]),
            1 => trim($this->_header[1]),
            2 => trim($this->_header[2]),
        ];
        $this->_duplicate = [];
        $this->_notExisted = [
            0 => trim($this->_header[0]),
        ];
        $this->_number = [];
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

        $errors = [];
        $valids = [];
        $countRow = 0;
        $error = false;
        $empty = true;
        $fileName = "Import-Product-Hubs_By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];

        try {
            $dataCheck = [
                'Mã NPP' => [
                    'fields' => ['code', 'code', 'id'],
                    'model'  => new UserModel(),
                ],
                'Mã sản phẩm' => [
                    'fields' => ['code', 'id', 'code'],
                    'model'  => new ProductModel(),

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
//            $dataCheck = null;
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
                        $userId = User::where('code', $row[0])->first();
                        if(empty($userId)){
                            $row[]      = $row[0] . " Không tồn tại";
                            $warnings[] = array_map(function ($r) {
                                return "\"" . $r . "\"";
                            }, $row);
                            continue;
                        }

                        // Check Valid
                        if (!isset($row[1]) || $row[1] === "" || $row[1] === null) {
                            $row[]      = "Required " . $this->_header[1];
                            $warnings[] = array_map(function ($r) {
                                    return "\"" . $r . "\"";
                                }, $row);
                            continue;
                        }
                        $err = "";
                        $errorDetail = $this->checkValidRow($row, $codeUsed, $existedChk);
                        // $product  = Product::where('code', $row[1])->first();
                        $prod_code = explode(',',trim($row[1]));
                        $product  = Product::model()->whereIn('code', $prod_code)->get();
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
                        if (!empty($product)) {
                            $countRow++;
                            foreach ($product as $value) {
                                if (!empty($value->unit_id)) {
                                    $unitName = Unit::find($value->unit_id);
                                }
                                $productHub = ProductHub::where([
                                    'user_id' => $userId->id,
                                    'product_code' => $value->code
                                ])->first();
                                if (!empty($productHub)) { // exist
                                    $productHub->refresh();
                                    $productHub->limit_date = $row[2] ?? $productHub->limit_date;
                                    $productHub->save();
                                }
                                if (empty($productHub)) { // them moi
                                    $valids[] = [
                                        'user_id'           => $userId->id ?? NULL,
                                        'product_id'        => Arr::get($value, 'id'),
                                        'product_code'      => !empty($value->code) ? $value->code : NULL,
                                        'product_name'      => Arr::get($value, 'name'),
                                        'unit_id'           => Arr::get($value, 'unit_id'),
                                        'unit_name'         => $unitName['name'] ?? NULL,
                                        'limit_date'        => !empty($row[2]) ? $row[2] : 0
                                    ];
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
                    $this->startImport($valids, $codeUsed);
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

    private function startImport($data, $codeUsed = [])
    {
        if (empty($data)) {
            return 0;
        }

        DB::beginTransaction();
        $queryHeader = "INSERT INTO `product_hubs`(" .
            "`user_id`, " .
            "`product_id`," .
            "`product_code`," .
            "`product_name`," .
            "`unit_id`," .
            "`unit_name`," .
            "`store_id`," .
            "`company_id`," .
            "`limit_date`," .
            "`created_at`," .
            "`created_by`) VALUES ";
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        $me = TM::getCurrentUserId();
        $store = TM::getCurrentStoreId();
        $company = TM::getCurrentCompanyId();
        $now = date('Y-m-d H:i:s', time());
        foreach ($data as $datum) {
            $queryContent .=
                "(" .
                $pdo->quote($datum['user_id']) . "," .
                ($datum['product_id'] ? $pdo->quote($datum['product_id']) : "null") . "," .
                ($datum['product_code'] ? $pdo->quote($datum['product_code']) : "null") . "," .
                $pdo->quote($datum['product_name']) . "," .
                ($datum['unit_id'] ? $pdo->quote($datum['unit_id']) : "null") . "," .
                ($datum['unit_name'] ? $pdo->quote($datum['unit_name']) : "null") . "," .
                $pdo->quote($store) . "," .
                $pdo->quote($company) . "," .
                $pdo->quote($datum['limit_date']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", "));
            DB::statement($queryUpdate);
        }
        // Commit transaction
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
}
