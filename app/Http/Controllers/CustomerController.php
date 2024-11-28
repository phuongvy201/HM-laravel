<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function getCustomers()
    {
        try {
            $customers = User::customers()->get();

            return response()->json([
                'success' => true,
                'data' => $customers,
                'message' => 'Lấy danh sách khách hàng thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách khách hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:6',
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string',
                'gender' => 'required|in:1,2',
                'role' => 'required|in:employee',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Xử lý upload avatar nếu có
            $avatarPath = null;
            if ($request->hasFile('image')) {
                $mainImage = $request->file('image');
                $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
                $mainImage->move(public_path('images/users'), $mainImageName);
                $avatarPath = 'images/users/' . $mainImageName;
            }


            // Tạo seller mới
            $seller = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'employee',
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'gender' => $request->gender,
                'avatar' => $avatarPath,
                'status' => $request->status
            ]);

            return response()->json([
                'success' => true,
                'data' => $seller,
                'message' => 'Thêm người bán hàng thành công'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm người bán hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateStatus(string $id)
    {
        DB::beginTransaction();
        try {
            $customer = User::findOrFail($id);
            // Toggle status giữa 0 và 1
            $customer->status = $customer->status == 1 ? 0 : 1;
            $customer->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái khách hàng thành công',
                'data' => $customer
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating customer status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái khách hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $customer = User::findOrFail($id);
            $customer->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Xóa khách hàng thành công'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa khách hàng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa khách hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function show(string $id)
    {
        try {
            // Tìm khách hàng theo ID
            $customer = User::findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $customer,
                'message' => 'Lấy thông tin khách hàng thành công'
            ]);
        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin khách hàng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy khách hàng',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getCurrentCustomer()
    {
        try {
            $user = Auth::user();

            if (!$user || $user->role !== 'customer') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access or not a customer',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => $user,
                'message' => 'Customer information retrieved successfully'
            ]);
        } catch (Exception $e) {
            Log::error('Error getting current customer: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving customer information',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCurrentUser(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = User::find(Auth::id());

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Người dùng chưa đăng nhập'
                ], 401);
            }

            // Validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'email' => 'string|email|max:255|unique:users,email,' . $user->id,
                'phone_number' => 'string|max:20',
                'address' => 'nullable|string',
                'gender' => 'in:1,2',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dữ liệu không hợp lệ',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Xử lý upload avatar mới nếu có
            if ($request->hasFile('avatar')) {
                // Xóa avatar cũ nếu không phải avatar mặc định
                if ($user->avatar && $user->avatar != 'images/default-avatar.png') {
                    if (file_exists(public_path($user->avatar))) {
                        unlink(public_path($user->avatar));
                    }
                }

                $avatar = $request->file('avatar');
                $avatarName = time() . '_' . $avatar->getClientOriginalName();
                $avatar->move(public_path('images/users'), $avatarName);
                $user->avatar = 'images/users/' . $avatarName;
            }

            // Cập nhật thông tin cơ bản
            $user->name = $request->name ?? $user->name;
            $user->email = $request->email ?? $user->email;
            $user->phone_number = $request->phone_number ?? $user->phone_number;
            $user->address = $request->address ?? $user->address;
            $user->gender = $request->gender ?? $user->gender;

            $user->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin thành công',
                'data' => $user
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật thông tin người dùng: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
