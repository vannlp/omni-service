<?php

namespace App\Jobs\KOffice;

use App\Jobs\Job;
use App\Supports\OFFICE_Email;

class SendMailCreateIssueJob extends Job
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
        OFFICE_Email::send(OFFICE_Email::$view_mail_create_issue, $to, $data, null, null, 'K-OFFICE');
    }
}