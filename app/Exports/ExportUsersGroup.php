<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportUsersGroup implements FromView
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
        return view("exports.report.list_export_users_group", [
            'data' => $this->_data,
        ]);
    }
}