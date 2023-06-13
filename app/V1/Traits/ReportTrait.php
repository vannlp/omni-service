<?php


namespace App\V1\Traits;


use Maatwebsite\Excel\Excel;

trait ReportTrait
{

    protected function writeExcelOrderGrand($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/list_order_grand', $data);
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }

    protected function writeExcelPriceDetail($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        header('Access-Control-Allow-Origin: *');
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Export', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/list_price_detail', $data);
            });
        })->export('xlsx');
//
//        $fileExported = $fileName . ".xlsx";
//
//        readfile("$dir/$fileExported");
    }

    protected function writeExcelOrder($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/list_order', $data);
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }

    protected function writeExcelPartnerTurnover($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/list_order_partner_turnover', $data);
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }

    protected function writeExcelBusinessResult($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/my_business_result', $data);
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }

    protected function writeExcelIssueUserReport($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('report_issue_user', $data);
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }

    protected function writeExcelUserReferenceByUser($fileName, $dir, $data)
    {
        if (!file_exists($dir)) {
            mkdir($dir, 0777, true);
        }
        \Excel::create($fileName, function ($writer) use ($data) {

            $writer->sheet('Report', function ($sheet) use ($data) {
                $sheet->loadView('exports/report/list_user_reference_by_user', $data);
            });
        })->store('xlsx', $dir);

        $fileExported = $fileName . ".xlsx";
        header('Access-Control-Allow-Origin: *');
        readfile("$dir/$fileExported");
    }
}