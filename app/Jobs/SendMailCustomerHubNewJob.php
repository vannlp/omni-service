<?php

namespace App\Jobs;


use App\Supports\TM_Email;

class SendMailCustomerHubNewJob extends Job
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
        try {
            TM_Email::send(TM_Email::$view_mail_send_customer_hub_new, $to, $data, null, null, "IDP");
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}