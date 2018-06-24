<?php
/**
 *   'aggregations' => [
 *       'group_by_month' => [
 *           "date_histogram" => [
 *               "field"         => "date",
 *               "interval"      => "month",
 *               "format"        =>  "YYYY-MM",
 *               "min_doc_count" => 0, // fill empty intervals with 0
 *           ],
 *           'aggregations' => [
 *               'group_by_device' => [
 *                   'terms' => [
 *                       'field' => 'metadata.device_type',
 *                   ],
 *               ],
 *           ],
 *       ],
 *   ],
 */

namespace JClaveau\ElasticSearch;

class ElasticSearchQuery
{
    // query types
    // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-aggregations-metrics-stats-aggregation.html
    const COUNT             = 'count';
    const AVERAGE           = 'average';
    const MAX               = 'max';
    const MIN               = 'min';
    const SUM               = 'sum';
    const CARDINALITY       = 'cardinality';
    const PERCENTILES       = 'percentiles';
    const PERCENTILES_RANKS = 'percentiles_ranks';
    const STATS             = 'stats';
    const EXTENDED_STATS    = 'extended_stats';
    const GEO_BOUNDS        = 'geo_bounds';
    const GEO_CENTROID      = 'geo_centroid';
    const VALUE_COUNT       = 'value_count';
    const SCRIPTED          = 'scripted';
    const HISTOGRAM         = 'histogram';
    const CUSTOM            = 'custom';

    /** @var string MISSING_AGGREGATION_FIELD
     * If a field to aggregate on for grouping is missing, we need a default value
     * but nuill is not supported so we set -1 and replace it during the result
     * parsing.
     */
    const MISSING_AGGREGATION_FIELD = -1;

    /** @var $type The type of query COUNT|MAX|MIN|... */
    protected $type;
    /** @var $field The filed on which apply the operation defined by the $type */
    // protected $field;
    protected $parameters = [];

    protected $aggregations;
    protected $current_aggregation   = [];

    protected $filters;
    protected $current_filters_level = [];

    protected $index_pattern;

    protected $dateRanges            = [];

    protected $nested_fields         = [];

    /**
     * Constructor.
     *
     * @param   string $query_type COUNT|MIN|MAX|SUM...
     * @$column string $field      All types excepted COUNT
     */
    public function __construct($query_type, $parameters=[])
    {
        if ( ! in_array($query_type, $this->supportedQueryTypes()))
            throw new \ErrorException('Unimplemented type of ES query: '
                . $query_type);

        $this->type                  = $query_type;
        $this->parameters            = $parameters;
        $this->aggregations          = &$this->current_aggregation;
        $this->filters               = &$this->current_filters_level;
        // $this->current_filters_level = &$this->filters;
    }

    /**
     *
     */
    protected function supportedQueryTypes()
    {
        return [
            // scalar results
            self::COUNT,
            self::AVERAGE,
            self::MAX,
            self::MIN,
            self::SUM,
            self::HISTOGRAM,
            // self::VALUE_COUNT,

            self::CARDINALITY,
            self::PERCENTILES,
            // self::PERCENTILES_RANKS,
            // self::STATS,
            self::EXTENDED_STATS,
            // self::GEO_BOUNDS,
            // self::GEO_CENTROID,
            // self::SCRIPTED,
            self::CUSTOM,
        ];
    }

    /**
     * groupBy corresponds to the most basic aggregation type.
     */
    public function groupBy($field_alias, array $aggregation_parameters=[])
    {
        $field = ElasticSearch_Server::addMetadataIfRequired($field_alias);

        if (!$aggregation_parameters) {
            // default aggregagtion is by term
            $aggregation_parameters = [
                'terms' => [
                    'field' => $field,
                ],
            ];
        }

        $this->aggregate('group_by_'.$field_alias, $aggregation_parameters);

        return $this;
    }

    protected $aggregationNames = [];

