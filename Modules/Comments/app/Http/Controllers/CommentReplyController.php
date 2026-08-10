<?php

namespace Modules\Comments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Comments\Models\Comment;
use Modules\Comments\Transformers\CommentResource;
use Modules\Comments\Actions\CreateReply;
use Modules\Comments\Actions\ListReplies;
use Illuminate\Http\Request;

class CommentReplyController extends Controller
{
    use RespondsWithApi;
    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
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
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
