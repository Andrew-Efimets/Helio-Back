<?php

namespace App\Http\Controllers;

use App\Events\PostCreated;
use App\Http\Requests\PostRequest;
use App\Jobs\SendPostImageToS3;
use App\Models\Post;
use App\Models\User;
use App\Traits\HasOwnerStatus;

class PostController extends Controller
{
    use HasOwnerStatus;
    public function index(User $user)
    {
        $posts = $user->posts()
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return response()->json([
            'message' => 'Посты успешно получены',
            'data' => $posts,
        ]);
    }

    public function store(PostRequest $request, User $user)
    {
        $data = $request->validated();

        $post = $user->posts()->create([
            'content' => $data['content'] ?? null,
            'path' => null,
            'image_url' => null,
        ]);

        if ($request->hasFile('image')) {
            $file = $request->file('image');

            $tempPath = $file->store('temp_posts', 'local');

            SendPostImageToS3::dispatch($post, $tempPath);
        } else {
            broadcast(new PostCreated($post));
        }

        return response()->json([
            'message' => 'Запись принята в обработку',
            'data' => $post,
        ], 201);
    }

    public function update(User $user, Post $post)
    {

    }

    public function destroy(User $user, Post $post)
    {

    }
}

