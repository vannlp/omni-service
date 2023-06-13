<?php
/**
 * User: dai.ho
 * Date: 22/06/2020
 * Time: 11:45 AM
 */

namespace App\Jobs;


use App\Supports\TM_Email;

class SendStoreMailUpdateOrderStatusJob extends Job
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
        $subject = "Đơn hàng được cập nhật tình trạng - " . ($data['order']->store->name ?? null);
        if (!empty($data['company_name'])) {
            config(['mail.from.name' => $data['company_name']]);
        }
        try {
            TM_Email::send("mail_send_store_update_order_status", $to, $data, null, null, $subject);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}