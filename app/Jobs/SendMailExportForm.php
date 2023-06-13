<?php
/**
 * User: kpistech2
 * Date: 2020-06-20
 * Time: 10:28
 */

namespace App\Jobs;


use App\Supports\TM_Email;

class SendMailExportForm extends Job
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
        $subject = "[EXPORT ENQUIRY] - Email: " .$data['email']. " from Nutifoodshop.com";
        try {
            TM_Email::send(TM_Email::$mail_send_to_export_form, $to, $data, null, null, $subject);
        } catch (\Exception $ex) {
            // return $ex->getMessage()  . $ex->getLine();
            // echo $ex->getMessage();
            // die;
        }
    }
}