    /**
     *
     */
    private function aggregate($field_alias, array $aggregation_parameters)
    {
        if (in_array($field_alias, $this->aggregationNames) ) {
            // avoid duplicating aggregations
            return $this;
        }

        $this->aggregationNames[] = $field_alias;

        /** /
        if ($field_alias == 'avg') {
            var_dump( $aggregation_parameters );
            var_dump( debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5) );
            // exit;
        }
        /**/

        ini_set('memory_limit', '4096M');

        // type can be terms|date_historgram|...
        $keys             = array_keys($aggregation_parameters);
        $aggregation_type = reset($keys);

        // buggy with date_histogram
        if ($aggregation_type == 'terms') {
            // No limit of data to aggregate on
            $aggregation_parameters[$aggregation_type]['size'] = 0;
            // null not supported for "missing" option
            $aggregation_parameters[$aggregation_type]['missing'] = self::MISSING_AGGREGATION_FIELD;
        }

        $this->current_aggregation['aggregations'] = [];

        $this->current_aggregation['aggregations'][$field_alias]
            = $aggregation_parameters;

        $this->current_aggregation
            = &$this->current_aggregation['aggregations'][$field_alias];

        return $this;
    }

    /**
     *
     */
    public function getAggregationsQueryPart()
    {
        return !empty($this->aggregations['aggregations'])
            ? $this->aggregations['aggregations']
            : null;
    }

    /**
     * Add the nested wrapper required to filter on njested fields.
     *
     * @param string $field
     * @param array  $filter
     *
     * @return array
     */
    protected function wrapFilterIfNested($field, $filter)
    {
        if (!$this->nested_fields)
            return $filter;

        foreach ($this->nested_fields as $nested_field) {
            if (preg_match("#^".preg_quote($nested_field, '#')."#", $field)) {
                break;
            }
            return $filter;
        }

        $new_filter = [
            "nested" => [
                'path' => $nested_field,
                'query' => [
                    'filtered' => [
                        "filter" => [
                            "bool" => [
                                "must" => [
                                    $filter
                                ],
                            ]
                        ]
                    ]
                ],
            ],
        ];

        /**/
        // add two levels of aggregation:
        // + nested aggregation on the nested path
        // + repeat the nested filter in the nested aggregation
        $this->aggregate('nested_'.$nested_field, [
            'nested' => [
                'path'=> $nested_field,
            ],
        ]);


        /**/
        if (isset($this->current_filters_level['type']) && $this->current_filters_level['type'] == 'or') {
            // if parent is OR use parent as filter
            // Debug::dumpJson( array_keys($this->current_filters_level), true);
            // Debug::dumpJson( array_keys($this->current_filters_level['parent']), true);
            // Debug::dumpJson( $filter, true);
            // Debug::dumpJson( $filter, true);
            $filter = [
                'bool' => [
                    'must' => [
                        'or' => &$this->current_filters_level
                    ]
                ]
            ];

            // Debug::dumpJson( $this->current_filters_level, true);
        }
        /**/

        $this->aggregate('filter_'.$field, [
            'filter' => $filter,
        ]);
        /**/

        return $new_filter;
    }

