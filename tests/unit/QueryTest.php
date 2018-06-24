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

        $query->orWhere(function ($query) {
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
    public function test_orWhere_notIn_full()
    {
        $query = new ElasticSearchQuery( ElasticSearchQuery::COUNT );

        $query->orWhere(function ($query) {
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

        $query->orWhere(function ($query) {
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

    /**/
}
