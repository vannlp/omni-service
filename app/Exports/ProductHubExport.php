<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProductHubExport implements FromView
{
     protected $_data;

     /**
      * SaleByProductExport constructor.
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
          return view("exports.report.list_productHub", [
               'data' => $this->_data
          ]);
     }
}
