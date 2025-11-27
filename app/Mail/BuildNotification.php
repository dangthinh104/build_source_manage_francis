<?php
namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BuildNotification extends Mailable
{
    use Queueable, SerializesModels;

    protected $siteName;
    protected $status;
    protected $timestamp;
    protected $mailContent;
    protected $mailDev;
    public function __construct($siteName, $status, $timestamp, $mailContent, $mailDev)
    {
        $this->siteName = $siteName;
        $this->status = $status;
        $this->timestamp = $timestamp;
        $this->mailContent = $mailContent;
        $this->mailDev = $mailDev;    
}

         public function envelope()
    {
        return new Envelope(
            subject: "Build Notification for {$this->siteName}",
        );
    }

public function build()
    {
        return $this->to($this->mailDev)
                    ->markdown('emails.build-notification')
                    ->content($this->content());
    }
    public function content()
    {
        return new Content(
            markdown: 'emails.build-notification',
            with: [
                'siteName' => $this->siteName,
                'status' => $this->status,
                'timestamp' => $this->timestamp,
                'mailContent' => $this->mailContent
            ]
        );
    }
    public function attachments()
    {
        return [];
    }
}

