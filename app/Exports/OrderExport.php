<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class OrderExport implements FromView
{
    protected $_data;
    protected $_from;
    protected $_to;
    protected $_total;


    /**
     * ExportOrderExport constructor.
     */
    public function __construct($data, $from, $to, $total)
    {
        $this->_data = $data;
        $this->_from = empty($from) ? null : $from;
        $this->_to   = empty($to) ? null : $to;
        $this->_total = $total;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.order_export", [
            'data' => $this->_data,
            'from' => $this->_from,
            'to'   => $this->_to,
            'total'=>$this->_total
        ]);
    }
}