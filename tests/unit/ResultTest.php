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
     */
    public function test_getAsSqlResult_basic()
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
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_renameField()
    {
        $result = (new ElasticSearchResult(
            [
                'hits' => [
                    'total' => 4,
                    'max_score' => 1,
                    'hits' => []
                ],
                'aggregations' => [
                    'group_by_type' => [
                        'buckets' => [
                            0 => [
                                'key' => 'request',
                                'doc_count' => 4,
                                'group_by_id' => [
                                    'buckets' => [
                                        0 => [
                                            'key' => 529,
                                            'doc_count' => 4,
                                            'nested_deals' => [
                                                'doc_count' => 10,
                                                'filter_deal_id' => [
                                                    'doc_count' => 10,
                                                    'group_by_deals.deal_id' => [
                                                        'buckets' => [
                                                            0 => [
                                                                'key' => 'DEAL1',
                                                                'doc_count' => 1,
                                                            ],
                                                            1 => [
                                                                'key' => 'DEAL2',
                                                                'doc_count' => 2,
                                                            ],
                                                            2 => [
                                                                'key' => 'DEAL3',
                                                                'doc_count' => 3,
                                                            ],
                                                            3 => [
                                                                'key' => 'DEAL4',
                                                                'doc_count' => 4,
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
            [
                'deals.deal_id' => 'deal_id',
            ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            [
                'type:request-id:529-deal_id:DEAL1' => [
                    'type' => 'request',
                    'id' => 529,
                    'deal_id' => 'DEAL1',
                    'total' => 1,
                ],
                'type:request-id:529-deal_id:DEAL2' => [
                    'type' => 'request',
                    'id' => 529,
                    'deal_id' => 'DEAL2',
                    'total' => 2,
                ],
                'type:request-id:529-deal_id:DEAL3' => [
                    'type' => 'request',
                    'id' => 529,
                    'deal_id' => 'DEAL3',
                    'total' => 3,
                ],
                'type:request-id:529-deal_id:DEAL4' => [
                    'type' => 'request',
                    'id' => 529,
                    'deal_id' => 'DEAL4',
                    'total' => 4,
                ],
            ],
            $result
        );
    }

    /**
     * @unit
     */
    public function test_getAsSqlResult_NestedAggregations_basic()
    {
        $result = (new ElasticSearchResult(
            [
                'hits' => [
                    'total' => 4,
                    'max_score' => 1,
                    'hits' => []
                ],
                'aggregations' => [
                    'group_by_type' => [
                        'buckets' => [
                            0 => [
                                'key' => 'request',
                                'doc_count' => 4,
                                'group_by_id' => [
                                    'buckets' => [
                                        0 => [
                                            'key' => 529,
                                            'doc_count' => 4,
                                            'nested_deals' => [
                                                'doc_count' => 10,
                                                'filter_deal_id' => [
                                                    'doc_count' => 10,
                                                    'group_by_deals.deal_id' => [
                                                        'buckets' => [
                                                            0 => [
                                                                'key' => 'DEAL1',
                                                                'doc_count' => 1,
                                                            ],
                                                            1 => [
                                                                'key' => 'DEAL2',
                                                                'doc_count' => 2,
                                                            ],
                                                            2 => [
                                                                'key' => 'DEAL3',
                                                                'doc_count' => 3,
                                                            ],
                                                            3 => [
                                                                'key' => 'DEAL4',
                                                                'doc_count' => 4,
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
            [
                'type:request-id:529-deals.deal_id:DEAL1' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL1',
                    'total' => 1,
                ],
                'type:request-id:529-deals.deal_id:DEAL2' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL2',
                    'total' => 2,
                ],
                'type:request-id:529-deals.deal_id:DEAL3' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL3',
                    'total' => 3,
                ],
                'type:request-id:529-deals.deal_id:DEAL4' => [
                    'type' => 'request',
                    'id' => 529,
                    'deals.deal_id' => 'DEAL4',
                    'total' => 4,
                ],
            ],
            $result
        );
    }

    /**
     * @unit
     */
    public function test_getAsSqlResult_NestedAggregations_missing_simple()
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
                'A_id:101-D.id:' => [
                    'A_id' => 101,
                    'D.id' => NULL,
                    'total' => 446,
                ],
                'A_id:100-D.id:' => [
                    'A_id' => 100,
                    'D.id' => NULL,
                    'total' => 8,
                ],
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_NestedAggregations_missing_with_operation()
    {
        $result = (new ElasticSearchResult(
            [
                "hits" => [
                    "total" => 454,
                    "max_score" => 0,
                    "hits" => []
                ],
                "aggregations" => [
                    "group_by_A_id" => [
                        "doc_count_error_upper_bound" => 0,
                        "sum_other_doc_count" => 0,
                        "buckets" => [
                            [
                                "key" => 101,
                                "doc_count" => 446,
                                "group_by_E" => [
                                    "doc_count_error_upper_bound" => 0,
                                    "sum_other_doc_count" => 0,
                                    "buckets" => [
                                        [
                                            "key" => "my-event",
                                            "doc_count" => 446,
                                            "calculation_avg_cpm" => [
                                                "value" => 6.9845391990103
                                            ],
                                            "nested_Ds" => [
                                                "doc_count" => 0,
                                                "group_by_Ds.D_id" => [
                                                    "doc_count_error_upper_bound" => 0,
                                                    "sum_other_doc_count" => 0,
                                                    "buckets" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ],
                            [
                                "key" => 100,
                                "doc_count" => 8,
                                "group_by_E" => [
                                    "doc_count_error_upper_bound" => 0,
                                    "sum_other_doc_count" => 0,
                                    "buckets" => [
                                        [
                                            "key" => "my-event",
                                            "doc_count" => 8,
                                            "calculation_avg_cpm" => [
                                                "value" => 9.2425
                                            ],
                                            "nested_Ds" => [
                                                "doc_count" => 0,
                                                "group_by_Ds.D_id" => [
                                                    "doc_count_error_upper_bound" => 0,
                                                    "sum_other_doc_count" => 0,
                                                    "buckets" => []
                                                ]
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            [
                'A_id:101-E:my-event-Ds.D_id:' => [
                    'A_id'    => 101,
                    'E'       => 'my-event',
                    'avg_cpm' => 6.9845391990103,
                    'total'   => 446,
                    'Ds.D_id' => NULL,
                ],
                'A_id:100-E:my-event-Ds.D_id:' => [
                    'A_id'    => 100,
                    'E'       => 'my-event',
                    'avg_cpm' => 9.2425,
                    'total'   => 8,
                    'Ds.D_id' => NULL,
                ],
            ],
            $result
        );
    }

    /**
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
            [
                'deals.deal_id:DEAL1' => [
                    'deals.deal_id' => 'DEAL1',
                    'total' => 12349,
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
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_script_aggregation()
    {
        $result = (new ElasticSearchResult(
            [
                "aggregations" => [
                    "script_script-result" => [
                        "doc_count_error_upper_bound" => 0,
                        "sum_other_doc_count" => 0,
                        "buckets" => [
                            [
                                "key" => 5,
                                "doc_count" => 64715
                            ],
                            [
                                "key" => 4,
                                "doc_count" => 27131
                            ],
                            [
                                "key" => 8,
                                "doc_count" => 22032
                            ],
                            [
                                "key" => 6,
                                "doc_count" => 8699
                            ],
                            [
                                "key" => 0,
                                "doc_count" => 6935
                            ],
                            [
                                "key" => 7,
                                "doc_count" => 4462
                            ],
                            [
                                "key" => 3,
                                "doc_count" => 3053
                            ],
                            [
                                "key" => 9,
                                "doc_count" => 1296
                            ],
                            [
                                "key" => 15,
                                "doc_count" => 963
                            ],
                            [
                                "key" => 10,
                                "doc_count" => 634
                            ],
                            [
                                "key" => 12,
                                "doc_count" => 466
                            ],
                            [
                                "key" => 11,
                                "doc_count" => 153
                            ],
                            [
                                "key" => 2,
                                "doc_count" => 152
                            ],
                            [
                                "key" => 16,
                                "doc_count" => 86
                            ],
                            [
                                "key" => 13,
                                "doc_count" => 69
                            ],
                            [
                                "key" => 14,
                                "doc_count" => 38
                            ],
                            [
                                "key" => 1,
                                "doc_count" => 12
                            ],
                            [
                                "key" => 17,
                                "doc_count" => 5
                            ],
                            [
                                "key" => 20,
                                "doc_count" => 4
                            ],
                            [
                                "key" => 18,
                                "doc_count" => 3
                            ],
                            [
                                "key" => 21,
                                "doc_count" => 3
                            ],
                            [
                                "key" => 26,
                                "doc_count" => 2
                            ],
                            [
                                "key" => 28,
                                "doc_count" => 2
                            ],
                            [
                                "key" => 32,
                                "doc_count" => 2
                            ],
                            [
                                "key" => 19,
                                "doc_count" => 1
                            ],
                            [
                                "key" => 27,
                                "doc_count" => 1
                            ],
                            [
                                "key" => 45,
                                "doc_count" => 1
                            ]
                        ]
                    ]
                ]
            ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            [
                0 => [
                    'script_script-result' => [
                        0 => 5,
                        1 => 4,
                        2 => 8,
                        3 => 6,
                        4 => 0,
                        5 => 7,
                        6 => 3,
                        7 => 9,
                        8 => 15,
                        9 => 10,
                        10 => 12,
                        11 => 11,
                        12 => 2,
                        13 => 16,
                        14 => 13,
                        15 => 14,
                        16 => 1,
                        17 => 17,
                        18 => 20,
                        19 => 18,
                        20 => 21,
                        21 => 26,
                        22 => 28,
                        23 => 32,
                        24 => 19,
                        25 => 27,
                        26 => 45,
                    ],
                ],
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_filters_aggregation()
    {
        $result = (new ElasticSearchResult(
            [
                "aggregations" => [
                    "filters_0abebf637b9e4e88208c14addcf966db" => [
                        'buckets' => [
                            'left' => [
                                'doc_count' => 2266,
                            ],
                            'center' => [
                                'doc_count' => 21807,
                            ],
                            'right' => [
                                'doc_count' => 10083,
                            ],
                            'other' => [
                                'doc_count' => 10,
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
                0 => [
                    'filters_left'   => 2266,
                    'filters_center' => 21807,
                    'filters_right'  => 10083,
                    'filters_other'  => 10,
                ],
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_count_nested_aggregation()
    {
        // WITHOUT NESTED GROUP_BY
        $result = (new ElasticSearchResult(
            [
                "aggregations" => [
                    "count_nested_inventory" => [
                        'doc_count' => 2266
                    ],
                ],
            ]
        ))
        ->getAsSqlResult();

        $this->assertEquals(
            [
                0 => [
                    'total' => 2266,
                ],
            ],
            $result
        );

        // WITH NON-NESTED GROUP_BY
        $result = (new ElasticSearchResult(
            [
                "hits" => [
                    "total" => 36185,
                    "max_score" => 0,
                    "hits" => []
                ],
                "aggregations" => [
                    "nested_inventory" => [
                        'doc_count' => 2266,
                        'group_by_p_id' => [
                            'doc_count_error_upper_bound' => 0,
                            'sum_other_doc_count' => 0,
                            'buckets' => [
                                [
                                    'key' => 1,
                                    'doc_count' => 1000,
                                ],
                                [
                                    'key' => 2,
                                    'doc_count' => 1266,
                                ],
                            ]
                        ]
                    ],
                ],
            ]
        ))
        ->getAsSqlResult();

        $this->assertEquals(
            [
                'p_id:1' => [
                    'total' => 1000,
                    'p_id' => 1,
                ],
                'p_id:2' => [
                    'total' => 1266,
                    'p_id' => 2,
                ],
            ],
            $result
        );


        // WITH NESTED GROUP_BY
        $result = (new ElasticSearchResult(
            [
                "hits" => [
                    "total" => 36185,
                    "max_score" => 0,
                    "hits" => []
                ],
                "aggregations" => [
                    "nested_inventory" => [
                        'doc_count' => 2266,
                        'group_by_adserver_id' => [
                            'doc_count_error_upper_bound' => 0,
                            'sum_other_doc_count' => 0,
                            'buckets' => [
                                [
                                    'key' => 1,
                                    'doc_count' => 1000,
                                ],
                                [
                                    'key' => 2,
                                    'doc_count' => 1266,
                                ],
                            ]
                        ]
                    ],
                ],
            ]
        ))
        ->getAsSqlResult();

        $this->assertEquals(
            [
                'adserver_id:1' => [
                    'total' => 1000,
                    'adserver_id' => 1,
                ],
                'adserver_id:2' => [
                    'total' => 1266,
                    'adserver_id' => 2,
                ],
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_operation_on_script_aggregation()
    {
        // ne rsult case
        $result = (new ElasticSearchResult(
        [
            "hits" => [
                "total" => 36185,
                "max_score" => 0,
                "hits" => []
            ],
            "aggregations" => [
                "group_by_campaign_id" => [
                    "doc_count_error_upper_bound" => 0,
                    "sum_other_doc_count" => 0,
                    "buckets" => [
                        [
                            "key" => 1357,
                            "doc_count" => 36185,
                            "calculation_avg_scroll_size" => [
                                "value" => null
                            ],
                        ],
                    ],
                ],
            ],
        ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals([
                'campaign_id:1357' => [
                    'campaign_id' => 1357,
                    'total' => 36185,
                    'avg_scroll_size' => NULL,
                ],
            ],
            $result
        );
    }

    /**
     */
    public function test_getAsSqlResult_operation_on_inline_aggregation()
    {
        // ne rsult case
        $result = (new ElasticSearchResult(
            [
                "aggregations" => [
                    "inline_width_ratio.mean" => [
                        "doc_count_error_upper_bound" => 0,
                        "sum_other_doc_count" => 0,
                        "buckets" => [
                            [
                                "key" => 5,
                                "doc_count" => 1357,
                                "height_ratio.mean" => [
                                    "doc_count_error_upper_bound" => 0,
                                    "sum_other_doc_count" => 0,
                                    "buckets" => [
                                        [
                                            "key" => 2,
                                            "doc_count" => 522
                                        ],
                                        [
                                            "key" => 3,
                                            "doc_count" => 483
                                        ],
                                    ]
                                ]
                            ],
                            [
                                "key" => 7,
                                "doc_count" => 611,
                                "height_ratio.mean" => [
                                    "doc_count_error_upper_bound" => 0,
                                    "sum_other_doc_count" => 0,
                                    "buckets" => [
                                        [
                                            "key" => 3,
                                            "doc_count" => 352
                                        ],
                                        [
                                            "key" => 4,
                                            "doc_count" => 139
                                        ],
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            ]
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            [
                0 => [
                    "inline_width_ratio.mean" => [
                        "doc_count_error_upper_bound" => 0,
                        "sum_other_doc_count" => 0,
                        "buckets" => [
                            [
                                "key" => 5,
                                "doc_count" => 1357,
                                "height_ratio.mean" => [
                                    "doc_count_error_upper_bound" => 0,
                                    "sum_other_doc_count" => 0,
                                    "buckets" => [
                                        [
                                            "key" => 2,
                                            "doc_count" => 522
                                        ],
                                        [
                                            "key" => 3,
                                            "doc_count" => 483
                                        ],
                                    ]
                                ]
                            ],
                            [
                                "key" => 7,
                                "doc_count" => 611,
                                "height_ratio.mean" => [
                                    "doc_count_error_upper_bound" => 0,
                                    "sum_other_doc_count" => 0,
                                    "buckets" => [
                                        [
                                            "key" => 3,
                                            "doc_count" => 352
                                        ],
                                        [
                                            "key" => 4,
                                            "doc_count" => 139
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $result
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
