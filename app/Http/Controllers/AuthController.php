<?php

namespace App\Http\Controllers;

use App\Mail\VerificationCodeMail;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
                'role' => 'required|in:admin,user,employee,customer',
                'avatar' => 'nullable|string',
                'phone_number' => 'nullable|string',
                'address' => 'nullable|string',
                'status' => 'nullable|boolean',
                'gender' => 'nullable|string',
            ]);

            $verificationCode = rand(100000, 999999);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
                'avatar' => $validatedData['avatar'] ?? 'images/users/cat.png',
                'phone_number' => $validatedData['phone_number'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'status' => $validatedData['status'] ?? true,
                'gender' => $validatedData['gender'] ?? null,
                'verification_code' => $verificationCode,
            ]);

            // Generate token after user creation
            $abilities = $user->role === 'admin' ? ['admin'] : ['user'];
            $token = $user->createToken('auth_token', $abilities)->plainTextToken;

            // Send verification email
            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful. Please check your email for verification code.',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ]);
        } catch (ValidationException $e) {
            // Log lỗi validation
            Log::error('Validation error during registration: ', $e->errors());
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            // Log lỗi chung
            Log::error('Error during registration: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'errors' => $e->getMessage(),
            ], 500);
        }
    }

    public function verifyEmail(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|string|email|max:255',
            'verification_code' => 'required|numeric',
        ]);
        $user = User::where(
            'email',
            $validatedData['email']
        )->where('verification_code', $validatedData['verification_code'])->first();
        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid verification code or email.',
            ], 422);
        }
        $user->email_verified_at = now();
        $user->save();
        return response()->json([
            'status' => 'success',
            'message' => 'Email verified successfully.',
        ], 200);
    }
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($validatedData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Thông tin đăng nhập không chính xác'
            ], 401);
        }

        $user = User::where('email', $validatedData['email'])->firstOrFail();

        // Tạo token mới với quyền dựa trên vai trò
        $abilities = $user->role === 'admin' ? ['admin'] : ['user'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng nhập thành công',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout successful'
        ]);
    }

    public function getUser(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => $user,
            'permissions' => $user->currentAccessToken()->abilities
        ]);
    }

    public function changePassword(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|min:8|different:current_password',
                'confirm_password' => 'required|same:new_password'
            ]);

            $user = $request->user();

            if (!Hash::check($validatedData['current_password'], $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Current password is incorrect'
                ], 422);
            }

            $user->update([
                'password' => Hash::make($validatedData['new_password'])
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password changed successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function checkSession(Request $request)
    {
        if (Auth::check()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Session is active',
                'user' => Auth::user()
            ], 200);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'No active session'
            ], 401);
        }
    }
}
