<?php

declare(strict_types=1);

namespace App\Modules\Incognio;

use CurlHandle;
use Illuminate\Support\Facades\Cache;
use JsonException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Incognio
{
    public const STATUS_JOINED = 0;

    public const STATUS_DISCONNECTED = 1;

    public const STATUS_NOT_FOUND = 9;

    private string $apiPath;

    private string $apiKey;

    private string $botNickname;

    private CurlHandle $curlHandler;

    private bool $curlInited = false;

    public function __construct(string $apiPath, string $apiKey)
    {
        $this->apiPath = $apiPath;
        $this->apiKey = $apiKey;
    }

    public function setBot(string $botNickname): self
    {
        $this->botNickname = $botNickname;

        return $this;
    }

    public function generateInvite(string $memberName, int $validityMinutes = 10): string
    {
        $key = "incognio:$this->botNickname:".md5($memberName);
        $link = Cache::get($key, '');

        if ($link === '') {
            try {
                $result = $this->request('POST', "/chat/$this->botNickname/generate-invite", [
                    'member_name' => $memberName,
                    'validity_minutes' => $validityMinutes,
                ]);

                $link = $result['invite'] ?? '';

                if ($link !== '') {
                    Cache::put($key, $link, $validityMinutes * 60);
                }
            } catch (Throwable) {
            }
        }

        if ($link === '') {
            $link = "https://t.me/$this->botNickname";
        }

        return $link;
    }

    public function getMember(string $memberName): int
    {
        $result = $this->request('GET', "/chat/$this->botNickname/member", [
            'member_name' => $memberName,
        ]);

        return match ($result['status'] ?? '') {
            'JOINED' => self::STATUS_JOINED,
            'DISCONNECTED' => self::STATUS_DISCONNECTED,
            default => self::STATUS_NOT_FOUND,
        };
    }

    public function kickMember(string $memberName): void
    {
        $this->request('PUT', "/chat/$this->botNickname/kick", [
            'member_name' => $memberName,
        ]);
    }

    public function request(string $method, string $path, array $params = []): ?array
    {
        if (! $this->curlInited) {
            $this->curlInited = true;
            $this->curlHandler = curl_init();
            curl_setopt($this->curlHandler, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                "Authorization: Bearer $this->apiKey",
            ]);
            curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, true);
        }

        $method = strtoupper($method);
        curl_setopt($this->curlHandler, CURLOPT_CUSTOMREQUEST, $method);
        $url = "$this->apiPath$path?".http_build_query($params);
        curl_setopt($this->curlHandler, CURLOPT_URL, $url);

        try {
            /** @var string $output */
            $output = curl_exec($this->curlHandler);
            $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new HttpException(500, $exception->getMessage());
        }

        $httpCode = curl_getinfo($this->curlHandler, CURLINFO_HTTP_CODE);

        if ($httpCode !== 200) {
            throw new HttpException($httpCode);
        }

        return $result;
    }
}
