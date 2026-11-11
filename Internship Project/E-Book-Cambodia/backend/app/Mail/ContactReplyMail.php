<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $userName,
        public string $subjectText,
        public string $originalMessage,
        public string $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Re: '.$this->subjectText,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-reply',
            with: [
                'userName' => $this->userName,
                'subject' => $this->subject,
                'originalMessage' => $this->originalMessage,
                'reply' => $this->reply,
            ],
        );
    }
}