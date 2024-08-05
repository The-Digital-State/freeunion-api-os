<?php

declare(strict_types=1);

namespace App\Services\Payments;

use Illuminate\Http\Request;
use JsonException;
use RuntimeException;
use Stripe\Checkout\Session;
use Stripe\Event;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\AuthenticationException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\RateLimitException;
use Stripe\Price;
use Stripe\Product;
use Stripe\StripeClient;
use Stripe\WebhookEndpoint;
use UnexpectedValueException;

class Stripe
{
    private string $secretKey;

    private StripeClient $stripeClient;

    public function __construct(string $secret_key)
    {
        $this->secretKey = $secret_key;
        $this->stripeClient = new StripeClient($this->secretKey);
    }

    /**
     * @throws ApiErrorException
     */
    public function createProduct(string $name): Product
    {
        return $this->stripeClient->products->create(['name' => $name]);
    }

    /**
     * @throws ApiErrorException
     */
    public function createPrice(string $productId, int $sum, string $currency): Price
    {
        return $this->stripeClient->prices->create(
            [
                'product' => $productId,
                'unit_amount' => $sum,
                'currency' => $currency,
            ]
        );
    }

    /**
     * @throws ApiErrorException
     */
    public function createRecurringPrice(string $productId, int $sum, string $currency): Price
    {
        return $this->stripeClient->prices->create(
            [
                'product' => $productId,
                'unit_amount' => $sum,
                'currency' => $currency,
                'recurring' => ['interval' => 'month'],
            ]
        );
    }

    /**
     * @param  string  $productId
     * @param  int  $sum
     * @param  string  $currency
     * @param  array<string>  $routes
     * @param  bool  $recurring
     * @return Session
     *
     * @throws ApiErrorException
     */
    public function getPaymentUrl(
        string $productId,
        int $sum,
        string $currency,
        array $routes,
        bool $recurring = false,
    ): Session {
        $price = $recurring ? $this->createRecurringPrice($productId, $sum, $currency)
            : $this->createPrice($productId, $sum, $currency);

        return $this->stripeClient->checkout->sessions->create([
            'success_url' => $routes['success_url'],
            'cancel_url' => $routes['cancel_url'],
            'line_items' => [
                [
                    'price' => $price->id,
                    'quantity' => 1,
                ],
            ],
            'mode' => $recurring ? 'subscription' : 'payment',
        ]);
    }

    /**
     * @param  string  $route
     * @return array<string, bool|string|WebhookEndpoint>
     */
    public function createWebHook(string $route): array
    {
        try {
            $webhook = $this->stripeClient->webhookEndpoints->create([
                'url' => $route,
                'enabled_events' => [
                    'checkout.session.async_payment_failed',
                    'checkout.session.async_payment_succeeded',
                    'checkout.session.completed',
                    'checkout.session.expired',
                ],
            ]);
        } catch (RateLimitException|InvalidRequestException|AuthenticationException|ApiErrorException $error) {
            return ['ok' => false, 'message' => $error->getMessage()];
        }

        return ['ok' => true, 'webhook' => $webhook];
    }

    /**
     * @param  string  $route
     * @param  string|false  $webhook_secret
     * @return array<string, bool|string|WebhookEndpoint>
     */
    public function checkWebHook(string $route, string|false $webhook_secret = false): array
    {
        if (! $webhook_secret) {
            return $this->createWebHook($route);
        }

        try {
            $webhook = $this->stripeClient->webhookEndpoints->retrieve(
                $webhook_secret,
                []
            );
        } catch (InvalidRequestException $error) {
            if ($error->getHttpStatus() === 404) {
                return $this->createWebHook($route);
            }

            return ['ok' => false, 'message' => $error->getMessage()];
        } catch (AuthenticationException|ApiConnectionException|ApiErrorException $error) {
            return ['ok' => false, 'message' => $error->getMessage()];
        }

        return ['ok' => true, 'webhook' => $webhook];
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function getWebhook(array $webhook_secret, Request $request): array
    {
        \Stripe\Stripe::setApiKey($this->secretKey);

        /** @var string $payload */
        $payload = $request->getContent();

        try {
            $event = Event::constructFrom(
                json_decode($payload, true, 512, JSON_THROW_ON_ERROR)
            );
        } catch (UnexpectedValueException|JsonException) {
            return ['error' => 'Webhook error while parsing basic request.', 'code' => 400];
        }

        switch ($event->type) {
            case 'checkout.session.completed':
            case 'checkout.session.expired':
            case 'checkout.session.async_payment_faile':
                $payment = $event->data;
                $payed = false;

                break;
            case 'checkout.session.async_payment_succeeded':
                $payment = $event->data;
                $payed = true;

                break;
            default:
                throw new RuntimeException("Received unknown event type: $event->type");
        }

        return compact('payment', 'payed');
    }

    /**
     * @return array<string, string>
     */
    public static function getRequiredCredentials(): array
    {
        return [
            'public_key' => 'Publishable key',
            'secret_key' => 'Publishable key',
        ];
    }
}
