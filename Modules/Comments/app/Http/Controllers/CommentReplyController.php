<?php

namespace Modules\Comments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Comments\Models\Comment;
use Modules\Comments\Transformers\CommentResource;
use Modules\Comments\Actions\CreateReply;
use Modules\Comments\Actions\ListReplies;
use Modules\Comments\Actions\DeleteReply;
use Illuminate\Http\Request;

class CommentReplyController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Comments
     * @subgroup Replies
     * @authenticated
     * 
     * List Comment Replies
     * 
     * Retrieve paginated replies for a specific top-level comment.
     * 
     * @urlParam comment string required Parent comment UUID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @queryParam per_page integer Items per page. Example: 15
     * 
     * @response 200 {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "9c2eeb4d-3b7d-4bad-9bdd-2b0d7b3dcb6e",
     *       "content": "I completely agree!",
     *       "parent_comment_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d",
     *       "created_at": "2026-09-24T12:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "links": { "first": "...", "last": "...", "prev": null, "next": null },
     *     "meta": { "current_page": 1, "from": 1, "last_page": 1, "per_page": 15, "to": 1, "total": 1 }
     *   }
     * }
     */
    public function index(Comment $comment, Request $request, ListReplies $listReplies)
    {
        $replies = $listReplies($comment, $request->integer('per_page', 15));
        $paginated = CommentResource::collection($replies)->toResponse($request)->getData(true);
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
     * @subgroup Replies
     * @authenticated
     * 
     * Create Reply
     * 
     * Create a reply to a top-level comment.
     * 
     * @urlParam comment string required Parent comment UUID. Example: 9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d
     * @bodyParam content string required Reply text content. Example: I completely agree!
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Reply created successfully",
     *   "data": {
     *     "id": "9c2eeb4d-3b7d-4bad-9bdd-2b0d7b3dcb6e",
     *     "content": "I completely agree!",
     *     "parent_comment_id": "9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d"
     *   }
     * }
     */
    public function store(Comment $comment, Request $request, CreateReply $createReply)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        if ($comment->parent_comment_id !== null) {
            throw new \DomainException(
                'Replies to replies are not supported.'
            );
        }

        $reply = $createReply($request->user(), $comment, $data);

        return $this->success(new CommentResource($reply), 'Reply created successfully', 201);
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
     * @group Comments
     * @subgroup Replies
     * @authenticated
     * 
     * Delete Reply
     * 
     * Delete a comment reply.
     * 
     * @urlParam comment string required Reply comment UUID to delete. Example: 9c2eeb4d-3b7d-4bad-9bdd-2b0d7b3dcb6e
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Reply deleted successfully"
     * }
     * @response 403 status=403 scenario="Unauthorized deletion" {
     *   "message": "This action is unauthorized."
     * }
     */
    public function destroy(Comment $comment, Request $request, DeleteReply $deleteReply)
    {
        if ($request->user()->cannot('delete', $comment)) {
            abort(403);
        }

        $deleteReply($comment);

        return $this->success(message: 'Reply deleted successfully');
    }
}
