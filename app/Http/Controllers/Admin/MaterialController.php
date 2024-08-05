<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Material\StoreRequest;
use App\Http\Requests\Admin\Material\UpdateRequest;
use App\Http\Resources\MaterialFullResource;
use App\Http\Resources\MaterialListResource;
use App\Http\Response;
use App\Models\Material;
use App\Models\MSection;
use App\Models\NewsTag;
use App\Models\Organization;
use App\Models\User;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class MaterialController extends Controller
{
    public const FILTERS = [
        'id',
        'index',
        'published',
        'created_at',
        'updated_at',
        'published_at',
    ];

    public function index(Request $request, Organization $organization): AnonymousResourceCollection
    {
        $query = $organization->materials();

        if ($request->has('section')) {
            $query->where('m_section_id', $request->get('section'));
        }

        self::filterQuery($query, $request->only(self::FILTERS));

        $sortBy = $request->get('sortBy', 'index');
        $sortDirection = $request->get('sortDirection', 'asc');

        if (in_array($sortBy, self::FILTERS, true)) {
            $query->orderBy($sortBy, $sortDirection);
        }

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return MaterialListResource::collection($query->paginate($limit));
        }

        return MaterialListResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): JsonResource
    {
        $this->authorize(OrganizationPolicy::KBASE_STORE, $organization);

        /** @var User $user */
        $user = $request->user();

        /** @var Material $material */
        $material = $organization->materials()->make($request->validated());
        $material->organization_id = $organization->id;
        $material->m_section_id = $request->get('section');
        $material->user_id = $user->id;
        $material->type ??= 'text';
        $material->published = false;
        $material->index = $this->getLastIndex($request->get('section'));
        $material->save();

        $this->syncTags($material, $request->get('tags'));

        return new MaterialFullResource($material);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Material $material): JsonResource
    {
        $this->checkInOrganization($organization, $material);

        if (! $material->published) {
            $this->authorize(OrganizationPolicy::KBASE_VIEW_UNPUBLISH, $organization);
        }

        return new MaterialFullResource($material);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization, Material $material): JsonResource
    {
        $this->authorize(OrganizationPolicy::KBASE_UPDATE, $organization);
        $this->checkInOrganization($organization, $material);

        $material->fill($request->validated());

        if ($request->has('section')) {
            $material->m_section_id = $request->get('section');
        }

        $material->save();

        $this->syncTags($material, $request->get('tags'));

        return new MaterialFullResource($material);
    }

    /**
     * @throws AuthorizationException
     */
    public function drag(Organization $organization, Material $material, int $after): JsonResource
    {
        $this->authorize(OrganizationPolicy::KBASE_UPDATE, $organization);
        $this->checkInOrganization($organization, $material);

        $this->moveAfter($organization, $material, $after);

        return new MaterialFullResource($material);
    }

    /**
     * @throws AuthorizationException
     */
    public function uploadImage(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize(OrganizationPolicy::KBASE_STORE, $organization);

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

        if (! $storage->exists("materials/$organization->id/$folder1/$folder2/$fileName")) {
            $fileWasUploaded = $storage->put(
                "materials/$organization->id/$folder1/$folder2/$fileName",
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
                'url' => $storage->url("materials/$organization->id/$folder1/$folder2/$fileName"),
            ]
        );
    }

    /**
     * @throws AuthorizationException
     */
    public function publish(Organization $organization, Material $material): JsonResource
    {
        $this->authorize(OrganizationPolicy::KBASE_PUBLISH, $organization);
        $this->checkInOrganization($organization, $material);

        $material->published = true;
        $material->save();

        return new MaterialFullResource($material);
    }

    /**
     * @throws AuthorizationException
     */
    public function unpublish(Organization $organization, Material $material): JsonResource
    {
        $this->authorize(OrganizationPolicy::KBASE_PUBLISH, $organization);
        $this->checkInOrganization($organization, $material);

        $material->published = false;
        $material->save();

        return new MaterialFullResource($material);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Material $material): JsonResponse
    {
        $this->authorize(OrganizationPolicy::KBASE_DESTROY, $organization);
        $this->checkInOrganization($organization, $material);

        $material->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, Material $material): void
    {
        if ($organization->id !== $material->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function syncTags(Material $material, array|null $tags): void
    {
        if ($tags !== null) {
            /** @var Collection<int, int> */
            $tagsID = new Collection();

            foreach ($tags as $tag) {
                /** @var NewsTag $newsTag */
                $newsTag = NewsTag::query()->firstOrCreate(['tag' => $tag]);
                $tagsID->add($newsTag->id);
            }

            $material->newsTags()->sync($tagsID);
        }
    }

    private function getLastIndex(int $section_id): int
    {
        $section = MSection::findOrFail($section_id);

        /** @var ?int $lastIndex */
        $lastIndex = $section->materials()
            ->orderBy('index', 'desc')->first()?->index;

        return $lastIndex !== null ? $lastIndex + 1 : 0;
    }

    private function moveAfter(Organization $organization, Material $material, int $after): void
    {
        DB::beginTransaction();

        $after = max($after, -1);

        $counter = $after + 2;

        $organization->materials()->where('index', '>', $after)
            ->orderBy('index')->get()
            ->each(static function (Material $material) use (&$counter) {
                $material->index = $counter++;
                $material->save();
            });

        $material->index = $after + 1;
        $material->save();

        DB::commit();
    }
}
