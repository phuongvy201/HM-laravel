<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user:id,name,avatar', 'topic:id,name,slug'])
            ->where('status', 1)  // Điều kiện status = 1
            ->where('type', 'post')  // Điều kiện type = 'post'
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }


    public function getLatestPosts()
    {
        $posts = Post::with(['user:id,name,avatar', 'topic:id,name,slug'])
            ->where([
                ['status', 1],
                ['type', 'post']
            ])

            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $posts
        ]);
    }

    public function getPostsByTopic($slug)
    {
        try {
            $posts = Post::with(['user:id,name', 'topic:id,name,slug'])
                ->whereHas('topic', function ($query) use ($slug) {
                    $query->where('slug', $slug);
                })
                ->where([
                    ['status', 1],
                    ['type', 'post']
                ])
                ->orderBy('created_at', 'desc')
                ->paginate(12);

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách bài viết theo topic thành công',
                'data' => [
                    'items' => $posts->items(),
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'last_page' => $posts->lastPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách bài viết',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRelatedPosts($slug)
    {
        try {
            // Lấy topic_id của bài viết hiện tại
            $currentPost = Post::findOrFail($slug);

            // Lấy các bài viết cùng topic, ngoại trừ bài viết hiện tại
            $relatedPosts = Post::with(['user:id,name', 'topic:id,name,slug'])
                ->where('topic_id', $currentPost->topic_id)
                ->where('slug', '!=', $slug)
                ->where([
                    ['status', 1],
                    ['type', 'post']
                ])
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách bài viết liên quan thành công',
                'data' => $relatedPosts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách bài viết liên quan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPostBySlug($slug)
    {
        try {
            $post = Post::with([
                'user:id,name',
                'topic:id,name,slug',

            ])
                ->where([
                    ['slug', $slug],
                    ['status', 1],
                    ['type', 'post']
                ])
                ->firstOrFail();



            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết bài viết thành công',
                'data' => $post
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy bài viết hoặc có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getPages()
    {
        try {
            $pages = Post::with(['user:id,name'])
                ->where([
                    ['status', 1],
                    ['type', 'page']
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách trang tĩnh thành công',
                'data' => $pages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách trang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getPageBySlug($slug)
    {
        try {
            $page = Post::with(['user:id,name'])
                ->where([
                    ['slug', $slug],
                    ['status', 1],
                    ['type', 'page']
                ])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Lấy chi tiết trang thành công',
                'data' => $page
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy trang hoặc có lỗi xảy ra',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function getOtherPages($currentSlug)
    {
        try {
            $otherPages = Post::with(['user:id,name'])
                ->where([
                    ['status', 1],
                    ['type', 'page'],
                    ['slug', '!=', $currentSlug]
                ])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Lấy danh sách trang khác thành công',
                'data' => $otherPages
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách trang',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function createStaticPage(Request $request)
    {
        try {
            // Validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'detail' => 'required|string',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'status' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Tạo slug tự động
            $slug = Str::slug($request->title, '-');

            // Đảm bảo slug là duy nhất
            $originalSlug = $slug;
            $counter = 1;
            while (Post::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            // Xử lý file upload (nếu có)
            $imagePath = null;
            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('images/pages', 'public');
            }

            // Tạo static page mới
            $page = Post::create([
                'title' => $request->title,
                'slug' => $slug,
                'detail' => $request->detail,
                'description' => $request->description,
                'image' => $imagePath, // Lưu đường dẫn ảnh
                'status' => $request->status,
                'type' => 'page',
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo trang tĩnh thành công',
                'data' => $page,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo trang tĩnh',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updateStatus(string $id)
    {
        try {
            $page = Post::findOrFail($id);

            // Đổi status từ 1 -> 2 hoặc 2 -> 1
            $page->status = $page->status == 1 ? 2 : 1;
            $page->updated_by = Auth::id();
            $page->save();

            return response()->json([
                'success' => true,
                'message' => 'Status update successful',
                'data' => $page
            ]);
        } catch (Exception $e) {
            Log::error('Error updating single page status: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the status',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $post = Post::findOrFail($id);
            $post->delete();

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Deleted successfully'
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error while deleting: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting a promotion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function updateStaticPage(Request $request, $id)
    {
        try {
            // Tìm trang cần cập nhật
            $page = Post::find($id);
            if (!$page) {
                return response()->json([
                    'success' => false,
                    'message' => 'Page not found',
                ], 404);
            }

            // Validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'detail' => 'required|string',
                'description' => 'nullable|string',
                'status' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Cập nhật slug nếu tiêu đề thay đổi
            if ($page->title !== $request->title) {
                $slug = Str::slug($request->title, '-');
                $originalSlug = $slug;
                $counter = 1;
                while (Post::where('slug', $slug)->where('id', '!=', $id)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
                $page->slug = $slug;
            }

            // Cập nhật các thuộc tính khác
            $page->title = $request->title;
            $page->detail = $request->detail;
            $page->description = $request->description;
            $page->status = $request->status;
            $page->updated_by = Auth::user()->id;

            // Lưu thay đổi
            $page->save();

            return response()->json([
                'success' => true,
                'message' => '
Static page updated successfully',
                'data' => $page,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the static page',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getPageById($id)
    {
        try {
            // Tìm trang tĩnh theo ID
            $page = Post::with(['user:id,name'])
                ->where([
                    ['id', $id], // Có thể bỏ điều kiện này nếu muốn lấy cả các trang không hoạt động
                ])
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'message' => 'Retrieve page details successfully',
                'data' => $page
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Page not found or an error occurred',
                'error' => $e->getMessage()
            ], 404);
        }
    }
    public function getPostsByAuth()
    {
        try {
            // Lấy người dùng hiện tại từ auth
            $user = Auth::user();  // Lấy người dùng đã đăng nhập

            // Kiểm tra xem người dùng có tồn tại không
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found'
                ], 404);
            }

            // Lấy các bài viết của người dùng đã đăng nhập (created_by là user_id)
            $posts = Post::with(['user:id,name', 'topic:id,name,slug'])
                ->where('created_by', $user->id)  // Lọc theo người dùng đã đăng nhập
                ->where([
                    ['status', 1],
                    ['type', 'post']
                ])
                ->orderBy('created_at', 'desc')
                ->paginate(12);  // Phân trang kết quả

            return response()->json([
                'success' => true,
                'message' => "Get the list of user's posts successfully",
                'data' => [
                    'items' => $posts->items(),
                    'pagination' => [
                        'current_page' => $posts->currentPage(),
                        'last_page' => $posts->lastPage(),
                        'per_page' => $posts->perPage(),
                        'total' => $posts->total()
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "An error occurred while retrieving the user's post list",
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function createPost(Request $request)
    {
        try {
            // Validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'detail' => 'required|string',
                'topic_id' => 'required|integer',
                'description' => 'nullable|string',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg',
                'status' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Tạo slug tự động
            $slug = Str::slug($request->title, '-');

            // Đảm bảo slug là duy nhất
            $originalSlug = $slug;
            $counter = 1;
            while (Post::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            $mainImagePath = null;
            if ($request->hasFile('image')) {
                $mainImage = $request->file('image');
                $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
                $mainImage->move(public_path('images/posts'), $mainImageName);
                $mainImagePath = 'images/posts/' . $mainImageName;
            }
            // Tạo static page mới
            $page = Post::create([
                'title' => $request->title,
                'slug' => $slug,
                'detail' => $request->detail,
                'topic_id' => $request->topic_id,
                'description' => $request->description,
                'image' => $mainImagePath, // Lưu đường dẫn ảnh
                'status' => $request->status,
                'type' => 'post',
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Created a successful post',
                'data' => $page,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function updatePost(Request $request, $id)
    {
        try {
            // Tìm bài viết theo ID
            $post = Post::find($id);

            // Kiểm tra xem bài viết có tồn tại không
            if (!$post) {
                return response()->json([
                    'success' => false,
                    'message' => 'Post not found',
                ], 404);
            }

            // Validate dữ liệu đầu vào
            $validator = Validator::make($request->all(), [
                'title' => 'required|string|max:255',
                'detail' => 'required|string',
                'topic_id' => 'required|integer',
                'description' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
                'status' => 'required|integer|in:0,1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation Error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            // Cập nhật slug nếu tiêu đề thay đổi
            $slug = Str::slug($request->title, '-');
            if ($slug !== $post->slug) {
                $originalSlug = $slug;
                $counter = 1;
                while (Post::where('slug', $slug)->exists()) {
                    $slug = $originalSlug . '-' . $counter++;
                }
            }

            // Xử lý ảnh nếu có file mới
            $mainImagePath = $post->image; // Giữ nguyên đường dẫn ảnh cũ
            if ($request->hasFile('image')) {
                // Xóa ảnh cũ nếu có
                if (file_exists(public_path($post->image))) {
                    unlink(public_path($post->image));
                }
                // Lưu ảnh mới
                $mainImage = $request->file('image');
                $mainImageName = time() . '_' . $mainImage->getClientOriginalName();
                $mainImage->move(public_path('images/posts'), $mainImageName);
                $mainImagePath = 'images/posts/' . $mainImageName;
            }

            // Cập nhật bài viết
            $post->title = $request->title;
            $post->slug = $slug;
            $post->detail = $request->detail;
            $post->topic_id = $request->topic_id;
            $post->description = $request->description;
            $post->image = $mainImagePath;
            $post->status = $request->status;
            $post->updated_by = Auth::user()->id;

            // Lưu bài viết đã cập nhật
            $post->save();

            return response()->json([
                'success' => true,
                'message' => 'Post updated successfully',
                'data' => $post,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
