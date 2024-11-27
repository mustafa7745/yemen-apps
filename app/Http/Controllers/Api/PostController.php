<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function index()
    {
        return new JsonResponse([
            'data' => 99999
        ]);

        // return Post::all();
    }

    public function store(Request $request)
    {
        $post = Post::create($request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
        ]));
        return response()->json($post, 201);
    }

    public function show(Post $post)
    {
        return $post;
    }

    public function update(Request $request, Post $post)
    {
        $post->update($request->validate([
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]));
        return response()->json($post);
    }

    public function destroy(Post $post)
    {
        $post->delete();
        return response()->json(null, 204);
    }
}
