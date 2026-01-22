<?php

namespace App\Services;

use App\Models\SmtpConfiguration;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class MailService
{
    /**
     * Set the mailer configuration dynamically based on an SMTP configuration model.
     * 
     * @param SmtpConfiguration $smtp
     * @return void
     */
    public function setDynamicConfig(SmtpConfiguration $smtp)
    {
        $config = [
            'transport' => 'smtp',
            'host' => $smtp->host,
            'port' => $smtp->port,
            'encryption' => $smtp->encryption,
            'username' => $smtp->username,
            'password' => $smtp->password,
            'timeout' => null,
            'auth_mode' => null,
        ];

        Config::set('mail.mailers.smtp', array_merge(Config::get('mail.mailers.smtp', []), $config));
        Config::set('mail.from.address', $smtp->from_email);
        Config::set('mail.from.name', $smtp->from_name);
        Config::set('mail.default', 'smtp');
        
        // Reset the mailer to apply new config
        Mail::purge('smtp');
        Mail::purge(config('mail.default'));
    }

    /**
     * Send a test email.
     * 
     * @param string $to
     * @param SmtpConfiguration $smtp
     * @return void
     */
    public function sendTestEmail($to, SmtpConfiguration $smtp)
    {
        $this->setDynamicConfig($smtp);

        Mail::raw("Hello,\n\nThis is a test email from CodeAge ERP system. If you received this, your SMTP configuration '{$smtp->name}' is working correctly.\n\nRegards,\nCodeAge ERP", function ($message) use ($to, $smtp) {
            $message->to($to)
                    ->subject('SMTP Test Email - ' . config('app.name'));
        });
    }
}
