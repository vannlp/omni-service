<?php

/**
 * User: kpistech2
 * Date: 2020-11-25
 * Time: 22:34
 */

namespace App\V1\Controllers\Import;

use App\Price;
use App\PriceDetail;
use App\Product;
use App\Supports\Message;
use App\Supports\TM_Error;
use App\Sync\Models\ProductModel;
use App\TM;
use App\V1\Models\PriceDetailModel;
use App\V1\Models\PriceModel;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PriceImportController
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
            0 => "Mã bảng giá (*)",
            1 => "Tên bảng giá",
            2 => "Mã sản phẩm (*)",
            3 => "Từ ngày (*)",
            4 => "Đến ngày (*)",
            5 => "Giá bán (*)",
            6 => "Trạng thái  (*)",
        ];
        $this->_required   = [
            // 0 => trim($this->_header[0]),
            // 1 => trim($this->_header[1]),
            // 2 => trim($this->_header[2]),
            // 3 => trim($this->_header[3]),
            // 4 => trim($this->_header[4]),
            // 5 => trim($this->_header[5]),
        ];
        $this->_duplicate  = [];
        $this->_notExisted = [];
        $this->_number     = [
            // 4 => 'status',
            // 5 => 'price',
        ];
        $this->_date       = [
            3 => 'from',
            4 => 'to',
        ];
    }

    public function import(Request $request)
    {
        ini_set('max_execution_time', 3000);
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
        $fileName = "Import-Price-By-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        $company_id = TM::getCurrentCompanyId();
        $store_id   = TM::getCurrentStoreId();

        try {
            $dataCheck = [
                'product_code' => [
                    'fields' => ['code', 'id', 'code'],
                    'model'  => new ProductModel(),
                    'where'  => ' `store_id` = ' . $store_id,
                ],
                'price'        => [
                    'fields' => ['code', 'id', 'code'],
                    'model'  => new PriceModel(),
                    'where'  => ' `company_id` = ' . $company_id,
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
                    if ($key == 10) break;
                    $row = [
                        0 => $row[0] ?? "",
                        1 => $row[1] ?? "",
                        2 => $row[2] ?? "",
                        3 => $row[3] ?? "",
                        4 => $row[4] ?? "",
                        5 => $row[5] ?? "",
                        6 => $row[6] ?? "",
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
                        // $countRow++;
                        foreach ($this->_required as $key => $name) {
                            if (!isset($row[$key]) || $row[$key] === "" || $row[$key] === null) {
                                $row[]      = "Required " . $this->_header[0];
                                $warnings[] = array_map(function ($r) {
                                    if (gettype($r) == 'string') {
                                        return "\"" . $r . "\"";
                                    } else if (gettype($r) == 'integer') {
                                        return $r;
                                    }
                                }, $row);
                                continue;
                            }
                        }
                        $err = "";

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

                            $err .= "[" . $this->_header[$key] . " Bắt buộc phải là ngày!]";
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                if (gettype($r) == 'string') {
                                    return "\"" . $r . "\"";
                                } else if (gettype($r) == 'integer') {
                                    return $r;
                                }
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
                                    if (gettype($r) == 'string') {
                                        return "\"" . $r . "\"";
                                    } else if (gettype($r) == 'integer') {
                                        return $r;
                                    }
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
                        $tmp = strtoupper($row[2]);
                        if (in_array($tmp, $codeUsed)) {
                            $row[]      = "Duplicate Code " . $tmp;
                            $warnings[] = array_map(function ($r) {
                                if (gettype($r) == 'string') {
                                    return "\"" . $r . "\"";
                                } else if (gettype($r) == 'integer') {
                                    return $r;
                                }
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
                                if (gettype($r) == 'string') {
                                    return "\"" . $r . "\"";
                                } else if (gettype($r) == 'integer') {
                                    return $r;
                                }
                            }, $row);
                            continue;
                        }

                        $product = Product::where('code', $row[2])->first();

                        if(empty($product)){
                            $err .= "[$row[2] không tồn tại, Vui lòng nhập khác]";
                        }

                        if ($err) {
                            $row[]      = $err;
                            $warnings[] = array_map(function ($r) {
                                if (gettype($r) == 'string') {
                                    return "\"" . $r . "\"";
                                } else if (gettype($r) == 'integer') {
                                    return $r;
                                }
                            }, $row);
                            continue;
                        }
                        if (!empty($product)) {
                            if (empty($existedChk['price'][Str::upper($row[0])])) {
                                throw new \Exception("Mã bảng giá không tồn tại.");
                            }
                            $price_detail = PriceDetail::where('price_id', $existedChk['price'][Str::upper($row[0])])->where('product_id', $product->id)->first();
                            if (!empty($price_detail)) {
                                $countRow++;
                                $price_detail->refresh();
                                $price_detail->price   = $row[5] ?? $price_detail->price;
                                $price_detail->status   = !empty($row[6]) ? $row[6] : 0 ;
                                $price_detail->save();
                                continue;
                            }
                        }
                        $countRow++;
                        if (empty($price_detail)) {
                            $valids[] = [
                                'price_code'   => $row[0],
                                'product_code' => $row[2],
                                'from'         => $row[3],
                                'to'           => $row[4],
                                'price'        => $row[5],
                                'status'       =>  !empty($row[6]) ? $row[6] : 0,
                            ];
                        }
                    }
                }
//                dd($valids);
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
            return response()->json(['status' => 'error', 'message' => $ex->getMessage() . "|" . $ex->getLine()], 400);
        }

        try {

            $this->startImport($valids);

            $this->writeWarning($warnings, $this->_header); // if warnings

            return response()->json([
                'status'  => 'success',
                'message' => Message::get("R014", $countRow . " " . Message::get('data_row')),
                'warning' => !empty($warnings) ? $warnings : null,
            ], 200);

            //  return  Excel::download(new ExportErrorPrice($warnings ?? [], $this->_header ?? []), 'price_detail.xlsx');
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
        $queryHeader  = "INSERT INTO `price_details` (" .
            "`price_id`, " .
            "`product_id`, " .
            "`from`, " .
            "`to`, " .
            "`price`, " .
            "`status`, " .
            "`created_at`, " .
            "`created_by`, " .
            "`updated_at`, " .
            "`updated_by`) VALUES ";
        $now          = date('Y-m-d H:i:s', time());
        $me           = TM::getCurrentUserId();
        $company      = TM::getCurrentCompanyId();
        $queryContent = "";
        /** @var \PDO $pdo */
        $pdo = DB::getPdo();
        foreach ($data as $datum) {
            $price   = Price::where([
                'code'    => $datum['price_code'],
                'deleted' => 0
            ])->first();
            $product = Product::where([
                'code'    => $datum['product_code'],
                'deleted' => 0
            ])->first();
            $queryContent
                .= "(" .
                $pdo->quote($price->id) . "," .
                $pdo->quote($product->id) . "," .
                $pdo->quote(date_format(date_create($datum['from']), "Y-m-d H:i:s")) . "," .
                $pdo->quote(date_format(date_create($datum['to']), "Y-m-d H:i:s")) . "," .
                $pdo->quote($datum['price']) . "," .
                $pdo->quote($datum['status']) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "," .
                $pdo->quote($now) . "," .
                $pdo->quote($me) . "), ";
        }
        //        print_r($queryContent);die;
        if (!empty($queryContent)) {
            $queryUpdate = $queryHeader . (trim($queryContent, ", ")) .
                " ON DUPLICATE KEY UPDATE " .
                "`price_id`= values(`price_id`), " .
                "`product_id`= values(`product_id`), " .
                "`from`= values(`from`), " .
                "`to`= values(`to`), " .
                "`status`= values(`status`), " .
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

        $fileName = (date('YmdHis')) . "-IMPORT-CUSTOMER.csv";
        header('Content-Encoding: UTF-8');
        header('Content-Description: File Transfer');
        header('Content-Type: application/csv; charset=utf-8');
        header("Content-Disposition: attachment; filename=" . $fileName);
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        $dir = storage_path('Imports/Warnings');
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }

        $csv = $header ? "\"" . implode("\",\"", $header) . "\"\n" : "";
        foreach ($data as $record) {
            $csv .= implode(',', $record) . PHP_EOL;
        }

        $path = $dir . "/" . $fileName;
        $csv_handler = fopen($path, 'w');
        ob_clean();
        fwrite($csv_handler, chr(239) . chr(187) . chr(191) . $csv);
        ob_flush(); // dump buffer
        fclose($csv_handler);
        return true;
    }

}
