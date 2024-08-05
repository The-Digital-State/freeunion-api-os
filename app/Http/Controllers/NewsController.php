<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\News\AbuseRequest;
use App\Http\Requests\News\StoreRequest;
use App\Http\Resources\NewsFullResource;
use App\Http\Resources\NewsResource;
use App\Http\Resources\NewsShortResource;
use App\Http\Response;
use App\Models\News;
use App\Models\NewsAbuse;
use App\Models\NewsClick;
use App\Models\NewsImpression;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Throwable;

class NewsController extends Controller
{
    public const FILTERS = [
        'id',
        'featured',
        'published_at',
        'organization_id',
    ];

    public function index(Request $request): JsonResource
    {
        $query = $this->getQuery($request, News::query());

        $tags = $request->get('tags', []);

        if (is_array($tags) && count($tags) > 0) {
            $query->whereHas('newsTags', static function (Builder $q) use ($tags) {
                $q->whereIn('tag', $tags);
            });
        }

        $limit = (int) $request->get('limit', 0);
        $result = $limit > 0 ? $query->paginate($limit) : $query->get();
        $items = $result instanceof LengthAwarePaginator ? new Collection($result->items()) : $result;

        $user = $request->user();

        DB::beginTransaction();
        $items->pluck('id')->each(static function (int $newsId) use ($request, $user) {
            if ($user) {
                NewsImpression::firstOrCreate([
                    'news_id' => $newsId,
                    'user_id' => $user->id,
                ], [
                    'ip' => $request->ip(),
                ]);
            } else {
                NewsImpression::firstOrCreate([
                    'news_id' => $newsId,
                    'user_id' => null,
                    'ip' => $request->ip(),
                ]);
            }
        });
        DB::commit();

        return NewsShortResource::collection($result);
    }

    public function show(Request $request, News $news): NewsResource
    {
        $user = $request->user();

        if (! $this->canView($user, $news)) {
            throw (new ModelNotFoundException())->setModel(News::class);
        }

        if ($user) {
            NewsClick::firstOrCreate([
                'news_id' => $news->id,
                'user_id' => $user->id,
            ], [
                'ip' => $request->ip(),
            ]);
        } else {
            NewsClick::firstOrCreate([
                'news_id' => $news->id,
                'user_id' => null,
                'ip' => $request->ip(),
            ]);
        }

        $result = $this->getPrevNext($request, $news);

        /** @var News|null $previousNews */
        $previousNews = isset($result['previousId']) ? News::find($result['previousId']) : null;
        /** @var News|null $nextNews */
        $nextNews = isset($result['nextId']) ? News::find($result['nextId']) : null;

        return (new NewsResource($news))->addPrevNext(
            $previousNews?->id,
            $previousNews?->organization_id,
            $nextNews?->id,
            $nextNews?->organization_id
        );
    }

    public function abuse(AbuseRequest $request, News $news): JsonResponse
    {
        if (! $this->canView($request->user(), $news)) {
            throw (new ModelNotFoundException())->setModel(News::class);
        }

        $abuse = new NewsAbuse();
        $abuse->fill($request->all());
        $abuse->news_id = $news->id;
        $abuse->save();

        return Response::success();
    }

    public function orgIndex(Request $request): JsonResource
    {
        return $this->index($request);
    }

    public function orgShow(Request $request, Organization $organization, News $news): NewsResource
    {
        if ($organization->id !== $news->organization_id) {
            throw new ModelNotFoundException();
        }

        return $this->show($request, $news);
    }

    public function orgStore(StoreRequest $request, Organization $organization): NewsFullResource
    {
        $user = $request->user();

        if ($user === null || ! $user->membership->pluck('id')->contains($organization->id)) {
            throw new BadRequestHttpException();
        }

        $news = $organization->news()->make($request->validated());
        $news->organization_id = $organization->id;
        $news->user_id = $user->id;
        $news->published = false;
        $news->save();

        return new NewsFullResource($news);
    }

