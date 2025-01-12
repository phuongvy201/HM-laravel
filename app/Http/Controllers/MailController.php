<?php

namespace App\Http\Controllers;

use App\Mail\CustomerInquiryMail;
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
    public function submitForm(Request $request)
    {
        $request->validate([
            'quantity' => 'required',
            'products' => 'required',
            'name' => 'required',
            'email' => 'required|email',
            'file' => 'required|file|max:5120',
        ]);

        $filePath = $request->file('file')->store('uploads', 'public');

        $data = [
            'quantity' => $request->input('quantity'),
            'products' => $request->input('products'),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'company' => $request->input('company'),
            'phone' => $request->input('phone'),
            'file' => $filePath,
        ];

        Mail::to('admin@bluprinter.com')->send(new CustomerInquiryMail($data));

        return back()->with('success', 'Your inquiry has been sent successfully!');
    }
}
