<?php

namespace App\Http\Controllers;

use App\Models\Topic;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TopicController extends Controller
{
    /**
     * Lấy tất cả các topic
     */
    public function getAllTopics(): JsonResponse
    {
        try {
            $topics = Topic::where('status', 1)

                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Get the list of topics successfully',
                'data' => $topics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving the topic list',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Thêm một topic mới
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function addTopic(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|boolean',
        ]);

        try {
            $topic = Topic::addTopic($request->all()); // Gọi hàm thêm topic từ model

            return response()->json([
                'success' => true,
                'message' => 'Topic đã được thêm thành công',
                'data' => $topic
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi thêm topic',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Xóa một topic
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteTopic(int $id): JsonResponse
    {
        try {
            $topic = Topic::find($id);

            if (!$topic) {
                return response()->json([
                    'success' => false,
                    'message' => 'Topic không tồn tại'
                ], 404);
            }

            $topic->delete();

            return response()->json([
                'success' => true,
                'message' => 'Topic đã được xóa thành công'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi xóa topic',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
