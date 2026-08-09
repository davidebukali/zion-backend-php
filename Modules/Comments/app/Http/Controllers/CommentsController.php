<?php

namespace Modules\Comments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Comments\Http\Requests\StoreCommentRequest;
use Modules\Comments\Actions\CreateComment;
use Modules\Posts\Models\Post;
use Modules\Comments\Transformers\CommentResource;
use Modules\Comments\Actions\ListComments;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    use RespondsWithApi;
    /**
     * Display a listing of the resource.
     */
    public function index(Post $post, Request $request, ListComments $listComments)
    {
        $comments = $listComments($post, $request->integer('per_page', 15));
        $paginated = CommentResource::collection($comments)->toResponse($request)->getData(true);
        return $this->success(
            data: $paginated['data'],
            meta: [
                'links' => $paginated['links'],
                'meta' => $paginated['meta'],
            ]
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('comments::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Post $post, StoreCommentRequest $request, CreateComment $createComment)
    {
        $comment = $createComment($request->user(), $post, $request->validated());
        return $this->success(new CommentResource($comment), 'Comment created successfully', 201);
    }

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('comments::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('comments::edit');
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
