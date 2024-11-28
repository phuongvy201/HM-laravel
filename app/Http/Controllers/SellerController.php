<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Exception;

class SellerController extends Controller
{
    public function getEmployees()
    {
        try {
            $employees = User::employees()->get();

            return response()->json([
                'success' => true,
                'data' => $employees,
                'message' => 'Lấy danh sách nhân viên thành công'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách nhân viên',
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
            $employee = User::findOrFail($id);
            // Toggle status giữa 0 và 1
            $employee->status = $employee->status == 1 ? 0 : 1;
            $employee->save();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật trạng thái nhân viên thành công',
                'data' => $employee
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating employee status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật trạng thái nhân viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $employee = User::findOrFail($id);
            $employee->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Xóa nhân viên thành công'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi xóa nhân viên: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa nhân viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        DB::beginTransaction();
        try {
            // Tìm nhân viên cần cập nhật
            $employee = User::findOrFail($id);

            // Validate dữ liệu
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users,email,' . $id,
                'phone_number' => 'required|string|max:20',
                'address' => 'required|string',
                'gender' => 'nullable|in:1,2',
                'avatar' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
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
                // Xóa avatar cũ nếu có
                if ($employee->avatar && file_exists(public_path($employee->avatar))) {
                    unlink(public_path($employee->avatar));
                }

                $avatar = $request->file('avatar');
                $avatarName = time() . '_' . $avatar->getClientOriginalName();
                $avatar->move(public_path('images/users'), $avatarName);
                $avatarPath = 'images/users/' . $avatarName;
            }

            // Cập nhật thông tin nhân viên
            $employee->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'status' => $request->status,
                'address' => $request->address,
                'gender' => $request->gender,
                'avatar' => $request->hasFile('avatar') ? $avatarPath : $employee->avatar
            ]);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật thông tin nhân viên thành công',
                'data' => $employee
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi cập nhật thông tin nhân viên: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật thông tin nhân viên',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function show(string $id)
    {
        try {
            // Tìm nhân viên theo ID
            $employee = User::findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $employee,
                'message' => 'Lấy thông tin nhân viên thành công'
            ]);

        } catch (Exception $e) {
            Log::error('Lỗi khi lấy thông tin nhân viên: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy nhân viên',
                'error' => $e->getMessage()
            ], 404);
        }
    }
}
