<?php
/**
 * User: dai.ho
 * Date: 11/7/2019
 * Time: 11:35 AM
 */

namespace App\Jobs;


use App\Supports\TM_Email;

class SendMailCancelRequestJob extends Job
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $data = $this->data;
        try {
            TM_Email::send(TM_Email::$view_cancel_request, TM_Email::$mail_supporter, $data, null, null, 'TM-Shine+');
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}
