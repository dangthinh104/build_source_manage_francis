<?php

namespace App\Services;
use App\Mail\MyAppAlertMail;
use Illuminate\Support\Facades\Mail;

class SendEmailService {
    public static function sendEmail(string $title, string $details, array|string $mailTo)
    : string {
        $details = [
            'title' => $title,
            'body' => $details
        ];

        if (is_array($mailTo)) {
            foreach ($mailTo as $email) {
                Mail::to($email)->send(new MyAppAlertMail($details));
            }
        } else {
            Mail::to($mailTo)->send(new MyAppAlertMail($details));
        }

        return 'Email Sent!';
    }
}
