<?php

namespace App\Http\Controllers;

use App\Mail\MyCustomMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailController extends Controller
{
    public function sendMail(Request $request)
    {
        $details = ['title' => 'Mail từ MyApp', 'body' => 'Đây là một email thử nghiệm.'];
        Mail::to('recipient@example.com')->send(new MyCustomMail($details));
        return response()->json(['message' => 'Email đã được gửi thành công']);
    }
}
