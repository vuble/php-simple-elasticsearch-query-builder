<?php
namespace JClaveau\ElasticSearch;

use JClaveau\VisibilityViolator\VisibilityViolator;


/**
 * Video::getPrice
 */
class QueryTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        return parent::setUp();
    }

    /**
     */
    public function test_where_equal()
    {

        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->where('field', '=', 'value');
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');
        $this->assertEquals('value', $filters[0]['term']['field']);
    }

    /**
     */
    public function test_where_in()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', 'IN', 'value');
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals(['value'], $filters[0]['terms']['field']);

        // multiple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', 'IN', ['value1', 'value2']);
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals(['value1', 'value2'], $filters[0]['terms']['field']);
    }

    /**
     */
    public function test_addFilterLevel()
    {
        $query  = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        VisibilityViolator::callHiddenMethod($query, 'addFilterLevel', ['osef', function($query) {
            //
        }], true);

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');
        
        $this->assertEquals([
            ['osef' => []],
        ], $filters);
    }

    /**
     */
    public function test_orWhere()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->where('field', '=', 'value');
            $query->where('field2', '=', 'value2');
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([[
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'term' => [
                        'field2' => 'value2',
                    ]
                ]
            ]
        ]], $filters);
    }

    /**
     */
    public function test_where_not_in()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', 'NOT IN', 'value');

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([[
            'bool' => [
                'must_not' => [
                    [
                        'terms' => [
                            'field' => ['value'],
                        ]
                    ],
                ],
            ],
        ]], $filters);
    }

    /**
     */
    public function test_where_greater_than()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '>', 'value');

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'gt' => 'value'
                    ],
                ]
            ]
        ], $filters);
    }

    /**
     */
    public function test_where_lower_than()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $query->where('field', '<', 'value');
        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'lt' => 'value'
                    ],
                ]
            ]
        ], $filters);
    }

    /**
     */
    public function test_getSearchParams()
    {
        // simple
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $filters = VisibilityViolator::setHiddenProperty($query, 'filters', [
            [
                'range' => [
                    'field' => [
                        'lt' => 'value'
                    ],
                ],
            ]
        ]);

        $params = $query->getSearchParams();
        $must   = $params['body']['query']['constant_score']['filter']['bool']['must'];

        // print_r($params);

        $this->assertEquals([
            [
                'range' => [
                    'field' => [
                        'lt' => 'value'
                    ],
                ]
            ]
        ], $must);

        // with a group of filters
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );
        $filters = VisibilityViolator::setHiddenProperty($query, 'filters', [
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'term' => [
                        'field2' => 'value2',
                    ]
                ]
            ]
        ]);

        $params = $query->getSearchParams();
        $must   = $params['body']['query']['constant_score']['filter']['bool']['must'];

        // print_r($must);
        $this->assertEquals([
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'term' => [
                        'field2' => 'value2',
                    ]
                ]
            ]
        ], $must);
    }

    /**
     */
    public function test_should_notIn_full()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->where('field', '=', 'value');
            $query->where('field2', 'NOT IN', 'value2');
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        // print_r($filters);

        $this->assertEquals([[
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'bool' => [
                        'must_not' => [
                            [
                                'terms' => [
                                    'field2' => ['value2'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]], $filters);

    }

    /**
     */
    public function test_openOr_closeOr_notIn_full()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->where('field', '=', 'value');
            $query->where('field2', 'NOT IN', 'value2');
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        // print_r($filters);

        $this->assertEquals([[
            'or' => [
                [
                    'term' => [
                        'field' => 'value',
                    ]
                ],
                [
                    'bool' => [
                        'must_not' => [
                            [
                                'terms' => [
                                    'field2' => ['value2'],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]], $filters);

    }

    /**
     */
    public function test_and_inside_or()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->should(function ($query) {
            $query->must(function ($query) {
                $query->where('field', '=', 'value');
                $query->where('field2', 'NOT IN', 'value2');
            });
            $query->must(function ($query) {
                $query->where('field3', '=', 'value3');
                $query->where('field2', 'NOT IN', 'something else');
            });
        });

        $filters = VisibilityViolator::getHiddenProperty($query, 'filters');

        // print_r($filters);

        $this->assertEquals([
            [
                'or' => [
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'field' => 'value',
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'terms' => [
                                                    'field2' => ['value2'],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'field3' => 'value3',
                                    ]
                                ],
                                [
                                    'bool' => [
                                        'must_not' => [
                                            [
                                                'terms' => [
                                                    'field2' => ['something else'],
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
        ], $filters);
    }

    /**
     */
    public function test_addOperationAggregation()
    {
        $query = new ElasticSearchQuery;

        $query->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field_to_sum']);

        $es_query = $query->getSearchParams();

        $this->assertEquals([
            'sum' => ['field' => 'field_to_sum']
        ], $es_query['body']['aggregations']['calculation_sum_field_to_sum']);
    }

    /**
     */
    public function test_fieldRenamer()
    {
        $query = new ElasticSearchQuery;

        $query
            ->setFieldRenamer(function($field_name) {
                if ($field_name == 'field_to_rename')
                    return 'renamed_field';
                
                return $field_name;
            })
            ->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field_to_rename'])
            ->addOperationAggregation( ElasticSearchQuery::SUM, ['field' => 'field_with_good_name'])
            ->groupBy('field_to_groupon')
            ->addOperationAggregation( ElasticSearchQuery::AVERAGE, ['field' => 'field_for_avg'])
            ;

            
        $es_query = $query->getSearchParams();
        // print_r($es_query);
        
        $this->assertEquals([
            'sum' => ['field' => 'renamed_field']
        ], $es_query['body']['aggregations']['calculation_sum_field_to_rename']);

        $this->assertEquals([
            'sum' => ['field' => 'field_with_good_name']
        ], $es_query['body']['aggregations']['calculation_sum_field_with_good_name']);
        
        $this->assertEquals([
            'avg' => ['field' => 'field_for_avg']
        ], $es_query['body']['aggregations']['group_by_field_to_groupon']['aggregations']['calculation_avg_field_for_avg']);
    }

    /**/
}
