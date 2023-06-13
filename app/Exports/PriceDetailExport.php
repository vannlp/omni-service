<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PriceDetailExport implements FromView
{
    protected $_data;

    /**
     * OrderDetailExport constructor.
     */
    public function __construct($data)
    {
        $this->_data = $data;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_price_detail", [
            'data' => $this->_data
        ]);
    }
}