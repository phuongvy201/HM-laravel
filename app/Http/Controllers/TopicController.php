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
                'message' => 'Lấy danh sách topic thành công',
                'data' => $topics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi lấy danh sách topic',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
