<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
                'gender' => 'nullable|string'
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => $validatedData['role'],
                'avatar' => $validatedData['avatar'] ?? 'images/users/—Pngtree—cartoon hand drawn default avatar_7127563.png',
                'phone_number' => $validatedData['phone_number'] ?? null,
                'address' => $validatedData['address'] ?? null,
                'status' => $validatedData['status'] ?? true,
                'gender' => $validatedData['gender'] ?? null
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Registration successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user
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

    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($validatedData)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Incorrect login information'
            ], 401);
        }

        $user = User::where('email', $validatedData['email'])->firstOrFail();

        // Delete old token if exists
        $user->tokens()->delete();

        // Create new token with abilities based on role
        $abilities = $user->role === 'admin' ? ['admin'] : ['user'];
        $token = $user->createToken('auth_token', $abilities)->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
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
}
