<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SaleByProductExport implements FromView
{
    protected $_data;
    protected $_from;
    protected $_to;

    /**
     * SaleByProductExport constructor.
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
        return view("exports.report.list_sale_by_product", [
            'data' => $this->_data,
            'from' => $this->_from,
            'to'   => $this->_to
        ]);
    }
}