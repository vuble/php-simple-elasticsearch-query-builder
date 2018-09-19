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
            [
                'hits' => [
                    'hits' => []
                ],
                'aggregations' => [
                    'group_by_id' => [
                        'buckets' => [
                            0 => [
                                'key' => 529,
                                'doc_count' => 12347,
                                'group_by_aid' => [
                                    'buckets' => [
                                        0 => [
                                            'key' => 1021374,
                                            'doc_count' => 15732,
                                            'group_by_type' => [
                                                'buckets' => [
                                                    0 => [
                                                        'key' => 'impression',
                                                        'doc_count' => 15732,
                                                        'group_by_cid' => [
                                                            'buckets' => [
                                                                0 => [
                                                                    'key' => 0,
                                                                    'doc_count' => 15732,
                                                                ],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ],
                                        1 => [
                                            'key' => 7020009,
                                            'doc_count' => 13032,
                                            'group_by_type' => [
                                                'buckets' => [
                                                    0 => [
                                                        'key' => 'impression',
                                                        'doc_count' => 13032,
                                                        'group_by_cid' => [
                                                            'buckets' => [
                                                                0 => [
                                                                        'key' => 1234,
                                                                        'doc_count' => 13032,
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
                ],
            ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            $result,
            [
                'id:529-aid:1021374-type:impression-cid:0' => [
                    'id' => 529,
                    'aid' => '1021374',
                    'type' => 'impression',
                    'cid' => '0',
                    'total' => 15732,
                ],
                'id:529-aid:7020009-type:impression-cid:1234' => [
                    'id' => 529,
                    'aid' => '7020009',
                    'type' => 'impression',
                    'cid' => '1234',
                    'total' => 13032,
                ],
            ]
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
    public function test_getAsSqlResult_NestedAggregations_missing()
    {
        $result = (new ElasticSearchResult(
            [
                'hits' => [
                    'total' => 454,
                    'max_score' => 0,
                    'hits' => [],
                ],
                'aggregations' => [
                    'group_by_A_id' => [
                        'doc_count_error_upper_bound' => 0,
                        'sum_other_doc_count' => 0,
                        'buckets' => [
                            [
                                'key' => 101,
                                'doc_count' => 446,
                                'nested_D' => [
                                    'doc_count' => 0,   // doc_count = 0 due to empty nested
                                    'group_by_D.id' => [
                                        'doc_count_error_upper_bound' => 0,
                                        'sum_other_doc_count' => 0,
                                        'buckets' => [],
                                    ],
                                ],
                            ],
                            [
                                'key' => 100,
                                'doc_count' => 8,
                                'nested_D' => [
                                    'doc_count' => 0,   // doc_count = 0 due to empty nested
                                    'group_by_D.id' => [
                                        'doc_count_error_upper_bound' => 0,
                                        'sum_other_doc_count' => 0,
                                        'buckets' => [],
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
            [
                'A_id:101' => [
                    'A_id' => 101,
                    'D.id' => NULL,
                    'total' => 446,
                ],
                'A_id:100' => [
                    'A_id' => 100,
                    'D.id' => NULL,
                    'total' => 8,
                ],
            ],
            $result
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

    /**
     * @unit
     */
    public function test_getHits()
    {
        $result = new ElasticSearchResult(
            [
                "took" => 1,
                "timed_out" => false,
                "hits" => [
                    "total" => 1,
                    "hits" => [
                        [
                            "_index" => "index1",
                            "_type" => "event",
                            "_score" => 1,
                            "_source" => [
                                "entry" => "data",
                                "entry2" => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            [
                "total" => 1,
                "hits" => [
                    [
                        "_index" => "index1",
                        "_type" => "event",
                        "_score" => 1,
                        "_source" => [
                            "entry" => "data",
                            "entry2" => 1
                        ]
                    ]
                ]
            ],
            $result->getHits()
        );
    }

    /**
     * @unit
     */
    public function test_getResults()
    {
        $result = new ElasticSearchResult(
            [
                "took" => 1,
                "timed_out" => false,
                "hits" => [
                    "total" => 1,
                    "hits" => [
                        [
                            "_index" => "index1",
                            "_type" => "event",
                            "_score" => 1,
                            "_source" => [
                                "entry" => "data",
                                "entry2" => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $this->assertEquals(
            [
                "took" => 1,
                "timed_out" => false,
                "hits" => [
                    "total" => 1,
                    "hits" => [
                        [
                            "_index" => "index1",
                            "_type" => "event",
                            "_score" => 1,
                            "_source" => [
                                "entry" => "data",
                                "entry2" => 1
                            ]
                        ]
                    ]
                ]
            ],
            $result->getResults()
        );
    }

    /**/
}
