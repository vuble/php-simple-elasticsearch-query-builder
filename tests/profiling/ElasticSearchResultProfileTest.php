<?php

namespace JClaveau\ElasticSearch;

class ElasticSearchResultProfileTest extends \AbstractTest
{
    use \JClaveau\PHPUnit\Framework\UsageConstraintTrait;

    /**
     * @profile
     * @coversNothing
     */
    public function test_profile_getAsSqlResult_big_aggregation()
    {
        $result = (new ElasticSearchResult(
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_BigAggregation.php'
        ))
        ->getAsSqlResult()
        ;

        // var_dump($this->getExecutionTime());
        $this->assertExecutionTimeBelow(0.2 * 5);
    }

    /**
     * @profile
     * @coversNothing
     */
    public function test_profile_getAsSqlResult_nested()
    {
        $result = (new ElasticSearchResult(
            [
                'hits' => [
                    'total' => 12348,
                    'max_score' => 1,
                    'hits' => []
                ],
                'aggregations' => [
                    'group_by_type' => [
                        'buckets' => [
                            0 => [
                                'key' => 'request',
                                'doc_count' => 12349,
                                'group_by_id' => [
                                    'buckets' => [
                                        0 => [
                                            'key' => 529,
                                            'doc_count' => 12347,
                                            'nested_deals' => [
                                                'doc_count' => 47288,
                                                'filter_deal_id' => [
                                                    'doc_count' => 47288,
                                                    'group_by_deals.deal_id' => [
                                                        'buckets' => [
                                                            0 => [
                                                                'key' => 'DEAL1',
                                                                'doc_count' => 12349,
                                                            ],
                                                            1 => [
                                                                'key' => 'DEAL2',
                                                                'doc_count' => 12346,
                                                            ],
                                                            2 => [
                                                                'key' => 'DEAL3',
                                                                'doc_count' => 12347,
                                                            ],
                                                            3 => [
                                                                'key' => 'DEAL4',
                                                                'doc_count' => 9974,
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ))
        ->getAsSqlResult()
        ;

        // var_dump($this->getExecutionTime());
        $this->assertExecutionTimeBelow(0.008 * 5);
    }

}
