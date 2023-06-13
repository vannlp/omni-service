<?php
/**
 * User: dai.ho
 * Date: 9/24/2019
 * Time: 10:11 AM
 */

namespace App\Jobs;


use App\Supports\TM_Email;

class SendMailConfirmOrderJob extends Job
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
        $subject = "KPIS";
        try {
            TM_Email::send(TM_Email::$view_mail_send_confirm_order, $to, $data, null, null, $subject);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}