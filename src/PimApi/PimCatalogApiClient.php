<?php

declare(strict_types=1);

namespace App\PimApi;

use App\PimApi\Model\Catalog;
use App\Storage\AccessTokenStorageInterface;
use App\Storage\PimURLStorageInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class PimCatalogApiClient
{
    public function __construct(
        private HttpClientInterface $client,
        private readonly PimURLStorageInterface $pimURLStorage,
        AccessTokenStorageInterface $accessTokenStorage,
    ) {
        $this->client = $this->client->withOptions([
            'headers' => [
                'Content-type' => 'application/json',
                'Authorization' => 'Bearer '.$accessTokenStorage->getAccessToken(),
            ],
        ]);
    }

    public function getCatalog(string $catalogId): Catalog
    {
        $pimUrl = $this->pimURLStorage->getPimURL();

        $catalogEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId";

        $response = $this->client->request('GET', $catalogEndpointUrl)->toArray();

        return new Catalog(
            $response['id'],
            $response['name'],
            $response['enabled'],
        );
    }

    /**
     * @return array<string>
     */
    public function getProductIdentifiers(string $catalogId, int $limit = 100): array
    {
        $pimUrl = $this->pimURLStorage->getPimURL();

        $catalogIdentifierEndpointUrl = "$pimUrl/api/rest/v1/catalogs/$catalogId/product-identifiers";

        $response = $this->client->request('GET', $catalogIdentifierEndpointUrl, [
            'query' => ['limit' => $limit],
        ])->toArray();

        return $response['_embedded']['items'];
    }
}
