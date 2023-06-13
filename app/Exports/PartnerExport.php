<?php

namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * Class CategoryExport
 * @package App\Exports
 */
class PartnerExport implements FromView
{

    protected $data;

    /**
     * CategoryExport constructor.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * @return View
     */
    public function view(): View
    {
        return view("exports.report.list_partner", ['data'=>$this->data]);
    }

}