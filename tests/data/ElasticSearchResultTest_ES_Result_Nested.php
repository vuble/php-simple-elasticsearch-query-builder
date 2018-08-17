<?php

return [
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
];