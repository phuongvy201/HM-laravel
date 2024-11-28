<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Post;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $topic;
    protected $post;

    protected function setUp(): void
    {
        parent::setUp();

        // Tạo dữ liệu test
        $this->user = User::factory()->create([
            'name' => 'Test User',
            'avatar' => 'test-avatar.jpg'
        ]);

        $this->topic = Topic::factory()->create([
            'name' => 'Test Topic',
            'slug' => 'test-topic',
            'status' => 1
        ]);

        $this->post = Post::factory()->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'title' => 'Test Post',
            'status' => 1,
            'type' => 'post'
        ]);
    }

    public function test_get_latest_posts()
    {
        // Tạo thêm vài bài post
        Post::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'status' => 1,
            'type' => 'post'
        ]);

        $response = $this->getJson('/api/posts/latest');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'user' => ['id', 'name', 'avatar'],
                        'topic' => ['id', 'name', 'slug']
                    ]
                ]
            ])
            ->assertJsonCount(6, 'data');
    }

    public function test_get_posts_by_topic()
    {
        // Tạo thêm posts cho topic
        Post::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'status' => 1,
            'type' => 'post'
        ]);

        $response = $this->getJson("/api/posts/topic/{$this->topic->slug}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'user' => ['id', 'name', 'avatar'],
                            'topic' => ['id', 'name', 'slug']
                        ]
                    ],
                    'total'
                ]
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Lấy danh sách bài viết theo topic thành công'
            ]);
    }

    public function test_get_posts_by_invalid_topic()
    {
        $response = $this->getJson('/api/posts/topic/invalid-slug');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['data' => []]
            ]);
    }

    public function test_only_active_posts_are_returned()
    {
        // Tạo một post không active
        Post::factory()->create([
            'user_id' => $this->user->id,
            'topic_id' => $this->topic->id,
            'status' => 0,
            'type' => 'post'
        ]);

        $response = $this->getJson("/api/posts/topic/{$this->topic->slug}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data'); // Chỉ có 1 post active được trả về
    }
}