    /**
     *
     */
    public function where($field, $operator, $values=null, $or_missing=false)
    {
        $operator = strtolower($operator);
        // $field    = ElasticSearch_Server::addMetadataIfRequired($field);

        if ($operator != 'exists' && is_null($values)) {
            throw new \InvalidArgumentException(
                "Only EXISTS clause doesn't require a value "
            );
        }

        // echo json_encode([
            // 'line'   => __LINE__,
            // 'op'     => $operator,
            // 'field'  => $field,
            // 'values' => $values,
        // ]);

        if ($values instanceof Helper_Table)
            $values = $values->getArray();


        if ($operator == 'in') {
            if (!is_array($values))
                $values = [$values];

            $values = array_values($values);

            if (!$or_missing) {
                // $filter_rule = [
                    // [
                        // 'terms' => [
                            // $field => $values,
                        // ],
                    // ],
                // ];
                // $this->filters[] = $filter_rule;

                $this->addFilter( $this->wrapFilterIfNested( $field, [ 'terms' => [
                        $field => $values,
                ]]) );
            }
            else {
                // $filter_rule = [
                    // 'or' => [
                        // 'filters' => [
                            // [
                                // 'terms' => [
                                    // $field => $values,
                                // ],
                            // ],
                            // [
                                // 'missing' => [
                                    // 'field' => $field,
                                // ],
                            // ],
                        // ],
                    // ],
                // ];

                // $this->filters[] = $filter_rule;

                $this->addFilterLevel('or', function($query) use ($field, $values) {
                    $this->addFilter( $this->wrapFilterIfNested( $field, [
                        [
                            'terms' => [
                                $field => $values,
                            ],
                        ],
                        [
                            'missing' => [
                                'field' => $field,
                            ],
                        ],
                    ]) );
                });
            }

        }
        elseif ($operator == 'not in') {
            if (!is_array($values))
                $values = [$values];

            // must_not has to be nested in a bool query
            $this->addFilterLevel('bool', function($query) use ($field, $values) {
                $this->addFilterLevel('must_not', function($query) use ($field, $values) {
                    $this->addFilter( $this->wrapFilterIfNested( $field, [ 'terms' => [
                        $field => array_values($values),
                    ]]) );
                }, true);
            });

        }
        elseif ($operator == '=') {

            if (!is_scalar($values)) {
                throw new \ErrorException(
                    "Non scalar value for = comparison in ES: \n"
                    .var_export($values, true)
                );
            }

            if (!$or_missing) {
                // $filter_rule = [
                    // [
                        // 'term' => [
                            // $field => $values,
                        // ],
                    // ],
                // ];
                // $this->filters[] = $filter_rule;

                $this->addFilter( $this->wrapFilterIfNested( $field, [ 'term' => [
                    $field => $values,
                ]]) );
            }
            else {
                // $filter_rule = [
                    // 'or' => [
                        // 'filters' => [
                            // [
                                // 'term' => [
                                    // $field => $values,
                                // ],
                            // ],
                            // [
                                // 'missing' => [
                                    // 'field' => $field,
                                // ],
                            // ],
                        // ],
                    // ],
                // ];

                // $this->filters[] = $filter_rule;
            }
        }
        elseif ($operator == 'between') {

            $start = Date::createFromArgument($values[0]);
            $end   = Date::createFromArgument($values[1]);

            // this provoques ElasticSearch error :
            // parse_exception: failed to parse date field [2016-09-29 23:59:59.0000000] with format [yyyy-MM-dd HH:mm:ss.SSSSSS]
            // but only in preprod... voodoo bug
            // As this filter is not essential with vuble because of
            // indices granularity by date, I simply comment it.

            //
            // $filter_rule = [
                // 'range' => [
                    // $field => [
                        // 'from' => $start->format('Y-m-d 00:00:00'),
                        // 'to'   => $end->format(  'Y-m-d 23:59:59'),
                    // ],
                // ],
            // ];

            // $this->filters[] = $filter_rule;

            // used to allow cache or not
            $this->dateRanges[] = [
                'start' => $start,
                'end'   => $end,
            ];
        }
        elseif ($operator == '>') {

            $this->addFilter( $this->wrapFilterIfNested( $field, ['range' => [
                $field => [
                    'gt' => is_array($values) ? max($values) : $values,
                ],
            ]]) );

        }
        elseif ($operator == '<') {

            $this->addFilter( $this->wrapFilterIfNested( $field, ['range' => [
                $field => [
                    'lt' => is_array($values) ? max($values) : $values,
                ],
            ]]) );
        }
        elseif ($operator == 'exists') {
            $this->addFilter( $this->wrapFilterIfNested( $field, ['exists' => [
                'field' => $field
            ]]) );
        }
        elseif (in_array($operator, ['regex', 'regexp'])) {
            // example
            // "filter" : {
            //     "regexp": {
            //         "url":".*info-for/media.*"
            //     }
            // }
            $this->addFilter(['regexp' => [
                $field => $values,
            ]]);
        }
        else {
            throw new \ErrorException("Unhandled operator for ES query: " . $operator);
        }
    }

    /**
     * Opens a new level of filters
     *
     * @param string   $type           The type of filter group
     * @param callable $nested_actions The modifier of the query before
     *                                 the group is closed.
     *
     * @return $this
     */
    protected function addFilterLevel( $type, callable $nested_actions, $is_clause=false )
    {
        $this->openFilterLevel($type, $is_clause);

        call_user_func_array($nested_actions, [$this]);

        $this->closeFilterLevel();

        return $this;
    }

