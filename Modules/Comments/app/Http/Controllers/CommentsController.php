<?php

namespace Modules\Comments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Comments\Http\Requests\StoreCommentRequest;
use Modules\Comments\Actions\CreateComment;
use Modules\Posts\Models\Post;
use Modules\Comments\Transformers\CommentResource;
use Modules\Comments\Actions\ListComments;
use Modules\Comments\Http\Requests\UpdateCommentRequest;
use Modules\Comments\Actions\UpdateComment;
use Modules\Comments\Models\Comment;
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
    public function update(Comment $comment, UpdateCommentRequest $request, UpdateComment $updateComment)
    {
        if ($request->user()->cannot('update', $comment)) {
            abort(403);
        }

        $updatedComment = $updateComment($comment, $request->validated());

        return $this->success(new CommentResource($updatedComment), 'Comment updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
