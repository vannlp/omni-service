<?php


namespace App\Jobs;


use App\Supports\TM_Email;

class SendMailResetPassword extends Job
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
            config(['mail.from.name' => "Reset Password"]);
            $subject = "Thay đổi mật khẩu thành công!";
            TM_Email::send(TM_Email::$view_mail_send_forget_password, $data['to'], $data, null, null,
                $subject);
        } catch (\Exception $ex) {
            echo $ex->getMessage();
            die;
        }
    }
}