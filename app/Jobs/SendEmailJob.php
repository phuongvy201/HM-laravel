<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $user;
    protected $subject;
    protected $content;
    public function __construct($user, $subject, $content)
    {
        $this->user = $user;
        $this->subject = $subject;
        $this->content = $content;
    }
    public function handle()
    {
        try {
            Mail::send([], [], function ($message) {
                $message->to($this->user->email)->subject($this->subject)->html($this->content);
            });
        } catch (Exception $e) {
            Log::error('Error sending email to ' . $this->user->email . ': ' . $e->getMessage());
            throw $e;
        }
    }
}
