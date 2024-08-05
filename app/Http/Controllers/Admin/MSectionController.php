<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MSection\StoreRequest;
use App\Http\Requests\Admin\MSection\UpdateRequest;
use App\Http\Resources\MSectionResource;
use App\Http\Response;
use App\Models\MSection;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class MSectionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Organization  $organization
     * @return AnonymousResourceCollection
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $query = $organization->mSections();

        return MSectionResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest  $request
     * @param  Organization  $organization
     * @return MSectionResource|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): MSectionResource|JsonResponse
    {
        $this->authorize(OrganizationPolicy::KBASE_STORE, $organization);

        $section = $organization->mSections()->make($request->validated());

        if ($request->has('cover')) {
            $resultUploaded = $this->uploadImage($request, $organization, $section);

            if (is_array($resultUploaded)) {
                return Response::error(...$resultUploaded);
            }
        }

        $section->save();

        return new MSectionResource($section);
    }

    /**
     * Display the specified resource.
     *
     * @param  Organization  $organization
     * @param  MSection  $section
     * @return MSectionResource
     */
    public function show(Organization $organization, MSection $section): MSectionResource
    {
        $this->checkInOrganization($organization, $section);

        return new MSectionResource($section);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateRequest  $request
     * @param  Organization  $organization
     * @param  MSection  $section
     * @return MSectionResource|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        MSection $section,
    ): MSectionResource|JsonResponse {
        $this->authorize(OrganizationPolicy::KBASE_UPDATE, $organization);
        $this->checkInOrganization($organization, $section);

        $section->fill($request->validated());

        if ($request->has('cover')) {
            $resultUploaded = $this->uploadImage($request, $organization, $section);

            if (is_array($resultUploaded)) {
                return Response::error(...$resultUploaded);
            }
        }

        $section->save();

        return new MSectionResource($section);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Organization  $organization
     * @param  MSection  $section
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, MSection $section): JsonResponse
    {
        $this->authorize(OrganizationPolicy::KBASE_DESTROY, $organization);
        $this->checkInOrganization($organization, $section);

        $storage = Storage::disk(config('filesystems.public'));

        if ($storage->exists("mcovers/$section->cover")) {
            $storage->delete("mcovers/$section->cover");
        }

        $section->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, MSection $section): void
    {
        if ($organization->id !== $section->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function uploadImage(Request $request, Organization $organization, MSection $section): bool|array
    {
        try {
            $url = $request->get('cover');

            if ($url) {
                $file = Image::make($url);
            } else {
                $file = $request->file('cover');

                if ($file) {
                    $file = Image::make($file);
                }
            }
        } catch (Throwable $error) {
            return [$error->getMessage()];
        }

        if (! isset($file) || ! $file instanceof \Intervention\Image\Image) {
            return [
                __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE,
            ];
        }

        $time = time();
        $fileName = "{$organization->id}_$time.jpg";

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk(config('filesystems.public'));
        $fileWasUploaded = $storage->put("mcovers/$fileName", (string) $file->stream('jpg'));

        if (! $fileWasUploaded) {
            return [
                __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE,
            ];
        }

        if ($storage->exists("mcovers/$section->cover")) {
            $storage->delete("mcovers/$section->cover");
        }

        $section->cover = $fileName;
        $section->save();

        return true;
    }
}
