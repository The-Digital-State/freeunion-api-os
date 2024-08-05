<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PaymentSystem\StoreRequest;
use App\Http\Requests\Admin\PaymentSystem\UpdateRequest;
use App\Http\Resources\PaymentSystemResource;
use App\Http\Response;
use App\Models\Organization;
use App\Models\PaymentSystem;
use App\Policies\OrganizationPolicy;
use App\Services\Payments\Stripe;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentSystemController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->paymentSystems();

        $limit = (int) $request->get('limit', 0);

        if ($limit > 0) {
            return PaymentSystemResource::collection($query->paginate($limit));
        }

        return PaymentSystemResource::collection($query->get());
    }

    /**
     * @throws AuthorizationException
     */
    public function store(StoreRequest $request, Organization $organization): JsonResource|JsonResponse
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        /** @var PaymentSystem|null $paymentSystem */
        $paymentSystem = $organization->paymentSystems()->where('payment_system', $request['payment_system'])->first();

        if ($paymentSystem) {
            return $this->updatePaymentSystem($request, $organization, $paymentSystem);
        }

        $webhookResult = $this->getWebhook($request['credentials'], $organization);

        if (! $webhookResult['ok']) {
            return Response::error($webhookResult['message']);
        }

        $webhook = $webhookResult['webhook'];
        $paymentSystem = $organization->paymentSystems()->create($request->validated());

        $this->updatePaymentWebhook($paymentSystem, $webhook->id);

        return new PaymentSystemResource($paymentSystem);
    }

    /**
     * @throws AuthorizationException
     */
    public function show(Organization $organization, PaymentSystem $paymentSystem): PaymentSystemResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $this->checkInOrganization($organization, $paymentSystem);

        return new PaymentSystemResource($paymentSystem);
    }

    /**
     * @throws AuthorizationException
     */
    public function showByName(Organization $organization, string $system): PaymentSystemResource
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);
        /** @var PaymentSystem|null $paymentSystem */
        $paymentSystem = $organization->paymentSystems()->where('payment_system', $system)->first();

        if (! $paymentSystem) {
            throw new ModelNotFoundException();
        }

        $this->checkInOrganization($organization, $paymentSystem);

        return new PaymentSystemResource($paymentSystem);
    }

    /**
     * @throws AuthorizationException
     */
    public function update(
        UpdateRequest $request,
        Organization $organization,
        PaymentSystem $paymentSystem,
    ): JsonResource|JsonResponse {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        $this->checkInOrganization($organization, $paymentSystem);

        return $this->updatePaymentSystem($request, $organization, $paymentSystem);
    }

    /**
     * @throws AuthorizationException
     */
    public function updateByName(
        UpdateRequest $request,
        Organization $organization,
        string $system,
    ): JsonResource|JsonResponse {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);

        /** @var PaymentSystem $paymentSystem */
        $paymentSystem = $organization->paymentSystems()->where('payment_system', $system)->firstOrFail();

        $this->checkInOrganization($organization, $paymentSystem);

        return $this->updatePaymentSystem($request, $organization, $paymentSystem);
    }

    /**
     * @throws AuthorizationException
     */
    public function destroy(Organization $organization, PaymentSystem $paymentSystem): JsonResponse
    {
        $this->authorize(OrganizationPolicy::FINANCE_MANAGE, $organization);
        $this->checkInOrganization($organization, $paymentSystem);

        $paymentSystem->delete();

        return Response::noContent();
    }

    private function checkInOrganization(Organization $organization, PaymentSystem $paymentSystem): void
    {
        if ($organization->id !== $paymentSystem->organization_id) {
            throw new ModelNotFoundException();
        }
    }

    private function getWebhook(array $credentials, Organization $organization): array
    {
        $route = route('organizations.stripe.webhook', [
            'organization' => $organization,
        ]);

        if ($credentials['secret_key'] === null) {
            return [
                'ok' => false,
                'message' => 'Wrong credentials',
            ];
        }

        return (new Stripe($credentials['secret_key']))
            ->checkWebHook($route, $credentials['webhook_secret'] ?? false);
    }

    // TODO: delete?
//    private function checkWebhook(PaymentSystem $paymentSystem): void
//    {
//        $credentials = $paymentSystem->credentials;
//
//        if (!isset($credentials['webhook_secret'])) {
//            $stripe = new Stripe($credentials['secret_key']);
//            $webhook = $stripe->createWebHook(route('organizations.stripe.webhook', [
//                'organization' => $paymentSystem->organization,
//            ]));
//            $credentials['webhook_secret'] = $webhook->id;
//            $paymentSystem->credentials = $credentials;
//            $paymentSystem->save();
//        }
//    }

    private function updatePaymentSystem(
        Request $request,
        Organization $organization,
        PaymentSystem $paymentSystem,
    ): JsonResource|JsonResponse {
        if (! $request['credentials']) {
            $paymentSystem->update($request->all());

            return new PaymentSystemResource($paymentSystem);
        }

        $credentials = $request['credentials'];
        $credentials['webhook_secret'] = $paymentSystem->credentials['webhook_secret'] ?? false;
        $webhookResult = $this->getWebhook($credentials, $organization);

        if (! $webhookResult['ok']) {
            return Response::error($webhookResult['message']);
        }

        $webhook = $webhookResult['webhook'];
        $paymentSystem->update($request->all());
        $this->updatePaymentWebhook($paymentSystem, $webhook->id);

        return new PaymentSystemResource($paymentSystem);
    }

    private function updatePaymentWebhook(PaymentSystem $paymentSystem, string $webhook_secret): void
    {
        $credentials = $paymentSystem->credentials;
        $credentials['webhook_secret'] = $webhook_secret;
        $paymentSystem->credentials = $credentials;
        $paymentSystem->save();
    }
}
