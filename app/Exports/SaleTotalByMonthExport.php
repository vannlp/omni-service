<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SaleTotalByMonthExport implements FromView
{
    protected $_data;
    protected $_from;
    protected $_to;

    /**
     * SaleTotalByMonthExport constructor.
     */
    public function __construct($data, $from, $to)
    {
        $this->_data = $data;
        $this->_from = $from;
        $this->_to   = $to;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_sale_total_by_month", [
            'data' => $this->_data,
            'from' => $this->_from,
            'to'   => $this->_to
        ]);
    }
}