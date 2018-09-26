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

class ElasticSearchQuery implements \JsonSerializable
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
    const SCRIPT            = 'script';
    const HISTOGRAM         = 'histogram';
    const CUSTOM            = 'custom';
    const INLINE            = 'inline'; // aggregations provide directly as ES json DSL

    /** @var string MISSING_AGGREGATION_FIELD
     * If a field to aggregate on for grouping is missing, we need a default value
     * but nuill is not supported so we set -1 and replace it during the result
     * parsing.
     */
    const MISSING_AGGREGATION_FIELD = -1;


    /** @var string Elastic Search time unit */
    protected $request_timeout = null;

    protected $aggregations;
    protected $current_aggregation   = [];

    protected $filters;
    protected $current_filters_level = [];

    protected $index_pattern;

    protected $dateRanges            = [];

    protected $nested_fields         = [];


    protected $field_renamer;

    /**
     * Constructor.
     *
     * @param   string $query_type COUNT|MIN|MAX|SUM...
     * @$column string $field      All types excepted COUNT
     */
    public function __construct()
    {
        $this->aggregations          = &$this->current_aggregation;
        $this->filters               = &$this->current_filters_level;
    }

    /** @var array $group_by_aggregations_on_nested_fields */
    protected $group_by_aggregations_on_nested_fields = [];

    /**
     * groupBy corresponds to the most basic aggregation type.
     */
    public function groupBy($field_alias, array $aggregation_parameters=[])
    {
        $field = $this->renameField($field_alias);

        if (!$aggregation_parameters) {
            // default aggregagtion is by term
            $aggregation_parameters = [
                'terms' => [
                    'field' => $field,
                ],
            ];
        }

        if ($this->fieldIsNested($field, $nesting_field)) {
            // setting an aggregation on non nested field inside another
            // one concerning nested fields would produce elastic search errors :
            // Elasticsearch\Common\Exceptions\ServerErrorResponseException:
            // Merging/Reducing the aggregations failed when computing the aggregation [ Name: group_by_deals.win, Type: terms ] because: the field you gave in the aggregation query existed as two different types in two different indices

            // We need to aggregate on the nested path first
            if (!isset($this->group_by_aggregations_on_nested_fields[$nesting_field])) {
                $this->group_by_aggregations_on_nested_fields['nested_'.$nesting_field] = [
                    'nested' => [
                        'path' => $nesting_field,
                    ]
                ];
            }

            if (!isset($this->group_by_aggregations_on_nested_fields[$field]))
                $this->group_by_aggregations_on_nested_fields['group_by_'.$field_alias] = $aggregation_parameters;
        }
        else {
            $this->aggregate('group_by_'.$field_alias, $aggregation_parameters);
        }

        return $this;
    }

    protected $aggregationNames = [];

    /**
     * Build aggregation for ES request
     *
     * @param string    $field_alias
     * @param array     $aggregation_parameters
     * @param bool      $change_aggregation_level
     * @param bool      $store_for_duplicate_check
     *
     * @return $this
     */
    private function aggregate($field_alias, array $aggregation_parameters, $change_aggregation_level=true, $store_for_duplicate_check=true)
    {
        if (in_array($field_alias, $this->aggregationNames) ) {
            // avoid duplicating aggregations
            return $this;
        }

        if ($store_for_duplicate_check) {
            $this->aggregationNames[] = $field_alias;
        }

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
            if (empty($aggregation_parameters[$aggregation_type]['script']))
                $aggregation_parameters[$aggregation_type]['missing'] = self::MISSING_AGGREGATION_FIELD;
        }

        if (!isset($this->current_aggregation['aggregations']))
            $this->current_aggregation['aggregations'] = [];

        $this->current_aggregation['aggregations'][$field_alias]
            = $aggregation_parameters;

        if ($change_aggregation_level) {
            $this->current_aggregation
                = &$this->current_aggregation['aggregations'][$field_alias];
        }

        return $this;
    }

    public function setRequestTimeout($request_timeout)
    {
        $this->request_timeout = $request_timeout;
        return $this;
    }

    public function getRequestTimeout()
    {
        return $this->request_timeout;
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
     * Add the nested wrapper required to filter on nested fields.
     *
     * @param string $field
     * @param array  $filter
     *
     * @return array
     */
    protected function wrapFilterIfNested($field, $filter)
    {
        if (!$this->fieldIsNested($field, $nesting_field))
            return $filter;

        $new_filter = [
            "nested" => [
                'path' => $nesting_field,
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

        return $new_filter;
    }

    /**
     *
     */
    public function where($field, $operator, $values=null, $or_missing=false)
    {
        $operator = strtolower($operator);
        $field    = $this->renameField($field);

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
        elseif ($operator == 'not in' || $operator == '!in') {
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
    public function openFilterLevel( $type, $is_clause=false )
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
    public function addFilter( array $filter_rule )
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
    public function openShould()
    {
        $this->openFilterLevel('bool');
        $this->openFilterLevel('should', true);

        return $this;
    }

    /**
     * @return $this
     */
    public function closeShould()
    {
        $this->closeFilterLevel();
        $this->closeFilterLevel();

        return $this;
    }

    /**
     * @return $this
     */
    public function must( callable $nested_actions )
    {
        $this->openMust();

        call_user_func_array($nested_actions, [$this]);

        $this->closeMust();
        return $this;
    }

    /**
     * @return $this
     */
    public function openMust()
    {
        $this->openFilterLevel('bool');
        $this->openFilterLevel('must', true);

        return $this;
    }

    /**
     * @return $this
     */
    public function closeMust()
    {
        $this->closeFilterLevel();
        $this->closeFilterLevel();

        return $this;
    }

    /**
     * Defines the indexes to look into
     */
    public function setIndex($index_pattern)
    {
        if (is_array($index_pattern))
            $index_pattern = implode(',', $index_pattern);

        $this->index_pattern = $index_pattern;
        return $this;
    }

    /**
     * Defines a callback that will rename fields before the query
     */
    public function setFieldRenamer(callable $renamer=null)
    {
        $this->field_renamer = $renamer;
        return $this;
    }

    /**
     */
    protected function renameField($field_name)
    {
        if (!is_string($field_name)) {
            throw new \InvalidArgumentException(
                "\$field_name must be a string instead of: "
                . var_export($field_name, true)
            );
        }

        if (!$this->field_renamer)
            return $field_name;

        return call_user_func($this->field_renamer, $field_name);
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
            self::SCRIPT,
            self::CUSTOM,
            self::INLINE,
        ];
    }

    /**
     * ES allows operations at each level of the nested tree of aggregations
     * but mostly those at the end are easy to understand.
     * This quee stores operations that must be added at the very last level
     * of the aggregation tree.
     *
     * This list would be added
     *
     */
    protected $queued_leaf_operations = [];

    protected $operations = [];

    /**
     *
     */
    public function addOperationAggregation($type, array $parameters=[], $as_leaf_of_aggregation_tree=true)
    {
        $change_aggregation_level = false;

        if ( ! in_array($type, $this->supportedQueryTypes())) {
            throw new \ErrorException('Unimplemented type of ES query: '
                . $type);
        }

        $this->operations[] = [
            'type' => $type,
            'parameters' => $parameters,
            'is_leaf_operation' => $as_leaf_of_aggregation_tree,
        ];

        // Operations on data
        if ($type == self::HISTOGRAM) {
            $name   = 'histogram_'.$parameters['field'].'_'.$parameters['interval'];
            $params = [
                'histogram' => [
                    'field'         => $this->renameField( $parameters['field'] ),
                    'interval'      => $parameters['interval'],
                    // 'min_doc_count' => 1,
                ],
            ];
        }
        elseif ($type == self::COUNT) {
            // COUNT is calculated as a simple SEARCH ES query to enable
            // aggregations
            return $this;
        }
        elseif ($type == self::CUSTOM) {
            $name   = 'calculation_custom_'
                    . $parameters['field']
                    . '_'.hash('md4', serialize($parameters['specific_filters']));
            $params = [
                'filters' => $parameters['specific_filters'],
            ];
        }
        elseif ($type == self::INLINE) {
            if ( ! $as_leaf_of_aggregation_tree) {
                throw new \InvalidArgumentException(
                    "Inline operations implemented only as leaf operations"
                );
            }

            foreach ($parameters as $operation_name => $inline_aggregation_parameters) {
                $this->queued_leaf_operations['inline_'.$operation_name] = $inline_aggregation_parameters;
            }

            return $this;
        }
        elseif ($type == self::SCRIPT) {
            $es_aggregation_type = 'script';
            $name = 'script_'.$parameters['field'];

            $parameters['field'] = $this->renameField( $parameters['field'] );

            if (empty($parameters['field'])) {
                throw new \InvalidArgumentException(
                    "Missing 'field' in parameters for a SCRIPT operation aggregation: \n"
                    .var_export($parameters, true)
                );
            }

            if (empty($parameters['script'])) {
                throw new \InvalidArgumentException(
                    "Missing 'script' in parameters for a SCRIPT operation aggregation: \n"
                    .var_export($parameters, true)
                );
            }

            $params = [
                'terms' => $parameters,
            ];

            $change_aggregation_level = true;
        }
        else {

            if ($type == self::AVERAGE) {
                $es_aggregation_type = 'avg';
            }
            elseif ($type == self::MIN) {
                $es_aggregation_type = 'min';
            }
            elseif ($type == self::MAX) {
                $es_aggregation_type = 'max';
            }
            elseif ($type == self::SUM) {
                $es_aggregation_type = 'sum';
            }
            elseif ($type == self::EXTENDED_STATS ) {
                $es_aggregation_type = 'extended_stats';
            }
            elseif ($type == self::CARDINALITY ) {
                $es_aggregation_type = 'cardinality';
                // $this->aggregate('calculation_'.$es_aggregation_type.'_'.$parameters['field'], [
                    // $es_aggregation_type => [
                        // 'field' => $this->renameField( $parameters['field'] ),
                        // 'precision_threshold' => 40000,
                    // ],
                // ], false);

                // return;
            }
            elseif ($type == self::PERCENTILES) {
                $es_aggregation_type = 'percentiles';
            }
            else {
                throw new \ErrorException("Queries of type {$type} not implemented.");
            }

            if (is_array($parameters['field'])) {
                if (empty($parameters['field']['field'])) {
                    throw new \InvalidArgumentException(
                        "Please provide a 'field' te generate a column name for "
                        ."your operation result"
                    );
                }

                // future name of the generated field would like 'avg_<field>'
                $field = $parameters['field']['field'];
                // We remove the fake composit field entry
                $parameters = [
                    'script' => $parameters['field']['script'],
                ];
            }
            else {
                $field = $parameters['field'];
                $parameters['field'] = $this->renameField( $parameters['field'] );
            }

            $name   = 'calculation_'.$es_aggregation_type.'_'.$field;
            $params = [
                $es_aggregation_type => $parameters,
            ];
        }

        if ($as_leaf_of_aggregation_tree) {
            // we queue now to call aggregate at the end
            $this->queued_leaf_operations[$name] = $params;
        }
        else {
            $this->aggregate($name, $params, $change_aggregation_level);
        }

        return $this;
    }

    /**
     */
    public function getOperations()
    {
        return $this->operations;
    }

    /**
     * @return array The parameters of generated the ES query.
     */
    public function getSearchParams()
    {
        foreach ($this->group_by_aggregations_on_nested_fields as $name => $aggregation_parameters) {
            // If we have a nested aggregation, we need to add the leaf operations at the same level
            if (!empty($aggregation_parameters['nested']['path']) && !empty($this->queued_leaf_operations)) {
                foreach ($this->queued_leaf_operations as $operation_name => $operation_aggregation_parameters) {
                    $this->aggregate($operation_name, $operation_aggregation_parameters, false, false);
                }
            }
            $this->aggregate($name, $aggregation_parameters);
        }

        foreach ($this->queued_leaf_operations as $name => $aggregation_parameters) {
            $this->aggregate($name, $aggregation_parameters, false);
        }

        $params = [
            'index'              => $this->index_pattern,
            'ignore_unavailable' => true,                               // https://www.elastic.co/guide/en/elasticsearch/reference/current/multi-index.html#multi-index
            'body'               => [
                'query' => [
                    'constant_score' => [
                        'filter' => [
                        ]
                    ],
                ],
                'size'         => isset($_REQUEST['debug_es']) ? 3 : 0, // return only aggregation results (forget hits)
            ]
        ];

        if ($this->getRequestTimeout() !== null) {
            $params['body']['timeout'] = $this->getRequestTimeout();
        }

        if ($this->getAggregationsQueryPart() !== null) {
            $params['body']['aggregations'] = $this->getAggregationsQueryPart();
        }

        $params['body']['query']['constant_score']['filter']['bool']['must']
            = $this->filters;



        // if ($aggregations = $this->getAggregationsQueryPart()) {
            // $params['body']['aggregations'] = $aggregations;
        // }

        return $params;
    }

    /**
     * Execute this query.
     *
     * @param array $options Handles 'disable_cache' as bool
     *
     * @return ElasticSearch_Result
     */
    public function execute($client, array $options=[])
    {
        $params = $this->getSearchParams();

        // we disable the cache if the current day is between the ranges
        $cache_is_enabled = !isset($_REQUEST['es_nocache']) || Debug::get('disable_es_cache');
        // $cache_is_enabled = false;
        // $today = new \DateTime('today');
        // foreach ($this->dateRanges as $i => $dateRange) {
            // if ($dateRange['start'] <= $today && $dateRange['end'] >= $today)
                // $cache_is_enabled = false;
        // }

        // $profiler_token = Profiler::start('es', __METHOD__ . '::' . __LINE__);

        $cache_key = hash('crc32b', var_export($params, true)).'-es';

        if ($cache_is_enabled && empty($options['disable_cache'])) {
            if ($cached = \Cache::get($cache_key)) {
                $result = unserialize($cached);
            }
        }

        if (!isset($result)) {
            // $client = \ElasticSearch_Server::getClient();

            try {
                $result = $client->search($params);
            }
            catch (\Exception $e) {
                $es_response = json_decode($e->getMessage(), JSON_OBJECT_AS_ARRAY);

                if (isset($es_response['error']['caused_by']['reason'])) {
                    $reason = $es_response['error']['caused_by']['reason'];
                }
                elseif (isset($es_response['error']['root_cause'][0]['reason'])) {
                    $reason = $es_response['error']['root_cause'][0]['reason'];
                }
                else {
                    $reason = 'Unable to extract reason from es response';
                }

                throw new \RuntimeException(
                    get_class($e).": $reason\n"
                    .$e->getFile().' - '.$e->getLine()."\n\n"
                    .json_encode($es_response, JSON_PRETTY_PRINT)."\n\n"
                    ."Failing Request: \n"
                    .json_encode($params)."\n\n"
                );
            }

            \Cache::set(
                $cache_key,
                serialize($result),
                \MB::$config->load('elasticSearch.query_cache') ? : 60 * 5
            );
        }

        // Profiler::stop($profiler_token);

        // echo json_encode([
            // 'method' => __FILE__ . ' ' . __LINE__,
            // 'file'   => __METHOD__,
            // 'stats'  => Profiler::stats([$profiler_token]),
        // ]);

        // return new ElasticSearch_Result($result);
        return new ElasticSearchResult($result);
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

    /**
     * Support json_encode
     * @see https://secure.php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize()
    {
        return $this->getSearchParams();
    }

    /**
     */
    public function setNestedFields(array $fields)
    {
        $this->nested_fields = $fields;
        return $this;
    }

    /**
     * Checks if a field is nested based on the list of nested fields
     *
     * @param  string The field to check
     * @param  string The variable to store the found nested field in
     *
     * @return bool
     */
    public function fieldIsNested($field, &$nesting_field_found=null)
    {
        if ($this->nested_fields) foreach ($this->nested_fields as $nested_field) {
            if (preg_match("#^".preg_quote($nested_field, '#')."#", $field)) {
                $nesting_field_found = $nested_field;
                return true;
            }
        }

        return false;
    }

    /**/
}
