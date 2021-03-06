<?php

namespace Akeneo\Pim\Enrichment\Bundle\Storage\ElasticsearchAndSql\ProductAndProductModel;

use Akeneo\Pim\Enrichment\Component\Product\Model\ProductModelInterface;
use Akeneo\Pim\Enrichment\Component\Product\Query\GetProductModelIdentifiersWithRemovedAttributeInterface;
use Akeneo\Tool\Bundle\ElasticsearchBundle\Client;

final class GetProductModelIdentifiersWithRemovedAttribute implements GetProductModelIdentifiersWithRemovedAttributeInterface
{
    /** @var Client */
    private $elasticsearchClient;

    public function __construct(
        Client $elasticsearchClient
    ) {
        $this->elasticsearchClient = $elasticsearchClient;
    }

    public function nextBatch(array $attributesCodes, int $batchSize): iterable
    {
        $body = [
            'size' => $batchSize,
            '_source' => [
                'identifier',
            ],
            'query' => [
                'constant_score' => [
                    'filter' => [
                        'bool' => [
                            'filter' => [
                                [
                                    'term' => [
                                        'document_type' => ProductModelInterface::class,
                                    ],
                                ],
                                [
                                    'terms' => [
                                        'attributes_for_this_level' => $attributesCodes,
                                    ],
                                ],
                            ],
                            'should' => array_map(function (string $attributeCode) {
                                return [
                                    'exists' => ['field' => sprintf('values.%s-*', $attributeCode)],
                                ];
                            }, $attributesCodes),
                            'minimum_should_match' => 1,
                        ],
                    ],
                ],
            ],
            'sort' => [
                'identifier' => 'asc',
            ],
        ];

        $rows = $this->elasticsearchClient->search($body);

        while (!empty($rows['hits']['hits'])) {
            $identifiers = array_map(function (array $product) {
                return $product['_source']['identifier'];
            }, $rows['hits']['hits']);
            yield $identifiers;
            $body['search_after'] = end($rows['hits']['hits'])['sort'];
            $rows = $this->elasticsearchClient->search($body);
        }
    }
}
