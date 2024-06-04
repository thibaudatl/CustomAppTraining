<?php

declare(strict_types=1);

namespace App\Controller;

use App\PimApi\Exception\PimApiException;
use App\PimApi\Model\Catalog;
use App\PimApi\Model\Product;
use App\PimApi\PimCatalogApiClient;
use App\Query\FetchMappedProductsQuery;
use App\Query\FetchProductsQuery;
use App\Query\GuessCurrentLocaleQuery;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment as TwigEnvironment;
use Akeneo\Pim\ApiClient\Api\ProductApi;
use App\Query\PatchProductsQuery;

final class TruncateAction extends AbstractController
{
    public function __construct(
        private readonly TwigEnvironment $twig,
        private readonly PimCatalogApiClient $catalogApiClient,
        private readonly GuessCurrentLocaleQuery $guessCurrentLocaleQuery,
        private readonly FetchProductsQuery $fetchProductsQuery,
        private readonly FetchMappedProductsQuery $fetchMappedProductsQuery,
        private readonly PatchProductsQuery $patchProductQuery,
        private readonly string $akeneoClientId
) {
    }

    #[Route('/catalogs/{catalogId}/truncate', name: 'truncate', methods: ['GET'])]
    public function __invoke(Request $request, string $catalogId): Response
    {
        $catalog = $this->getCatalogBy($catalogId);
        $products = $this->getProductsFrom($catalog);
        $myfile = fopen("newfile.txt", "w+") or die("Unable to open file!");

        $productsPayload = [];
        $currentLineTemplate=[];
        $i = 0;
        foreach($products as $p){

            if($p->description !== null) {
                $description = $this->truncateProductAttribute($p->description);
            }else{
                continue;
            }
            $currentLineTemplate[] =
                [
                    "identifier" => $p->attributes["sku"],
//                    "uuid" => $p->uuid,
                    "values" => [
                        "description" => [[
                            "data" => $description,
                            "locale" => "en_US",
                            "scope" => "ecommerce"
                        ]]
                    ]
                ];

            $i++;
        }

        $apiResponse = $this->patchProductQuery->patch($currentLineTemplate);

        $txt = json_encode($currentLineTemplate);

        fwrite($myfile, $txt);
        fclose($myfile);

        return $this->redirectToRoute('catalog', array("catalogId"=> $catalogId));

    }

    private function getCatalogBy(string $catalogId): Catalog
    {
        try {
            $catalog = $this->catalogApiClient->getCatalog($catalogId);
        } catch (PimApiException) {
            throw new NotFoundHttpException();
        }

        return $catalog;
    }

    private function truncateProductAttribute(string $string, $number = 37): string
    {
        return  substr($string, 0, $number) . '...' ;
    }

    private function postTruncatedAttribute(string $catalogId): Catalog
    {
        try {
            $catalog = $this->catalogApiClient->getCatalog($catalogId);
        } catch (PimApiException) {
            throw new NotFoundHttpException();
        }

        return $catalog;
    }

    /**
     * @return array<Product>
     */
    private function getProductsFrom(Catalog $catalog): array
    {
        if (!$catalog->enabled) {
            return [];
        }

        if (Catalog::ATTRIBUTE_MAPPING_NAME === $catalog->name) {
            return $this->fetchMappedProductsQuery->fetch($catalog->id);
        }

        $locale = $this->guessCurrentLocaleQuery->guess();

        return $this->fetchProductsQuery->fetch($locale, $catalog->id);
    }
}
