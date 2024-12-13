<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TrackingMail extends Mailable
{
    use Queueable, SerializesModels;
    public $trackingNumber;
    public function __construct($trackingNumber)
    {
        $this->trackingNumber = $trackingNumber;
    }
    public function build()
    {
        return $this->subject('Tracking Number Update')->view('emails.trackingMail');
    }
}
