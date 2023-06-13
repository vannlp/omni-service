<?php

/**
 * User: Ho Sy Dai
 * Date: 10/19/2018
 * Time: 11:19 AM
 */

namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

/**
 * Class AreaExport
 * @package App\Exports
 */
class SpecificationExport implements FromView
{

    protected $data;

    /**
     * AreaExport constructor.
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
        return view("exports.report.list_specification", ['data' => $this->data]);
    }
}
