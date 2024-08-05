<?php

declare(strict_types=1);

namespace App\Http\Controllers\SAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SAdmin\NewsRequest;
use App\Http\Resources\SAdmin\NewsResource;
use App\Http\Response;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NewsController extends Controller
{
    public function index(Request $request): JsonResource
    {
        $query = News::query();

        $query->with([
            'organization',
            'user',
        ]);
        $query->withCount([
            'impressions',
            'clicks',
            'abuses',
        ]);

        foreach ($request->all() as $id => $value) {
            switch ($id) {
                case 'id':
                    $query->where('id', $value);

                    break;
                case 'title':
                    $query->where('title', 'like', "%$value%");

                    break;
                case 'visible':
                    $query->where('visible', $value);

                    break;
                case 'published':
                    $query->where('published', $value === 'true');

                    break;
                case 'featured':
                    $query->where('featured', $value === 'true');

                    break;
            }
        }

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        $query->orderBy($sortBy, $sortDirection);

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return NewsResource::collection($query->paginate($limit));
        }

        return NewsResource::collection($query->get());
    }

    public function update(NewsRequest $request, News $news): JsonResource
    {
        $news->forceFill($request->validated())->save();

        return new NewsResource($news);
    }

    public function destroy(News $news): JsonResponse
    {
        $news->delete();

        return Response::noContent();
    }
}
