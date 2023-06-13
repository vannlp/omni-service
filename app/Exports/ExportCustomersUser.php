<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportCustomersUser implements FromView
{
    protected $_data;

    /**
     * ExportOrderExport constructor.
     */
    public function __construct($data)
    {
        $this->_data = $data;
//        $this->_from = empty($from) ? null : $from;
//        $this->_to   = empty($to) ? null : $to;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_export_customers_user", [
            'data' => $this->_data,
            //            'from' => $this->_from,
            //            'to'   => $this->_to
        ]);
    }
}