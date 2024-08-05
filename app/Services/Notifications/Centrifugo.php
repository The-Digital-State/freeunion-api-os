<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use JsonException;

class Centrifugo
{
    public static function publish(string $channel, array $data): array
    {
        return self::send('publish', compact('channel', 'data'));
    }

    public static function broadcast(array $channels, array $data): array
    {
        return self::send('broadcast', compact('channels', 'data'));
    }

    public static function channels(string $pattern = ''): array
    {
        return self::send('channels', compact('pattern'));
    }

    public static function generateConnectionToken(string $userId = '', int $exp = 0): string
    {
        $header = ['typ' => 'JWT', 'alg' => 'HS256'];
        $payload = ['sub' => $userId];

        if ($exp) {
            $payload['exp'] = $exp;
        }

        $segments = [];

        try {
            $segments[] = self::urlsafeB64Encode(json_encode($header, JSON_THROW_ON_ERROR));
            $segments[] = self::urlsafeB64Encode(json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
        }

        $signingInput = implode('.', $segments);
        $signature = self::sign($signingInput, self::getSecret());
        $segments[] = self::urlsafeB64Encode($signature);

        return implode('.', $segments);
    }

    public static function send(string $method, array $params = []): array
    {
        $url = self::prepareUrl();

        try {
            /** @var Collection<string, mixed> */
            $config = collect([
                'headers' => [
                    'Content-type' => 'application/json',
                    'Authorization' => 'apikey '.config('app.centrifugo.api_key'),
                ],
                'body' => json_encode(compact('method', 'params'), JSON_THROW_ON_ERROR),
                'http_errors' => true,
            ]);
        } catch (JsonException $error) {
            return [
                'method' => $method,
                'error' => $error->getMessage(),
                'body' => $params,
            ];
        }

        if (parse_url($url, PHP_URL_SCHEME) === 'https') {
            $config->put('verify', config('app.centrifugo.verify'));
            $sslKey = config('app.centrifugo.ssl_key');

            if ($sslKey) {
                $config->put('ssl_key', $sslKey);
            }
        }

        try {
            $response = (new Client())->post($url, $config->toArray());

            $result = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (GuzzleException|JsonException $error) {
            $result = [
                'method' => $method,
                'error' => $error->getMessage(),
                'body' => $params,
            ];
        }

        return $result;
    }

    protected static function getSecret(): string
    {
        return config('app.centrifugo.secret', '');
    }

    protected static function prepareUrl(): string
    {
        $apiPath = '/api';

        $address = rtrim(config('app.centrifugo.url'), '/');

        if (substr_compare($address, $apiPath, -strlen($apiPath)) !== 0) {
            $address .= $apiPath;
        }

        return $address;
    }

    private static function urlsafeB64Encode(string $input): string
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    private static function sign(string $msg, string $key): string
    {
        return hash_hmac('sha256', $msg, $key, true);
    }
}
