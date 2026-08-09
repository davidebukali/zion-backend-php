<?php

namespace Modules\Comments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Comments\Models\Comment;
use Modules\Comments\Transformers\CommentResource;
use Modules\Comments\Actions\CreateReply;
use Illuminate\Http\Request;

class CommentReplyController extends Controller
{
    use RespondsWithApi;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('comments::index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Comment $comment, Request $request, CreateReply $action)
    {
        $data = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $reply = $action($request->user(), $comment, $data);

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
