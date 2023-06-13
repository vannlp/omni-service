<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ReportInventoryExport implements FromView
{
    protected $_data;

    public function __construct($data)
    {
        $this->_data = $data;

    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_inventory", [
            'data'        => $this->_data
        ]);
    }
}