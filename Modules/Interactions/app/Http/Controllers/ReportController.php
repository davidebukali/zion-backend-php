<?php

namespace Modules\Interactions\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Concerns\RespondsWithApi;
use Modules\Interactions\Actions\ReportPost;
use Modules\Interactions\Http\Requests\StoreReportRequest;
use Modules\Posts\Models\Post;

class ReportController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Interactions
     * @subgroup Reports
     * @authenticated
     * 
     * Report Post
     * 
     * Submit a report for a post violating platform guidelines.
     * 
     * @urlParam post integer required The ID of the post to report. Example: 1
     * @bodyParam reason string required Reason for reporting. Allowed values: spam, harassment, hate_speech, violence, sexual_content, false_information, other. Example: spam
     * @bodyParam description string Optional detailed description of the violation. Example: Unwanted promotional spam.
     * 
     * @response 201 {
     *   "success": true,
     *   "message": "Post reported successfully"
     * }
     * @response 422 status=422 scenario="Validation error" {
     *   "message": "The selected reason is invalid.",
     *   "errors": { "reason": ["The selected reason is invalid."] }
     * }
     */
    public function store(StoreReportRequest $request, Post $post, ReportPost $reportPost)
    {
        $reportPost($request->user(), $post, $request->validated());

        return $this->success(message: 'Post reported successfully', status: 201);
    }
}
