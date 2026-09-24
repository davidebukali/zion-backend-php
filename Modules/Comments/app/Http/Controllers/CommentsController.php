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
use Modules\Comments\Actions\DeleteComment;
use Illuminate\Http\Request;

class CommentsController extends Controller
{
    use RespondsWithApi;
    
    /**
     * @group Comments
     * @authenticated
     * 
     * List Comments
     * 
     * Retrieve paginated top-level comments for a post.
     * 
     * @urlParam post integer required The ID of the post. Example: 1
     * @queryParam per_page integer Items per page. Example: 15
     * @queryParam page integer Page number. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *       "content": "Great post!",
     *       "user": { "id": 1, "name": "Jane Doe" },
     *       "created_at": "2026-09-24T12:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "links": { "first": "...", "last": "...", "prev": null, "next": null },
     *     "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1 }
     *   }
     * }
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
     * @group Comments
     * @authenticated
     * 
     * Add Comment
     * 
     * Create a comment on a specific post.
     * 
     * @urlParam post integer required The ID of the post. Example: 1
     * @bodyParam content string required The text content of the comment. Example: Great post!
     * @bodyParam media_ids string[] Optional attached media UUIDs. Example: ["9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d"]
     * @bodyParam parent_comment_id string Optional parent comment UUID if creating a reply directly. Example: null
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Comment created successfully",
     *   "data": {
     *     "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *     "content": "Great post!",
     *     "created_at": "2026-09-24T12:00:00.000000Z"
     *   }
     * }
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
     * @group Comments
     * @authenticated
     * 
     * Update Comment
     * 
     * Update the content of an existing comment owned by the authenticated user.
     * 
     * @urlParam comment string required The UUID of the comment. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam content string required Updated comment text. Example: Updated comment text!
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Comment updated successfully",
     *   "data": {
     *     "id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *     "content": "Updated comment text!"
     *   }
     * }
     * @response 403 status=403 scenario="Unauthorized update" {
     *   "message": "This action is unauthorized."
     * }
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
     * @group Comments
     * @authenticated
     * 
     * Delete Comment
     * 
     * Delete a comment owned by the authenticated user.
     * 
     * @urlParam comment string required The UUID of the comment to delete. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Comment deleted successfully"
     * }
     * @response 403 status=403 scenario="Unauthorized deletion" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Comment $comment, Request $request, DeleteComment $action)
    {
        if ($request->user()->cannot('delete', $comment)) {
            abort(403);
        }

        $action($comment);

        return $this->success(message: 'Comment deleted successfully');
    }
}
