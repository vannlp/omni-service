<?php
/** @var $config */
try {
    $config = \Illuminate\Support\Facades\DB::table('settings')
        ->whereNull('deleted_at')
        ->where('is_active', 1)
        ->where('company_id', \App\TM::getCurrentCompanyId())
        ->where('store_id', \App\TM::getCurrentStoreId())
        ->where('code', 'MAIL-CONFIG')->first();
    if (!empty($config) && !empty($config->data)) {
        $config = json_decode($config->data, true);;
        $configMail = array_pluck($config, 'value', 'key');
    }
} catch (\Exception $exception) {
}

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Driver
    |--------------------------------------------------------------------------
    |
    | Laravel supports both SMTP and PHP's "mail" function as drivers for the
    | sending of e-mail. You may specify which one you're using throughout
    | your application here. By default, Laravel is setup for SMTP mail.
    |
    | Supported: "smtp", "mail", "sendmail", "mailgun", "mandrill",
    |            "ses", "sparkpost", "log"
    |
    */
    'driver'     => !empty($configMail['MAIL_DRIVER']) ? $configMail['MAIL_DRIVER'] : env('MAIL_DRIVER', 'smtp'),
    /*
    |--------------------------------------------------------------------------
    | SMTP Host Address
    |--------------------------------------------------------------------------
    |
    | Here you may provide the host address of the SMTP server used by your
    | applications. A default option is provided that is compatible with
    | the Mailgun mail service which will provide reliable deliveries.
    |
    */
    'host'       => !empty($configMail['MAIL_HOST']) ? $configMail['MAIL_HOST'] : env('MAIL_HOST', 'smtp.gmail.com'),
    /*
    |--------------------------------------------------------------------------
    | SMTP Host Port
    |--------------------------------------------------------------------------
    |
    | This is the SMTP port used by your application to deliver e-mails to
    | users of the application. Like the host we have set this value to
    | stay compatible with the Mailgun e-mail application by default.
    |
    */
    'port'       => !empty($configMail['MAIL_PORT']) ? $configMail['MAIL_PORT'] : env('MAIL_PORT', 587),
    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all e-mails sent by your application to be sent from
    | the same address. Here, you may specify a name and address that is
    | used globally for all e-mails that are sent by your application.
    |
    */
    'from'       => [
        'address' => !empty($configMail['MAIL_FROM_EMAIL']) ? $configMail['MAIL_FROM_EMAIL'] : env('MAIL_FROM_EMAIL', 'noreply.noproblem@gmail.com'),
        'name'    => !empty($configMail['MAIL_FROM_NAME']) ? $configMail['MAIL_FROM_NAME'] : env('MAIL_FROM_NAME', 'NUTIFOODSHOP')
    ],
    /*
    |--------------------------------------------------------------------------
    | E-Mail Encryption Protocol
    |--------------------------------------------------------------------------
    |
    | Here you may specify the encryption protocol that should be used when
    | the application send e-mail messages. A sensible default using the
    | transport layer security protocol should provide great security.
    |
    */
    'encryption' => !empty($configMail['MAIL_ENCRYPTION']) ? $configMail['MAIL_ENCRYPTION'] : env('MAIL_ENCRYPTION', 'tls'),
    /*
    |--------------------------------------------------------------------------
    | SMTP Server Username
    |--------------------------------------------------------------------------
    |
    | If your SMTP server requires a username for authentication, you should
    | set it here. This will get used to authenticate with your server on
    | connection. You may also set the "password" value below this one.
    |
    */
    'username'   => !empty($configMail['MAIL_USERNAME']) ? $configMail['MAIL_USERNAME'] : env('MAIL_USERNAME', 'noreply.noproblem@gmail.com'),
    /*
    |--------------------------------------------------------------------------
    | SMTP Server Password
    |--------------------------------------------------------------------------
    |
    | Here you may set the password required by your SMTP server to send out
    | messages from your application. This will be given to the server on
    | connection so that the application will be able to send messages.
    |
    */
    'password'   => !empty($configMail['MAIL_PASSWORD']) ? $configMail['MAIL_PASSWORD'] : env('MAIL_PASSWORD', 'trdrhdtbbaspgfxp'),
    /*
    |--------------------------------------------------------------------------
    | Sendmail System Path
    |--------------------------------------------------------------------------
    |
    | When using the "sendmail" driver to send e-mails, we will need to know
    | the path to where Sendmail lives on this server. A default path has
    | been provided here, which will work well on most of your systems.
    |
    */
    'sendmail'   => '/usr/sbin/sendmail -bs',
];