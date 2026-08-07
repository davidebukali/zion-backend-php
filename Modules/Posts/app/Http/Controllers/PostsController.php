<?php

namespace Modules\Posts\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Posts\Http\Requests\StorePostRequest;
use Modules\Posts\Actions\CreatePost;
use Modules\Posts\Actions\ListPosts;
use Modules\Posts\Transformers\PostResource;

class PostsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, ListPosts $listPosts)
    {
        $posts = $listPosts($request->integer('per_page', 15));
        return PostResource::collection($posts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePostRequest $request, CreatePost $createPost) {
        $post = $createPost($request->user(), $request->validated());
        return response()->json($post, 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('posts::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('posts::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
