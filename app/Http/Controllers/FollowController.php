<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FollowController extends Controller
{
    public function follow(Request $request)
    {
        $request->validate(['followed_shop_id' => 'required|exists:profile_shop,id',]);
        $follow = Follow::create([
            'follower_id' => Auth::id(),
            'followed_shop_id' => $request->input('followed_shop_id'),
            'follow_date' => now(),
        ]);
        return response()->json([
            'status' => 'success',
            'message' => 'Successfully followed the shop.',
            'data' => $follow
        ], 201);
    }
    public function unfollow(Request $request)
    {
        $request->validate([
            'followed_shop_id' => 'required|exists:profile_shop,id',
        ]);
        $follow = Follow::where(
            'follower_id',
            Auth::id()
        )->where('followed_shop_id', $request->input('followed_shop_id'))->first();
        if ($follow) {
            $follow->delete();
            return response()->json([
                'status' => 'success',
                'message' => 'Successfully unfollowed the shop.',
            ], 200);
        }
        return response()->json(['status' => 'error', 'message' => 'You are not following this shop.',], 404);
    }
    public function checkFollow(Request $request)
    {
        $request->validate(['followed_shop_id' => 'required|exists:profile_shop,id',]);
        $isFollowing = Follow::where(
            'follower_id',
            Auth::id()
        )->where('followed_shop_id', $request->input('followed_shop_id'))->exists();
        return response()->json(['status' => 'success', 'is_following' => $isFollowing,], 200);
    }
}
