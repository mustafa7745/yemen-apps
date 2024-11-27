<?php
namespace App\Http\Controllers\Api\Users;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index()
    {
        $posts = DB::table('options')
        ->where('id','like', 1)
        ->where('id','like', 2)
        ->where('id','like', 3)
        ->get();
        return response()->json($posts);
        // return new JsonResponse([
        //     'data' => 88888
        // ]);

        // return Post::all();
    }

    // public function store(Request $request)
    // {
    //     // $post = Post::create($request->validate([
    //     //     'title' => 'required|string|max:255',
    //     //     'content' => 'required|string',
    //     // ]));
    //     // return response()->json($post, 201);
    //     return new JsonResponse([
    //         'data' => 11111
    //     ]);
    // }

    // public function show(Post $post)
    // {
    //     return new JsonResponse([
    //         'data' => 55555
    //     ]);
    //     // return $post;
    // }

    // public function update(Request $request, Post $post)
    // {
    //     $post->update($request->validate([
    //         'title' => 'sometimes|required|string|max:255',
    //         'content' => 'sometimes|required|string',
    //     ]));
    //     return response()->json($post);
    // }

    // public function destroy(Post $post)
    // {
    //     $post->delete();
    //     return response()->json(null, 204);
    // }
}
