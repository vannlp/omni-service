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
 * Class ProductExport
 * @package App\Exports
 */
class ProductExport implements FromView
{

    protected $data;

    /**
     * ProductExport constructor.
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
        return view("exports.report.list_products", ['data'=>$this->data]);
    }

}