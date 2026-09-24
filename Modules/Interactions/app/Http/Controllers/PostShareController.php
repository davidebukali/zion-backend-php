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

    /**
     * @group Interactions
     * @subgroup Shares
     * @authenticated
     * 
     * Share Post Internally
     * 
     * Share a post internally within the platform.
     * 
     * @urlParam post integer required The ID of the post. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Post shared internally successfully"
     * }
     */
    public function internalSharePost(Request $request, Post $post, ShareInternalPost $shareInternalPost)
    {
        $shareInternalPost($request->user(), $post);

        return $this->success(message: 'Post shared internally successfully');
    }

    /**
     * @group Interactions
     * @subgroup Shares
     * @authenticated
     * 
     * Share Post Externally
     * 
     * Record an external share action for a post.
     * 
     * @urlParam post integer required The ID of the post. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Post shared externally successfully"
     * }
     */
    public function externalSharePost(Request $request, Post $post, ShareExternalPost $shareExternalPost)
    {
        $shareExternalPost($request->user(), $post);

        return $this->success(message: 'Post shared externally successfully');
    }

    /**
     * @group Interactions
     * @subgroup Shares
     * @authenticated
     * 
     * Unshare Post
     * 
     * Remove share record for a post.
     * 
     * @urlParam post integer required The ID of the post. Example: 1
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Post unshared successfully"
     * }
     */
    public function unsharePost(Request $request, Post $post, UnsharePost $unsharePost)
    {
        $unsharePost($request->user(), $post);

        return $this->success(message: 'Post unshared successfully');
    }
}
