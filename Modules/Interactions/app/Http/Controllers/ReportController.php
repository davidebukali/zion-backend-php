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
     * Store a newly created resource in storage.
     */
    public function store(StoreReportRequest $request, Post $post, ReportPost $reportPost)
    {
        $reportPost($request->user(), $post, $request->validated());

        return $this->success(message: 'Post reported successfully', status: 201);
    }
}

