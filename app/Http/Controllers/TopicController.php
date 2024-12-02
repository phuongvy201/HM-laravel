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
}
