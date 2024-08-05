<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Banners\StoreRequest;
use App\Http\Requests\Admin\Banners\UpdateRequest;
use App\Http\Resources\BannerResource;
use App\Http\Response;
use App\Models\Banner;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class BannerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  Organization  $organization
     * @return AnonymousResourceCollection
     */
    public function index(Organization $organization): AnonymousResourceCollection
    {
        $query = $organization->banners();
        $query->orderBy('index');

        return BannerResource::collection($query->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  StoreRequest  $request
     * @param  Organization  $organization
     * @return BannerResource|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): BannerResource|JsonResponse
    {
        $this->authorize(OrganizationPolicy::BANNERS_STORE, $organization);

        /** @var Banner $banner */
        $banner = $organization->banners()->create($request->except(['large', 'small']));

        $resultUploaded = $this->uploadImage($request, $organization, $banner, 'large');

        if (is_array($resultUploaded)) {
            return Response::error(...$resultUploaded);
        }

        $resultUploaded = $this->uploadImage($request, $organization, $banner, 'small');

        if (is_array($resultUploaded)) {
            return Response::error(...$resultUploaded);
        }

        $banner->save();

        return new BannerResource($banner);
    }

    /**
     * Display the specified resource.
     *
     * @param  Organization  $organization
     * @param  Banner  $banner
     * @return BannerResource
     */
    public function show(Organization $organization, Banner $banner): BannerResource
    {
        $this->checkInOrganization($organization, $banner);

        return new BannerResource($banner);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  UpdateRequest  $request
     * @param  Organization  $organization
     * @param  Banner  $banner
     * @return BannerResource
     *
     * @throws AuthorizationException
     */
    public function update(UpdateRequest $request, Organization $organization, Banner $banner): BannerResource
    {
        $this->authorize(OrganizationPolicy::BANNERS_UPDATE, $organization);
        $this->checkInOrganization($organization, $banner);

        $banner->fill($request->all());
        $banner->save();

        return new BannerResource($banner);
    }

    /**
     * Upload large banner
     *
     * @param  Request  $request
     * @param  Organization  $organization
     * @param  Banner  $banner
     * @return BannerResource|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function uploadLarge(
        Request $request,
        Organization $organization,
        Banner $banner,
    ): BannerResource|JsonResponse {
        $this->authorize(OrganizationPolicy::BANNERS_UPDATE, $organization);
        $this->checkInOrganization($organization, $banner);

        $resultUploaded = $this->uploadImage($request, $organization, $banner, 'large');

        if (is_array($resultUploaded)) {
            return Response::error(...$resultUploaded);
        }

        return new BannerResource($banner);
    }

    /**
     * Upload small banner
     *
     * @param  Request  $request
     * @param  Organization  $organization
     * @param  Banner  $banner
     * @return BannerResource|JsonResponse
     *
     * @throws AuthorizationException
     */
    public function uploadSmall(
        Request $request,
        Organization $organization,
        Banner $banner,
    ): BannerResource|JsonResponse {
        $this->authorize(OrganizationPolicy::BANNERS_UPDATE, $organization);
        $this->checkInOrganization($organization, $banner);

        $resultUploaded = $this->uploadImage($request, $organization, $banner, 'small');

        if (is_array($resultUploaded)) {
            return Response::error(...$resultUploaded);
        }

        return new BannerResource($banner);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Organization  $organization
     * @param  Banner  $banner
     * @return JsonResponse
     *
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Banner $banner): JsonResponse
    {
        $this->authorize(OrganizationPolicy::BANNERS_DESTROY, $organization);
        $this->checkInOrganization($organization, $banner);

        $storage = Storage::disk(config('filesystems.public'));

        if ($storage->exists("bims/$banner->large")) {
            $storage->delete("bims/$banner->large");
        }

        if ($storage->exists("bims/$banner->small")) {
            $storage->delete("bims/$banner->small");
        }

        $banner->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, Banner $banner): void
    {
        if ($organization->id !== $banner->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function uploadImage(
        Request $request,
        Organization $organization,
        Banner $banner,
        string $size,
    ): bool|array {
        try {
            $url = $request->get($size);

            if ($url) {
                $file = Image::make($url);
            } else {
                $file = $request->file($size);

                if ($file) {
                    $file = Image::make($file);
                }
            }
        } catch (Throwable $error) {
            return [$error->getMessage()];
        }

        if (isset($file) && $file instanceof \Intervention\Image\Image) {
            $time = time();
            $fileName = "{$organization->id}_$time.$size.jpg";

            $width = $size === 'large' ? 1920 : 640;
            $height = $size === 'large' ? 400 : 320;
            $file->fit($width, $height);

            $storage = Storage::disk(config('filesystems.public'));
            $fileWasUploaded = $storage->put("bims/$fileName", (string) $file->stream('jpg'));

            if ($fileWasUploaded) {
                if ($storage->exists("bims/{$banner->$size}")) {
                    $storage->delete("bims/{$banner->$size}");
                }

                $banner->$size = $fileName;
                $banner->save();
            } else {
                return [
                    __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                    ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE,
                ];
            }
        } else {
            return [
                __('validation.mimes', ['attribute' => __('validation.attributes.image'), 'values' => 'image/*']),
                ResponseCode::HTTP_UNSUPPORTED_MEDIA_TYPE,
            ];
        }

        return true;
    }
}
