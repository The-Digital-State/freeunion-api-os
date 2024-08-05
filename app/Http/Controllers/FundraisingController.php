<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\FundraisingResource;
use App\Http\Response;
use App\Models\Fundraising;
use App\Models\Organization;
use App\Models\PaymentTransaction;
use App\Services\Payments\Stripe;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class FundraisingController extends Controller
{
    public function index(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->fundraisings()->active();

        $limit = (int) $request->get('limit', 0);
        $result = $limit > 0 ? $query->paginate($limit) : $query->get();

        return FundraisingResource::collection($result);
    }

    public function subscriptions(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->subscriptions()->active();

        $limit = (int) $request->get('limit', 0);
        $result = $limit > 0 ? $query->paginate($limit) : $query->get();

        return FundraisingResource::collection($result);
    }

    public function all(Request $request, Organization $organization): JsonResource
    {
        $query = $organization->fundraisingsAndSubscriptions()->active();

        $limit = (int) $request->get('limit', 0);
        $result = $limit > 0 ? $query->paginate($limit) : $query->get();

        return FundraisingResource::collection($result);
    }

    public function link(Request $request, Organization $organization, Fundraising $fundraising): JsonResponse
    {
        if ($fundraising->organization_id !== $organization->id) {
            throw new ModelNotFoundException();
        }

        $paymentSystem = $fundraising->paymentSystems()->first();

        if (! $paymentSystem) {
            return Response::error([]);
        }

        if (! $paymentSystem->active) {
            return Response::error([]);
        }

        if (! $paymentSystem->credentials['secret_key']) {
            return Response::error([]);
        }

        $routes = [
            'success_url' => $request->success_url,
            'cancel_url' => $request->cancel_url,
        ];

        $stripe = new Stripe($paymentSystem->credentials['secret_key']);
        $checkout = $stripe->getPaymentUrl(
            $paymentSystem->getRelationValue('pivot')->product_id,
            $request->summ * 100,
            $fundraising->currency ? Str::lower($fundraising->currency) : 'eur',
            $routes,
            $fundraising->is_subscription
        );
        $fundraising->transactions()->create([
            'transaction_id' => $checkout->id,
            'summ' => $request->summ,
            'payed' => false,
        ]);

        return Response::success(['url' => $checkout->url]);
    }

    public function stripeWebhook(Request $request, Organization $organization): JsonResponse
    {
        $paymentSystem = $organization->paymentSystems()->first();

        if (! $paymentSystem) {
            return Response::success();
        }

        $stripe = new Stripe($paymentSystem->credentials['secret_key']);
        $result = $stripe->getWebhook($paymentSystem->credentials, $request);

        $stripeTransaction = $result['payment'];
        $payed = $stripeTransaction->object->payment_status === 'paid';

        if ($payed) {
            $transaction = PaymentTransaction::where('transaction_id', $stripeTransaction->object->id)->first();

            if (! $transaction) {
                return Response::success();
            }

            $transaction->payed = true;
            $transaction->save();

            return Response::success();
        }

        return Response::success();
    }
}
