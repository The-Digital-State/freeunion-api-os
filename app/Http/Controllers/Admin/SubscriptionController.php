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
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Symfony\Component\HttpFoundation\Response as ResponseCode;
use Throwable;

class SubscriptionController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->subscriptions();

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return FundraisingResource::collection($query->paginate($limit));
        }

        return FundraisingResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): FundraisingResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        /** @var Fundraising $subscription */
        $subscription = $organization->subscriptions()->create($request->validated());
        $subscription->is_subscription = true;
        $subscription->save();

        foreach ($request['auto_payments'] as $paymentSystem) {
            $this->addAutoPaymentSystem($subscription, $paymentSystem);
        }

        return new FundraisingResource($subscription);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, Fundraising $subscription): FundraisingResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $this->checkInOrganization($organization, $subscription);

        return new FundraisingResource($subscription);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        Fundraising $subscription,
    ): FundraisingResource {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $this->checkInOrganization($organization, $subscription);

        $subscription->update($request->validated());
        $this->updateAutoPayments($subscription, $request['auto_payments']);

        return new FundraisingResource($subscription);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, Fundraising $subscription): JsonResponse
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);
        $this->checkInOrganization($organization, $subscription);

        $subscription->delete();

        return Response::noContent();
    }

    /**
     * @throws AuthorizationException
     */
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

        if (! $storage->exists("subscriptions/$organization->id/$folder1/$folder2/$fileName")) {
            $fileWasUploaded = $storage->put(
                "subscriptions/$organization->id/$folder1/$folder2/$fileName",
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
                'url' => $storage->url("subscriptions/$organization->id/$folder1/$folder2/$fileName"),
            ]
        );
    }

    private function checkInOrganization(Organization $organization, Fundraising $subscription): void
    {
        if ($organization->id !== $subscription->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function updateAutoPayments(Fundraising $fundraising, array $payments): void
    {
        $organization = $fundraising->organization;
        $fundraisingPayments = $fundraising->paymentSystems()->pluck('payment_system')->toArray();
        $deletePayments = array_diff($fundraisingPayments, $payments);
        $addPayments = array_diff($payments, $fundraisingPayments);

        foreach ($deletePayments as $payment_system) {
            $paymentSystem = $organization->paymentSystems()->where('payment_system', $payment_system)->first();
            $fundraising->paymentSystems()->detach($paymentSystem);
        }

        foreach ($addPayments as $payment_system) {
            $this->addAutoPaymentSystem($fundraising, $payment_system);
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
