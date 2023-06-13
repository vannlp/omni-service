<?php
/**
 * User: kpistech2
 * Date: 2020-06-20
 * Time: 10:28
 */

namespace App\Jobs;


use App\Supports\TM_Email;

class SendCustomerMailNewOrderJob extends Job
{
    protected $data;
    protected $to;

    public function __construct($to, $data)
    {
        $this->data = $data;
        $this->to = $to;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        $to = $this->to;

        $subject = "Xác nhận đơn hàng - Mã đơn hàng: " . ($data['order']->code ?? null) ." - ". ($data['order']->store->name ?? null);
//        if (!empty($data['company_name'])) {
//            config(['mail.from.name' => $data['company_name']]);
//        }
        try {
            TM_Email::send("mail_send_customer_new_order", $to, $data, null, null, $subject);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}