<?php

namespace App\Jobs;


use App\Supports\TM_Email;

class SendHUBMailNewOrderJob extends Job
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
        $subject = $data['order_id']. "-Đơn hàng mới từ Website TMDT - NUTIFOODSHOP.COM";
//        if (!empty($data['company_name'])) {
//            config(['mail.from.name' => $data['company_name']]);
//        }
        try {
            TM_Email::send("mail_send_hub_new_order", $to, $data, null, null, $subject);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}