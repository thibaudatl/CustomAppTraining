<?php

declare(strict_types=1);

namespace App\Query;

use App\PimApi\Model\Product;
use App\PimApi\PimCatalogApiClient;

/**
 * @phpstan-import-type RawMappedProduct from PimCatalogApiClient
 */
final class FetchMappedProductsQuery
{
    public function __construct(private readonly PimCatalogApiClient $catalogApiClient)
    {
    }

    /**
     * @return array<Product>
     */
    public function fetch(string $catalogId): array
    {
        /** @var array<RawMappedProduct> $rawMappedProducts */
        $rawMappedProducts = $this->catalogApiClient->getMappedProducts($catalogId);

        $products = [];
        foreach ($rawMappedProducts as $rawMappedProduct) {
            if(!isset($rawMappedProduct['name']))
                continue;
            $label = '' !== $rawMappedProduct['name']
                ? $rawMappedProduct['name']
                : $rawMappedProduct['uuid'];

            $description = $rawMappedProduct['body_html'];
            $products[] = new Product($rawMappedProduct['uuid'], $label, $description, $rawMappedProduct);
        }
        return $products;
    }

    private function getMyValue($attribute, $locale = null, $channel = null){
        foreach($attribute as $datapoint){
            if($datapoint["locale"] == $locale && $datapoint["channel"] == $channel){
                return $datapoint["data"];
            }
        }
        return null;
    }
}
