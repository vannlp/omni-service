<?php
/**
 * User: Administrator
 * Date: 16/10/2018
 * Time: 09:48 PM
 */

namespace App\Supports;

use Illuminate\Support\Facades\Mail;

class OFFICE_Email
{
    public static $mail_supporter = "h.sydai@gmail.com";
    static $view_approval_status = "k-office.mail_send_approval_status";
    static $view_assign_kpi = "k-office.mail_send_assign_kpi";
    static $view_reset_password = "k-office.mail_send_reset_password";
    static $view_new_user = "k-office.mail_send_new_user";
    static $view_register_confirm = "k-office.mail_send_register";
    static $view_report_error = "k-office.mail_send_report_error";
    static $view_contact = "k-office.mail_send_contact";
    static $view_mail_promp_issue = "k-office.mail_send_promp_issue";
    static $view_mail_create_issue = "k-office.mail_send_create_issue";

    /**
     * @param $view
     * @param $to
     * @param array $data
     * @param array $cc
     * @param array $bcc
     * @param string $subject
     */
    static function send($view, $to, $data = [], $cc = [], $bcc = [], $subject = "K-OFFICE!")
    {
        $data['logo'] = env('APP_LOGO');
        Mail::send($view, $data, function ($message) use ($to, $subject, $data, $cc, $bcc) {
            $message->to($to);
            if (!empty($cc)) {
                $message->cc($cc);
            }
//            $bcc[] = self::$mail_supporter;
//            $bcc[] = "kpis.vn@gmail.com";
//            $message->bcc($bcc);
            $message->subject($subject);
        });
    }
}