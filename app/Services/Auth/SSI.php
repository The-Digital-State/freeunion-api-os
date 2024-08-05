<?php

declare(strict_types=1);

namespace App\Services\Auth;

use GuzzleHttp\Client;
use Throwable;

class SSI
{
    private Client $client;

    /**
     * @param  string  $baseUrl
     */
    public function __construct(string $baseUrl)
    {
        $this->client = new Client([
            'base_uri' => $baseUrl,
        ]);
    }

    /**
     * @param  mixed  $request
     * @return object|false
     */
    public function auth(mixed $request): object|bool
    {
        $result = $this->request('POST', '/regov-auth/response/provide', $request);

        /** @phpstan-ignore-next-line */
        if ($result === false || ! $result->valid || ! $result->trusted || ! isset($result->instance->credentialSubject->did)) {
            return false;
        }

        return $result->instance;
    }

    /**
     * @param  mixed  $request
     * @return array|false
     */
    public function membership(mixed $request): array|bool
    {
        $result = $this->request('POST', '/regov-groups/membership/isOwner', $request);

        if ($result === false) {
            return false;
        }

        /** @phpstan-ignore-next-line */
        return [$result->group, $result->isOwner];
    }

    /**
     * @param  mixed  $request
     * @return object|array|false
     */
    public function trusted(mixed $request): object|array|bool
    {
        $result = $this->request('GET', '/regov/vcs/trusted', $request);

        if ($result === false) {
            return false;
        }

        return $result;
    }

    /**
     * @param  string  $path
     * @param  mixed  $json
     * @return object|array|false
     */
    private function request(string $method, string $path, mixed $json): object|array|bool
    {
        try {
            $response = $this->client->request($method, $path, compact('json'));
        } catch (Throwable) {
            return false;
        }

        if ($response->getStatusCode() !== 200) {
            return false;
        }

        try {
            $result = json_decode($response->getBody()->getContents(), false, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return false;
        }

        return $result;
    }
}
