<?php



namespace App\V1\Controllers\Import;

use App\Supports\Message;
use App\TM;
use App\Ward;
use Box\Spout\Reader\ReaderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InterDistrictImportController
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
            0 => "STT",
            1 => "KHU VỰC",
            2 => "TỈNH",
            3 => "QUẬN/HUYỆN/THỊ XÃ",
            4 => "PHƯỜNG/XÃ/THỊ TRẤN",
            5 => "ĐỊA DANH DỊCH VỤ NỘI TỈNH",
            6 => "ĐỊA DANH TÍNH PHỤ PHÍ KẾT NỐI",
        ];
    }

    public function import(Request $request)
    {
        //  dd('ok');
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
        $valids = [];
        $countRow = 0;
        $empty = true;
        $fileName = "CHECK-INNER-DISTRIC-" . (TM::getCurrentUserId()) . "_(" . date('Y_m_d-H_i_s', time()) . ")";

        $file = $input['file'];
        $path = storage_path("Imports");
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }

        $existedChk = [];
        $company_id = TM::getCurrentCompanyId();
        $store_id = TM::getCurrentStoreId();

        try {
            $dataCheck = [];
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

            $codeUsed = [];
            //    $phoneUsed = [];
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

                    ];
                    // $totalLines++;
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

                    $ward       = $row[4];
                    $district   = $row[3];
                    $city       = $row[2];
                    $rowCheck = Ward::where('full_name', strtoupper($ward))
                        ->whereHas('district', function ($query) use ($district, $city) {
                            $query->where('full_name', strtoupper($district))
                                  ->whereHas('city', function ($query) use ($city) {
                                        $query->where('name', strtoupper($city));
                                    });
                        })->update(array('is_inter_district' => 1));
                    if($rowCheck == 1){
                        $totalLines ++;
                    }
                }

                // Only process one sheet
                break;
            }


            $reader->close();
            unlink($filePath);
            return response()->json([
                'status'  => 'True',
                'message' => $totalLines." rows susessfully!",
            ], 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $ex->getMessage()], 400);
        }
    }
}
