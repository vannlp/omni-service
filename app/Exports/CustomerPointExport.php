<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CustomerPointExport implements FromView
{
    protected $data;
    protected $userCode;
    protected $groupCode;

    /**
     * CustomerPointExport constructor.
     */
    public function __construct($data, $userCode, $groupCode)
    {
        $this->data      = $data;
        $this->userCode  = $userCode;
        $this->groupCode = $groupCode;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_customer_point", [
            'data'      => $this->data,
            'userCode'  => $this->userCode,
            'groupCode' => $this->groupCode
        ]);
    }
}