    public function uploadImage(Request $request, Organization $organization): JsonResponse
    {
        $user = $request->user();

        if ($user === null || ! $user->membership->pluck('id')->contains($organization->id)) {
            throw new BadRequestHttpException();
        }

        try {
            $url = $request->get('image');

            if ($url) {
                $file = Image::make($url);
            } else {
                $file = $request->file('image');

                if ($file) {
                    $file = Image::make($file);
                }
            }
        } catch (Throwable $error) {
            return Response::error($error->getMessage());
        }

        if (! isset($file) || ! $file instanceof \Intervention\Image\Image) {
            return Response::error(
                __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE
            );
        }

        $hash = md5((string) $file->stream('jpg'));
        $folder1 = mb_substr($hash, 0, 2);
        $folder2 = mb_substr($hash, 2, 2);
        $fileName = mb_substr($hash, 4).'.jpg';

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(config('filesystems.public'));

        if (! $storage->exists("news/$organization->id/$folder1/$folder2/$fileName")) {
            $fileWasUploaded = $storage->put(
                "news/$organization->id/$folder1/$folder2/$fileName",
                (string) $file->stream('jpg')
            );

            if (! $fileWasUploaded) {
                return Response::error(
                    __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                    ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE
                );
            }
        }

        return Response::success(
            [
                'url' => $storage->url("news/$organization->id/$folder1/$folder2/$fileName"),
            ]
        );
    }

    /**
     * @param  Request  $request
     * @param  Builder<News>  $query
     * @return Builder<News>
     */
    private function getQuery(Request $request, Builder $query): Builder
    {
        $user = $request->user();

        $query->withCount(['impressions', 'clicks']);
        $query->where('published', true);

        if ($user === null) {
            $query->where('visible', News::VISIBLE_ALL);
        } else {
            $query->where(static function (Builder $q) use ($user) {
                $q->whereIn('visible', [News::VISIBLE_ALL, News::VISIBLE_USERS]);
                $q->orWhereIn('organization_id', $user->membership->pluck('id'));
            });
        }

        self::filterQuery($query, $request->only(self::FILTERS));

        $query->whereHas('organization', static function (Builder $q) use ($request, $user) {
            $q->where(static function (Builder $q) use ($user) {
                $q->whereIn('public_status', [
                    Organization::PUBLIC_STATUS_SHOW,
                    Organization::PUBLIC_STATUS_PARTIALLY_HIDDEN,
                ]);

                if ($user) {
                    $q->orWhereIn('organization_id', $user->membership->pluck('id'));
                }
            });

            $interestScope = $request->get('interest_scope');

            if ($interestScope) {
                $q->whereHas('interestScope', static function (Builder $q) use ($interestScope) {
                    self::filterQuery($q, ['id' => $interestScope]);
                });
            }
        });

        $sortBy = $request->get('sortBy', 'published_at');
        $sortDirection = $request->get('sortDirection', 'desc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        if ($sortBy === 'popular') {
            $query->orderByRaw('clicks_count / impressions_count desc, id desc');
        }

        return $query;
    }

    private function getPrevNext(Request $request, News $news): ?array
    {
        $query = $this->getQuery($request, News::query());
        $order = $query->getQuery()->orders;

        if (isset($order[0]['column'])) {
            $overOrder = $order[0]['column'].' '.$order[0]['direction'];
        } elseif (isset($order[0]['sql'])) {
            $overOrder = $order[0]['sql'];
        } else {
            $overOrder = 'id';
        }

        $query->addSelect([
            DB::raw("LAG(news.id) OVER (ORDER BY $overOrder) as previousId"),
            DB::raw("LEAD(news.id) OVER (ORDER BY $overOrder) as nextId"),
        ]);
        $query->groupBy('news.id');

        return News::from($query->getQuery())
            ->where('id', $news->id)
            ->first()?->toArray();
    }

    private function canView(User|null $user, News $news): bool
    {
        $canView = false;

        if ($news->published) {
            $canView = $user === null ? $news->visible === News::VISIBLE_ALL
                : in_array($news->visible, [News::VISIBLE_ALL, News::VISIBLE_USERS], true)
                || $user->membership->pluck('id')->contains($news->organization_id);

            $canView = $canView
                && (
                    in_array(
                        $news->organization->public_status,
                        [
                            Organization::PUBLIC_STATUS_SHOW,
                            Organization::PUBLIC_STATUS_PARTIALLY_HIDDEN,
                        ],
                        true
                    )
                    || (
                        $user && $user->membership->pluck('id')->contains($news->organization_id)
                    )
                );
        }

        return $canView;
    }
}
