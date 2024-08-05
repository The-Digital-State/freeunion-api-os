<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Response;
use App\Models\ActivityScope;
use App\Models\EnterRequest;
use App\Models\HelpOffer;
use App\Models\InterestScope;
use App\Models\NewsTag;
use App\Models\OrganizationType;
use App\Models\Position;
use App\Models\Reaction;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use JsonException;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class DictionaryController extends Controller
{
    public function activityScopes(): JsonResponse
    {
        return new JsonResponse(ActivityScope::all());
    }

    /**
     * @throws JsonException
     */
    public function countries(): JsonResponse
    {
        // TODO: Use local list of countries

        if (Cache::has('countries')) {
            return new JsonResponse(Cache::get('countries', []));
        }

        $client = new Client();

        try {
            $response = $client->get('http://api.countrylayer.com/v2/all', [
                'query' => [
                    'access_key' => '8db60cc25f0bcf8629557bc6e18e8037',
                    'filters' => implode(';', ['alpha2Code', 'callingCodes']),
                ],
                'timeout' => 10,
            ]);
        } catch (GuzzleException $e) {
            return Response::error($e->getMessage(), ResponseCode::HTTP_FAILED_DEPENDENCY);
        }

        if ($response->getStatusCode() > 300) {
            return Response::error($response->getReasonPhrase(), ResponseCode::HTTP_FAILED_DEPENDENCY);
        }

        $countries = [];

        if ($response->getStatusCode() === 200) {
            foreach (json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR) as $item) {
                $countries[] = [
                    'id' => $item['alpha2Code'],
                    'name' => __("country.{$item['alpha2Code']}"),
                    'code' => $item['callingCodes'][0] ?? '',
                ];
            }
        }

        Cache::put('countries', $countries);

        return new JsonResponse($countries);
    }

    public function helpOffers(): JsonResponse
    {
        return new JsonResponse(HelpOffer::all());
    }

    public function interestScopes(): JsonResponse
    {
        return new JsonResponse(InterestScope::all());
    }

    public function organizationTypes(): JsonResponse
    {
        return new JsonResponse(OrganizationType::all());
    }

    public function positions(): JsonResponse
    {
        return new JsonResponse(Position::all());
    }

    public function reactions(): JsonResponse
    {
        return new JsonResponse(Reaction::REACTIONS);
    }

    public function requestStatuses(): JsonResponse
    {
        return new JsonResponse(EnterRequest::availableStatuses());
    }

    /**
     * @throws JsonException
     */
    public function searchPlace(Request $request): JsonResponse
    {
        $name = $request->get('name', '');

        if (mb_strlen($name) < 3) {
            return Response::error(
                __('validation.min.string', ['attribute' => __('validation.attributes.name'), 'min' => 3])
            );
        }

        $client = new Client();

        try {
            $response = $client->get('http://egr.gov.by/api/v2/egr/getShortInfoByRegName/'.rawurlencode($name), [
                'timeout' => 5,
            ]);
        } catch (RequestException $e) {
            return Response::error($e->getResponse()?->getReasonPhrase() ?? '', ResponseCode::HTTP_FAILED_DEPENDENCY);
        } catch (GuzzleException $e) {
            return Response::error($e->getMessage(), ResponseCode::HTTP_FAILED_DEPENDENCY);
        }

        if ($response->getStatusCode() > 300) {
            return Response::error($response->getReasonPhrase(), ResponseCode::HTTP_FAILED_DEPENDENCY);
        }

        $data = [];

        if ($response->getStatusCode() === 200) {
            foreach (json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR) as $item) {
                if (isset($item['nsi00219'], $item['vnaim']) && ((int) $item['nsi00219']['nksost']) === 1) {
                    $data[] = [
                        'name' => $item['vnaim'],
                        'short' => $item['vn'] ?? '',
                    ];
                }

                if (count($data) >= 20) {
                    break;
                }
            }
        }

        return new JsonResponse($data, $response->getStatusCode());
    }

    public function tags(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 10);

        if ($limit > 10) {
            $limit = 10;
        }

        $query = NewsTag::query()
            ->orderByDesc('count')
            ->orderByDesc('last_published_at')
            ->limit($limit);

        return new JsonResponse($query->get()->pluck('tag'));
    }
}
