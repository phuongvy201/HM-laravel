<?php

namespace App\Http\Controllers;

use App\Jobs\SendEmailJob;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function sendCustomEmail(Request $request)
    {
        $request->validate(['subject' => 'required|string|max:255', 'content' => 'required|string',]);
        try {
            $users = User::where('role', 'customer')->get();
            $subject = $request->input('subject');
            $content = $request->input('content');
            foreach ($users as $user) {
                SendEmailJob::dispatch($user, $subject, $content);
            }
            return response()->json(['success' => true, 'message' => 'Emails sent successfully!',]);
        } catch (Exception $e) {
            Log::error('Error dispatching email jobs: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error sending emails', 'error' => $e->getMessage(),], 500);
        }
    }
}
