<?php

declare(strict_types=1);

namespace App\Components\Shopify;

use App\Components\Shopify\DTO\Cost;
use Exception;
use Illuminate\Support\Facades\Redis;
use Shopify\Clients\Graphql;
use Shopify\Clients\HttpResponse;
use Shopify\Exception\HttpRequestException;
use Shopify\Exception\MissingArgumentException;

class GraphqlClient extends Graphql
{
    private const TIMEOUT_PREFIX = 'shopify_graphql_timeout_';
    private const MAX_DELAY = 1000;
    private const DEFAULT_TRIES = 3;

    private string $store;

    public function __construct(string $domain, string $token)
    {
        parent::__construct($domain, $token);
        $this->store = $domain;
    }

    /**
     * Sends a GraphQL query to this client's domain.
     *
     * @param string|array $data Query to be posted to endpoint
     * @param array $query Parameters on a query to be added to the URL
     * @param array $extraHeaders Any extra headers to send along with the request
     * @param int|null $tries How many times to attempt the request
     *
     * @return HttpResponse
     * @throws HttpRequestException
     * @throws MissingArgumentException
     * @throws Exception
     */
    public function query(
        $data,
        array $query = [],
        array $extraHeaders = [],
        ?int $tries = self::DEFAULT_TRIES
    ): HttpResponse {
        while (Redis::get($this->timeoutKey()) > 0) {
            usleep(1000 * 100);
        }

        $response = parent::query($data, $query, $extraHeaders, $tries);
        $statusCode = $response->getStatusCode();

        if ($statusCode >= 500 && $statusCode < 600) {
            if ($tries) {
                return $this->query($data, $query, $extraHeaders, $tries - 1);
            } else {
                throw new Exception($statusCode . ' Shopify Request Error: ' . print_r($data, 1));
            }
        }

        $body = $response->getDecodedBody();
        $errors = $body['errors'] ?? null;
        $cost = new Cost($body['extensions']['cost'] ?? []);

        $msDelay = $this->calculateDelay($cost);

        if ($msDelay === null) {
            throw new Exception('Shopify Response Error: ' . print_r($body, 1));
        } else {
            Redis::set($this->timeoutKey(), '1', 'PX', $msDelay ?: self::MAX_DELAY);
        }

        if ($errors && $tries) {
            return $this->query($data, $query, $extraHeaders, $tries - 1);
        }

        return $response;
    }

    private function calculateDelay(Cost $cost): ?int
    {
        $requested = $cost->actualQueryCost ?? $cost->requestedQueryCost;
        $restoreAmount = max(0, $requested - $cost->throttleStatus->currentlyAvailable);

        return $cost->throttleStatus->restoreRate
            ? (int) (ceil($restoreAmount / $cost->throttleStatus->restoreRate) * 1000)
            : null;
    }

    private function timeoutKey(): string
    {
        return self::TIMEOUT_PREFIX . $this->store;
    }
}
