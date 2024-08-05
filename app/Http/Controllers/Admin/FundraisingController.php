<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Fundraising\StoreRequest;
use App\Http\Requests\Admin\Fundraising\UpdateRequest;
use App\Http\Resources\FundraisingResource;
use App\Http\Response;
use App\Models\Fundraising;
use App\Models\Organization;
use App\Policies\OrganizationPolicy;
use App\Services\Payments\Stripe;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class FundraisingController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->fundraisings();

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return FundraisingResource::collection($query->paginate($limit));
        }

        return FundraisingResource::collection($query->get());
    }

    public function store(StoreRequest $request, Organization $organization): FundraisingResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $fundraising = $organization->fundraisings()->create($request->validated());

        foreach ($request->auto_payments as $paymentSystem) {
            $this->addAutoPaymentSystem($fundraising, $paymentSystem);
        }

        return new FundraisingResource($fundraising);
    }

    public function show(Organization $organization, Fundraising $fundraising): FundraisingResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $this->checkInOrganization($organization, $fundraising);

        return new FundraisingResource($fundraising);
    }

    public function update(
        UpdateRequest $request,
        Organization $organization,
        Fundraising $fundraising,
    ): FundraisingResource {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $this->checkInOrganization($organization, $fundraising);

        $fundraising->update($request->validated());
        $this->updateAutoPayments($fundraising, $request['auto_payments']);

        return new FundraisingResource($fundraising);
    }

    public function destroy(Organization $organization, Fundraising $fundraising): JsonResponse
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);
        $this->checkInOrganization($organization, $fundraising);

        $fundraising->delete();

        return Response::noContent();
    }

    public function payments(Organization $organization): JsonResponse
    {
        $payments = $organization->paymentSystems()->where('active', true)->pluck('payment_system')->toArray();

        return new JsonResponse($payments);
    }

    public function uploadImage(Request $request, Organization $organization): JsonResponse
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

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

        if (! $storage->exists("fundraisings/$organization->id/$folder1/$folder2/$fileName")) {
            $fileWasUploaded = $storage->put(
                "fundraisings/$organization->id/$folder1/$folder2/$fileName",
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
                'url' => $storage->url("fundraisings/$organization->id/$folder1/$folder2/$fileName"),
            ]
        );
    }

    public function all(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->fundraisingsAndSubscriptions();

        $limit = (int) $request->get('limit', 0);
        $result = $limit > 0 ? $query->paginate($limit) : $query->get();

        return FundraisingResource::collection($result);
    }

    private function checkInOrganization(Organization $organization, Fundraising $fundraising): void
    {
        if ($organization->id !== $fundraising->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function updateAutoPayments(Fundraising $fundraising, array $payments): void
    {
        $organization = $fundraising->organization;
        $fundraisingPayments = $fundraising->paymentSystems()->pluck('payment_system')->toArray();
        $deletePayments = array_diff($fundraisingPayments, $payments);
        $addPayments = array_diff($payments, $fundraisingPayments);

        foreach ($deletePayments as $payment) {
            $paymentSystem = $organization->paymentSystems()->where('payment_system', $payment)->first();
            $fundraising->paymentSystems()->detach($paymentSystem);
        }

        foreach ($addPayments as $payment) {
            $this->addAutoPaymentSystem($fundraising, $payment);
        }

        $fundraising->save();
    }

    private function addAutoPaymentSystem(Fundraising $fundraising, string $payment_system): void
    {
        $organization = $fundraising->organization;
        $paymentSystem = $organization->paymentSystems()->where('payment_system', $payment_system)->first();

        if ($paymentSystem && $paymentSystem->active) {
            $stripe = new Stripe($paymentSystem->credentials['secret_key']);
            $stripeProduct = $stripe->createProduct($fundraising->title);
            $fundraising->paymentSystems()->attach($paymentSystem, [
                'product_id' => $stripeProduct->id,
            ]);
            $fundraising->save();
        }
    }
}
