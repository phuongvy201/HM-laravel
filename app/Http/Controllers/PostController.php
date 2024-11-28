<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['user:id,name,avatar', 'topic:id,name,slug'])
            ->where(['status', 1], ['type', 'post'])
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
            $request->validate([
                'title' => 'required|string|max:255',
                'slug' => 'required|string|unique:posts,slug',
                'detail' => 'required|string',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'status' => 'required|integer|in:0,1'
            ]);

            // Tạo static page mới
            $page = Post::create([
                'title' => $request->title,
                'slug' => $request->slug,
                'detail' => $request->detail,
                'description' => $request->description,
                'image' => $request->image,
                'status' => $request->status,
                'type' => 'page',
                'created_by' => Auth::user()->id,
                'updated_by' => Auth::user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tạo trang tĩnh thành công',
                'data' => $page
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi tạo trang tĩnh',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