    /**
     * Opens a new level of filters
     *
     * @param string   $type           The type of filter group
     *
     * @return $this
     */
    protected function openFilterLevel( $type, $is_clause=false )
    {
        $new_filter_level = [
            'parent'   => &$this->current_filters_level,
            'type'     => $type,
        ];

        if ($is_clause) {
            // for example must/must_not in bool queries
            $this->current_filters_level[$type] = &$new_filter_level;
        }
        else {
            // for example a multiple queries in an "or" statement
            $this->current_filters_level[] = [$type => &$new_filter_level];
        }

        $this->current_filters_level   = &$new_filter_level;

        return $this;
    }

    /**
     * Opens a new level of filters
     *
     * @param string   $type           The type of filter group
     * @param callable $nested_actions The modifier of the query before
     *                                 the group is closed.
     *
     * @return $this
     */
    public function closeFilterLevel()
    {
        $current_filters_level = &$this->current_filters_level;

        $this->current_filters_level = &$current_filters_level['parent'];

        unset(
             $current_filters_level['parent']
            ,$current_filters_level['type']
        );

        return $this;
    }

    /**
     * @return $this
     */
    protected function addFilter( array $filter_rule )
    {
        $this->current_filters_level[] = $filter_rule;
        return $this;
    }

    /**
     * @return $this
     */
    public function should( callable $or_option_definer_callback )
    {
        $this->addFilterLevel('or', $or_option_definer_callback);

        return $this;
    }

    /**
     * @return $this
     */
    public function must( callable $nested_actions )
    {
        $this->openFilterLevel('bool');
        $this->openFilterLevel('must', true);
        
        call_user_func_array($nested_actions, [$this]);
        
        $this->closeFilterLevel();
        $this->closeFilterLevel();
        return $this;
    }

    /**
     * Defines the indexes to look into
     */
    public function setIndex($index_pattern)
    {
        $this->index_pattern = $index_pattern;
        return $this;
    }

    /**
     *
     */
    protected function addOperationAggregation()
    {
        // Operations on data
        if ($this->type == self::HISTOGRAM) {
            $this->aggregate('histogram_'.$this->parameters['field'].'_'.$this->parameters['interval'], [
                'histogram' => [
                    'field'         => ElasticSearch_Server::addMetadataIfRequired( $this->parameters['field'] ),
                    'interval'      => $this->parameters['interval'],
                    // 'min_doc_count' => 1,
                ],
            ]);
        }
        elseif ($this->type == self::COUNT) {
            // COUNT is calculated as a simple SEARCH ES query to enable
            // aggregations
        }
        elseif($this->type == self::CUSTOM) {
            $es_aggregation_type = 'custom';
            $this->aggregate('calculation_'.$es_aggregation_type.'_'.$this->parameters['field'], [
                'filters' =>
                    $this->parameters['specific_filters'],
            ]);
        }
        else {

            if ($this->type == self::AVERAGE) {
                $es_aggregation_type = 'avg';
            }
            elseif ($this->type == self::MIN) {
                $es_aggregation_type = 'min';
            }
            elseif ($this->type == self::MAX) {
                $es_aggregation_type = 'max';
            }
            elseif ($this->type == self::SUM) {
                $es_aggregation_type = 'sum';
            }
            elseif ($this->type == self::EXTENDED_STATS ) {
                $es_aggregation_type = 'extended_stats';
            }
            elseif ($this->type == self::CARDINALITY ) {
                $es_aggregation_type = 'cardinality';
                $this->aggregate('calculation_'.$es_aggregation_type.'_'.$this->parameters['field'], [
                    $es_aggregation_type => [
                        'field' => ElasticSearch_Server::addMetadataIfRequired( $this->parameters['field'] ),
                        // 'precision_threshold' => 40000,
                    ],
                ]);

                return;
            }
            elseif ($this->type == self::PERCENTILES) {
                $es_aggregation_type = 'percentiles';
            }
            else {
                throw new \ErrorException("Queries of type {$this->type} not implemented.");
            }

            $this->aggregate('calculation_'.$es_aggregation_type.'_'.$this->parameters['field'], [
                $es_aggregation_type => [
                    'field' => ElasticSearch_Server::addMetadataIfRequired( $this->parameters['field'] ),
                ],
            ]);
        }
    }

