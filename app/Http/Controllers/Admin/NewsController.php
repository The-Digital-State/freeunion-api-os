<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\News\StoreRequest;
use App\Http\Requests\Admin\News\TelepostRequest;
use App\Http\Requests\Admin\News\UpdateRequest;
use App\Http\Resources\NewsAbuseResource;
use App\Http\Resources\NewsFullResource;
use App\Http\Resources\NewsListResource;
use App\Http\Response;
use App\Models\News;
use App\Models\NewsAbuse;
use App\Models\NewsTag;
use App\Models\Organization;
use App\Models\OrganizationTelepost;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class NewsController extends Controller
{
    public const FILTERS = [
        'id',
        'published',
        'created_at',
        'updated_at',
        'published_at',
    ];

    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->news()
            ->withCount(['impressions', 'clicks']);

        self::filterQuery($query, $request->only(self::FILTERS));

        $sortBy = $request->get('sortBy', 'id');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return NewsListResource::collection($query->paginate($limit));
        }

        return NewsListResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): NewsFullResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->authorize(OrganizationPolicy::NEWS_STORE, $organization);

        /** @var News $news */
        $news = $organization->news()->make($request->validated());
        $news->organization_id = $organization->id;
        $news->user_id = $user->id;
        $news->published = false;
        $news->save();

        $this->syncTags($news, $request->get('tags'));

        return new NewsFullResource($news);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, News $news): NewsFullResource
    {
        $this->checkInOrganization($organization, $news);

        if (! $news->published) {
            $this->authorize(OrganizationPolicy::NEWS_VIEW_UNPUBLISH, $organization);
        }

        $news->loadCount(['impressions', 'clicks']);

        return new NewsFullResource($news);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization, News $news): NewsFullResource
    {
        $this->authorize(OrganizationPolicy::NEWS_UPDATE, $organization);
        $this->checkInOrganization($organization, $news);

        $news->fill($request->validated());
        $news->save();

        $this->syncTags($news, $request->get('tags'));

        $news->loadCount(['impressions', 'clicks']);

        return new NewsFullResource($news);
    }

    /**
     * @throws AuthorizationException
     */
    public function uploadImage(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize(OrganizationPolicy::NEWS_STORE, $organization);

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
     * @throws AuthorizationException
     */
    public function publish(Organization $organization, News $news): NewsFullResource
    {
        $this->authorize(OrganizationPolicy::NEWS_PUBLISH, $organization);
        $this->checkInOrganization($organization, $news);

        $news->published = true;
        $news->save();

        $news->loadCount(['impressions', 'clicks']);

        return new NewsFullResource($news);
    }

    /**
     * @throws AuthorizationException
     */
    public function unpublish(Organization $organization, News $news): NewsFullResource
    {
        $this->authorize(OrganizationPolicy::NEWS_PUBLISH, $organization);
        $this->checkInOrganization($organization, $news);

        $news->published = false;
        $news->save();

        $news->loadCount(['impressions', 'clicks']);

        return new NewsFullResource($news);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, News $news): JsonResponse
    {
        $this->authorize(OrganizationPolicy::NEWS_DESTROY, $organization);
        $this->checkInOrganization($organization, $news);

        $news->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function abuses(Request $request, Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::NEWS_PUBLISH, $organization);

        $query = NewsAbuse::query()
            ->whereIn('news_id', $organization->news->pluck('id'))
            ->orderBy('id');
        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return NewsAbuseResource::collection($query->paginate($limit));
        }

        return NewsAbuseResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function abuseShow(Organization $organization, NewsAbuse $newsAbuse): JsonResource
    {
        $this->authorize(OrganizationPolicy::NEWS_PUBLISH, $organization);
        $this->checkInOrganization($organization, $newsAbuse->news);

        return new NewsAbuseResource($newsAbuse);
    }

    /**
     * @throws AuthorizationException
     */
    public function abuseDestroy(Organization $organization, NewsAbuse $newsAbuse): JsonResponse
    {
        $this->authorize(OrganizationPolicy::NEWS_PUBLISH, $organization);
        $this->checkInOrganization($organization, $newsAbuse->news);

        $newsAbuse->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
    public function telepost(TelepostRequest $request, Organization $organization, News $news): JsonResponse
    {
        $this->authorize(OrganizationPolicy::NEWS_PUBLISH, $organization);

        $this->checkInOrganization($organization, $news);

        foreach ($request->get('telepost') as $telepostId) {
            $telepost = OrganizationTelepost::find($telepostId);

            if (
                $telepost instanceof OrganizationTelepost
                && $telepost->organization_id === $organization->id
                && $telepost->verify_code === null
            ) {
                $telepost->sendNews($news);
            }
        }

        return Response::success();
    }

    private function checkInOrganization(Organization $organization, News $news): void
    {
        if ($organization->id !== $news->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function syncTags(News $news, array|null $tags): void
    {
        if ($tags !== null) {
            /** @var Collection<int, int> */
            $tagsID = new Collection();

            foreach ($tags as $tag) {
                /** @var NewsTag $newsTag */
                $newsTag = NewsTag::query()->firstOrCreate(['tag' => $tag]);
                $tagsID->add($newsTag->id);
            }

            $news->newsTags()->sync($tagsID);
        }
    }
}
