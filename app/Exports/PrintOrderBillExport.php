<?php


namespace App\Exports;


use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PrintOrderBillExport implements FromView
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
        $customers_pay = $this->_data['customers_pay'];
        $orders        = $this->_data['orders'];
        return view("exports.report.print_order_bill", [
            'orders'        => $orders,
            'customers_pay' => $customers_pay
        ]);
    }
}