    /**
     * @return array The parameters of generated the ES query.
     */
    public function getSearchParams()
    {

        $params = [
            'index'              => $this->index_pattern,
            'ignore_unavailable' => true,                               // https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-index.html#multi-index
            'body'               => [
                'query' => [
                    'constant_score' => [
                        'filter' => [
                            'bool' => [
                                'must'     => [],
                                // 'must_not' => [],
                            ],
                        ]
                    ],
                ],
                'aggregations' => $this->getAggregationsQueryPart(),
                'size'         => isset($_REQUEST['debug_es']) ? 3 : 0, // return only aggregation results (forget hits)
            ]
        ];

        $params['body']['query']['constant_score']['filter']['bool']['must']
            = $this->filters;

        if ($aggregations = $this->getAggregationsQueryPart()) {
            $params['body']['aggregations'] = $aggregations;
        }

        return $params;
    }

    /**
     * Execute this query.
     *
     * @param array $options Handles 'disable_cache' as bool
     *
     * @return ElasticSearch_Result
     */
    public function execute(array $options=[])
    {
        $this->addOperationAggregation();
        $params = $this->getSearchParams();

        // we disable the cache if the current day is between the ranges
        $today = new \DateTime('today');
        $cache_is_enabled = !isset($_REQUEST['es_nocache']) || Debug::get('disable_es_cache');
        // $cache_is_enabled = false;
        // foreach ($this->dateRanges as $i => $dateRange) {
            // if ($dateRange['start'] <= $today && $dateRange['end'] >= $today)
                // $cache_is_enabled = false;
        // }

        $profiler_token = Profiler::start('es', __METHOD__ . '::' . __LINE__);

        $cache_key = hash('crc32b', var_export($params, true)).'-es';

        if ($cache_is_enabled && empty($options['disable_cache'])) {
            if ($cached = Cache::get($cache_key)) {
                $result = unserialize($cached);
            }
        }

        if (!isset($result)) {
            $client = ElasticSearch_Server::getClient();

            try {
                // echo json_encode($params);
                // exit;
                $result = $client->search($params);
            }
            catch (\Exception $e) {
                // throw $e;
                // http_response_code(500);
                echo json_encode([
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'params'  => $params,
                ]);
                return new ElasticSearch_Result([]);
                // exit;
            }

            Cache::set(
                $cache_key,
                serialize($result),
                MB::$config->load('elasticSearch.query_cache') ? : 60 * 5
            );
        }

        Profiler::stop($profiler_token);

        // echo json_encode([
            // 'method' => __FILE__ . ' ' . __LINE__,
            // 'file'   => __METHOD__,
            // 'stats'  => Profiler::stats([$profiler_token]),
        // ]);

        return new ElasticSearch_Result($result);
    }

    /**
     * Execute this query with body and index passed in params.
     * TODO function replaced by search() with doctrine/es
     *
     * @param string $index
     * @param array $body
     * @param array $options
     *
     * @return ElasticSearch_Result
     */
    public static function directExecute($index, array $body, array $options=[])
    {
        $body['index'] = $index;
        $body['ignore_unavailable'] = true;

        $cache_is_enabled = !isset($_REQUEST['es_nocache']) || Debug::get('disable_es_cache');

        $profiler_token = Profiler::start('es', __METHOD__ . '::' . __LINE__);

        $cache_key = hash('crc32b', var_export($body, true)).'-es';

        if ($cache_is_enabled && empty($options['disable_cache'])) {
            if ($cached = Cache::get($cache_key)) {
                $result = unserialize($cached);
            }
        }

        if (!isset($result)) {
            // TODO Voir pourquoi la connection n'est pas active
            $client = ElasticSearch_Server::getClient();
            if (is_null($client)) {
                ElasticSearch_Server::connect();
                $client = ElasticSearch_Server::getClient();
            }

            try {
                $result = $client->search($body);
            }
            catch (\Exception $e) {
                //Debug::dumpJson($body);
                //Debug::dumpJson($e->getMessage(), true);
                // throw $e;
                // http_response_code(500);
                echo json_encode([
                    'message' => $e->getMessage(),
                    'file'    => $e->getFile(),
                    'line'    => $e->getLine(),
                    'params'  => $body,
                ]);
                return new ElasticSearch_Result([]);
            }
            //Debug::dumpJson($body);

            Cache::set($cache_key, serialize($result), 60 * 5);
        }

        Profiler::stop($profiler_token);

        return $result['aggregations'];
    }

    /**/
}
