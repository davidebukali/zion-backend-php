<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Illuminate\Http\Request;
use Modules\Interactions\Actions\ShareExternalPost;
use Modules\Interactions\Actions\ShareInternalPost;
use Modules\Interactions\Actions\UnsharePost;
use Modules\Posts\Models\Post;

class PostShareController extends Controller
{
    use RespondsWithApi;

    public function internalSharePost(Request $request, Post $post, ShareInternalPost $shareInternalPost)
    {
        $shareInternalPost($request->user(), $post);

        return $this->success(message: 'Post shared internally successfully');
    }

    public function externalSharePost(Request $request, Post $post, ShareExternalPost $shareExternalPost)
    {
        $shareExternalPost($request->user(), $post);

        return $this->success(message: 'Post shared externally successfully');
    }

    public function unsharePost(Request $request, Post $post, UnsharePost $unsharePost)
    {
        $unsharePost($request->user(), $post);

        return $this->success(message: 'Post unshared successfully');
    }
}


