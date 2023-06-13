<?php
/**
 * User: kpistech2
 * Date: 2019-11-09
 * Time: 18:02
 */

namespace App\Jobs;


use App\Supports\TM_Email;

class SendMailChangeAccountStatusJob extends Job
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
            TM_Email::send(TM_Email::$view_mail_send_change_account_status, $data['to'], $data, null, null,
                'OMNI');
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}
