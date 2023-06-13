<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportWareHouseDetails implements FromView
{
    protected $_data;

    /**
     * ExportOrderExport constructor.
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
        return view("exports.report.list_warehousedetails", [
            'data' => $this->_data,
        ]);
    }
}