<?php

namespace Modules\Churches\Http\Controllers;

use App\Http\Controllers\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Churches\Models\Church;
use Modules\Churches\Transformers\ChurchResource;

class ChurchesController extends Controller
{
    use RespondsWithApi;

    /**
     * @group Churches
     *
     * List Churches
     *
     * Retrieve a paginated list of predetermined and community-added churches, with optional search filtering.
     *
     * @queryParam search string Filter churches by name or location. Example: Watoto
     * @queryParam per_page integer Number of items per page (default: 50, max: 100). Example: 50
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Churches retrieved successfully.",
     *   "data": [
     *     {
     *       "id": "01j98z3q4x6y7w8v9u0t1s2c01",
     *       "name": "Watoto Church",
     *       "location": "Uganda",
     *       "created_at": "2026-11-02T20:00:00.000000Z",
     *       "updated_at": "2026-11-02T20:00:00.000000Z"
     *     }
     *   ],
     *   "meta": {
     *     "current_page": 1,
     *     "from": 1,
     *     "last_page": 1,
     *     "per_page": 50,
     *     "to": 1,
     *     "total": 1
     *   }
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->string('search')->trim()->toString();
        $perPage = min(max($request->integer('per_page', 50), 1), 100);

        $query = Church::query();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('location', 'ilike', "%{$search}%");
            });
        }

        $paginated = $query->orderBy('name')
            ->paginate($perPage);

        $resourceData = ChurchResource::collection($paginated)->response()->getData(true);

        return $this->success(
            data: $resourceData['data'] ?? [],
            message: 'Churches retrieved successfully.',
            status: 200,
            meta: $resourceData['meta'] ?? []
        );
    }
}

