<?php
namespace JClaveau\ElasticSearch;

use JClaveau\VisibilityViolator\VisibilityViolator;


/**
 */
class ResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @unit
     */
    public function test_jsonSerialize()
    {
        $result = new ElasticSearchResult(['fake_result']);

        $this->assertEquals(
            '["fake_result"]',
            json_encode($result)
        );
    }

    /**
     * @unit
     */
    public function test_getAsSqlResult()
    {
        $result = (new ElasticSearchResult(
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_BigAggregation.php'
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            $result,
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_As_Sql_BigAggregation.php'
        );
    }

    /**
     * @unit
     */
    public function test_getAsSqlResult_NestedAggregations()
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

        $this->assertEquals(
            $result,
            [
                'type:request-id:529-deals.deal_id:DEAL1' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL1',
                    'total' => 12347,
                ],
                'type:request-id:529-deals.deal_id:DEAL2' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL2',
                    'total' => 12346,
                ],
                'type:request-id:529-deals.deal_id:DEAL3' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL3',
                    'total' => 12347,
                ],
                'type:request-id:529-deals.deal_id:DEAL4' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL4',
                    'total' => 9974,
                ],
            ]
        );
    }

    /**
     * @unit
     */
    public function test_getAsSqlResult_RootNestedAggregations()
    {
        $result = (new ElasticSearchResult(
            [
                'hits' => [
                    'total' => 12348,
                    'max_score' => 1,
                    'hits' => []
                ],
                'aggregations' => [
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
            ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            $result,
            [
                'deals.deal_id:DEAL1' => [
                    'deals.deal_id' => 'DEAL1',
                    'total' => 12348,
                ],
                'deals.deal_id:DEAL2' => [
                    'deals.deal_id' => 'DEAL2',
                    'total' => 12346,
                ],
                'deals.deal_id:DEAL3' => [
                    'deals.deal_id' => 'DEAL3',
                    'total' => 12347,
                ],
                'deals.deal_id:DEAL4' => [
                    'deals.deal_id' => 'DEAL4',
                    'total' => 9974,
                ],
            ]
        );
    }

    /**/
}
