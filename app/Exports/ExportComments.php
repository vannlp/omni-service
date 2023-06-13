<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportComments implements FromView
{
    protected $_data;
    protected $_from;
    protected $_to;

    /**
     * ExportOrderExport constructor.
     */
    public function __construct($data, $from, $to)
    {
        $this->_data = $data;
        $this->_from = empty($from) ? null : $from;
        $this->_to   = empty($to) ? null : $to;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_export_comments", [
            'data' => $this->_data,
            'from' => $this->_from,
            'to'   => $this->_to
        ]);
    }
}