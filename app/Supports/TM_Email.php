<?php
/**
 * User: Administrator
 * Date: 16/10/2018
 * Time: 09:48 PM
 */

namespace App\Supports;


use Illuminate\Support\Facades\Mail;

class TM_Email
{
//    public static $mail_supporter = "h.sydai@gmail.com";
    public static $mail_supporter = "thuongtamduy@live.com";

    public static $view_reset_password                    = "mail_send_reset_password";
    public static $view_new_user                          = "mail_send_new_user";
    public static $view_register_confirm                  = "mail_send_register";
    public static $view_report_error                      = "mail_send_report_error";
    public static $view_cancel_request                    = "mail_send_cancel_request";
    public static $view_register_partner                  = "mail_send_register_partner";
    public static $view_to_customer_register_partner      = "mail_send_to_customer_register_partner";
    public static $view_mail_send_confirm_order           = "mail_send_confirm_order";
    public static $view_mail_send_change_account_status   = "mail_send_change_account_status";
    public static $view_mail_send_order_approved_canceled = "mail_send_order_approved_canceled";
    public static $view_mail_send_customer_hub_new        = "mail_send_customer_hub_new";
    public static $view_mail_send_forget_password     = "mail_send_reset_password";
    public static $view_mail_send_to_contact     = "mail_send_to_contact";
    public static $mail_send_to_export_form     = "mail_send_to_export_form";

    public static $view_new_order_for_store       = "mail_send_new_order_to_store";
    public static $view_new_order_for_customer    = "mail_send_new_order_to_customer";
    public static $view_update_order_for_store    = "mail_send_update_order_to_store";
    public static $view_update_order_for_customer = "mail_send_update_order_to_customer";

    /**
     * @param $view
     * @param $to
     * @param array $data
     * @param array $cc
     * @param array $bcc
     * @param string $subject
     */
    static function send($view, $to, $data = [], $cc = [], $bcc = [], $subject = "OMNI")
    {
        $data['logo'] = env('APP_LOGO');
        Mail::send($view, $data, function ($message) use ($to, $subject, $data, $cc, $bcc) {
            $message->to($to);

            if (!empty($cc)) {
                $message->cc($cc);
            }

//            $bcc[] = self::$mail_supporter;
//            $bcc[] = "trantiendat041198@gmail.com";

//            $message->bcc($bcc);

            $message->subject($subject);
        });
    }
}