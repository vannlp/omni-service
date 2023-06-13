<?php

namespace App\Jobs;

use App\Supports\TM_Email;

class SendMailErrorJob extends Job
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
            TM_Email::send(TM_Email::$view_report_error, TM_Email::$mail_supporter, $data, [], null, '[Error]TM');
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}
