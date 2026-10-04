<?php

namespace Modules\Search\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Transformers\UserResource;
use Modules\Posts\Transformers\PostResource;
use Modules\Search\Actions\SearchAll;
use Modules\Search\Actions\SearchPosts;
use Modules\Search\Actions\SearchUsers;
use Modules\Search\Http\Requests\SearchRequest;

class SearchController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Search
     * 
     * Unified Search
     * 
     * Search across users and posts with optional type filtering.
     * 
     * @queryParam q string required The search term. Example: laravel
     * @queryParam type string Filter by result type (all, users, posts). Defaults to all. Example: all
     * @queryParam per_page integer Number of items per page. Example: 15
     */
    public function search(
        SearchRequest $request,
        SearchAll $searchAll,
        SearchUsers $searchUsers,
        SearchPosts $searchPosts
    ): JsonResponse {
        $query = $request->string('q')->trim()->toString();
        $type = $request->string('type', 'all')->toString();
        $perPage = $request->integer('per_page', 15);
        $viewer = $request->user();

        if ($type === 'users') {
            $users = ($searchUsers)($query, $perPage);
            $paginated = UserResource::collection($users)->toResponse($request)->getData(true);

            return $this->success(
                data: $paginated['data'],
                message: 'Users retrieved successfully',
                meta: [
                    'links' => $paginated['links'],
                    'meta' => $paginated['meta'],
                ]
            );
        }

        if ($type === 'posts') {
            $posts = ($searchPosts)($query, $viewer, $perPage);
            $paginated = PostResource::collection($posts)->toResponse($request)->getData(true);

            return $this->success(
                data: $paginated['data'],
                message: 'Posts retrieved successfully',
                meta: [
                    'links' => $paginated['links'],
                    'meta' => $paginated['meta'],
                ]
            );
        }

        $results = ($searchAll)($query, $viewer, min($perPage, 10));

        return $this->success(
            data: [
                'users' => UserResource::collection($results['users']),
                'posts' => PostResource::collection($results['posts']),
            ],
            message: 'Search results retrieved successfully'
        );
    }
}
