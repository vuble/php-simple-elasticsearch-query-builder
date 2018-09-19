<?php
/**
 *
 * 'aggregations' =>
 *   array (size=1)
 *     'group_by_date' =>
 *       array (size=1)
 *         'buckets' =>
 *           array (size=2)
 *             0 =>
 *               array (size=4)
 *                 'key_as_string' => string '2016-07' (length=7)
 *                 'key' => int 1467331200000
 *                 'doc_count' => int 3059831
 *                 'group_by_device' =>
 *                   array (size=3)
 *                     'doc_count_error_upper_bound' => int 0
 *                     'sum_other_doc_count' => int 176
 *                     'buckets' =>
 *                       array (size=10)
 *                         0 =>
 *                           array (size=2)
 *                             'key' => string 'desktop' (length=7)
 *                             'doc_count' => int 2179402
 *                         1 =>
 *                           array (size=2)
 *                             'key' => string 'tablet' (length=6)
 *                             'doc_count' => int 765825
 *                         2 =>
 *                           array (size=2)
 *                             'key' => string 'mobile' (length=6)
 *                             'doc_count' => int 97485
 *                         3 =>
 *                           array (size=2)
 *                             'key' => string 'phone' (length=5)
 *                             'doc_count' => int 97485
 *             1 =>
 *               array (size=4)
 *                 'key_as_string' => string '2016-08' (length=7)
 *                 'key' => int 1470009600000
 *                 'doc_count' => int 4123831
 *                 'group_by_device' =>
 *                   array (size=3)
 *                     'doc_count_error_upper_bound' => int 0
 *                     'sum_other_doc_count' => int 316
 *                     'buckets' =>
 *                       array (size=10)
 *                         0 =>
 *                           array (size=2)
 *                             'key' => string 'desktop' (length=7)
 *                             'doc_count' => int 3092445
 *                         1 =>
 *                           array (size=2)
 *                             'key' => string 'tablet' (length=6)
 *                             'doc_count' => int 903903
 *                         2 =>
 *                           array (size=2)
 *                             'key' => string 'mobile' (length=6)
 *                             'doc_count' => int 101265
 *                         3 =>
 *                           array (size=2)
 *                             'key' => string 'phone' (length=5)
 *                             'doc_count' => int 101265
 *
 */
namespace JClaveau\ElasticSearch;

class ElasticSearchResult implements \JsonSerializable
{
    protected $es_result;
    /** @var COUNT Name of the column containing the number of values in the group */
    const COUNT = 'total';

    /**
     *
     */
    public function __construct(array $elasticsearch_result)
    {
        $this->es_result = $elasticsearch_result;
    }

    /**
     * Flattens the response of ES to provide an array looking like
     * an SQL result.
     * To do that, nested aggregations must processed.
     *
     * @return array
     */
    public function getAsSqlResult()
    {
        if (!isset($this->es_result['aggregations'])) {
            $count = isset($this->es_result['hits']['total'])
                ? $this->es_result['hits']['total']
                : 0;

            return [];
        }

        return $this->simplifyAggregations( $this->es_result['aggregations'] );
    }

    /**
     * This method scans recursivelly the aggregation result and extract
     * the value from the leafs.
     * At the end it returns a row looking like an SQL one.
     *
     * @todo   The case with multiple group aggregations on non-existing
     *         fields is not handled.
     *
     * @return array
     */
    protected function simplifyAggregations(
        array $aggregation_node,
        array $previous_aggregation_values=[],
        array $parent=[],
        $last_nonnested_parent_doc_count=0
    ) {
        // Debug::dumpJson([
            // '$this->es_result'  => $this->es_result,
            // '$previous_aggregation_values'  => $previous_aggregation_values,
        // ], !true);

        $aggregation_node['parent'] = $parent;

        if (isset($parent['aggregation_type']) && $parent['aggregation_type'] == "nested" && !$last_nonnested_parent_doc_count) {
            // Grouping aggregations on nested fields produce a doc_count which is
            // the sum of every group by and will count duplicates. Example where
            // the nested doc_count 7751686 is bigger than it's non-nested parent
            // aggregation 214753:
            // "aggregations": {
            //   "group_by_event": {
            //     "doc_count_error_upper_bound": 0,
            //     "sum_other_doc_count": 0,
            //     "buckets": [
            //       {
            //         "key": "rtb_request",
            //         "doc_count": 214753,
            //         "group_by_publisher_id": {
            //           "doc_count_error_upper_bound": 0,
            //           "sum_other_doc_count": 0,
            //           "buckets": [
            //             {
            //               "key": 12,
            //               "doc_count": 214753,
            //               "nested_metadata.deals.deal_id": {
            //                 "doc_count": 7751686,
            //                 "filter_metadata.deals.deal_id": {
            //                   "doc_count": 7751686
            //                 }
            //               }
            //             }
            //           ]
            //         }
            //       }
            //     ]
            //   }
            // }
            // We're on our first nested aggregation. We get the last non nested one and get the doc_count.
            // If there isn't one, meaning our nested aggregation is the root aggregation, we get the hits.
            if ($last_nonnested_parent = $this->findLastNonNestedParentAggregation($parent)) {
                if (!isset($last_nonnested_parent['doc_count']) && !$last_nonnested_parent['parent']) {
                    // get the doc count from the hits
                    $last_nonnested_parent_doc_count = $this->es_result['hits']['total'];
                }
                else {
                    $last_nonnested_parent_doc_count = $last_nonnested_parent['doc_count'];
                }
            }
        }

        if (    $last_nonnested_parent_doc_count
            && (
                    !$aggregation_node['doc_count']
                ||  $last_nonnested_parent_doc_count < $aggregation_node['doc_count']
            )
        ) {
            $aggregation_node['doc_count'] = $last_nonnested_parent_doc_count;
        }

        $out = [];
        if ($group_by_key = $this->findGroupByKey($aggregation_node)) {

            // This case occures when a group aggregation is made on a
            // field that doesn't exist in ES.
            // TODO check if it still occures with the "missing" option enabled
            // TODO check if this behavior could be applied to all buckets
            //      aggregations or only the grouping ones.
            if (  !empty($aggregation_node['doc_count'])
                && empty($aggregation_node[$group_by_key['key']]['buckets'])) {

                $row_id = $this->doubleImplodeOfGroupedByValues(
                    $previous_aggregation_values
                );

                // We add a fake entry matching the group field and fill
                // it with null.
                // TODO : handle the case with multiple aggregations in
                //        this case.
                $previous_aggregation_values[$group_by_key['field']] = null;
                $previous_aggregation_values[self::COUNT] = $aggregation_node['doc_count'];

                $out[ $row_id ] = $previous_aggregation_values;
            }
            else {
                foreach ($aggregation_node[ $group_by_key['key'] ]['buckets'] as $i => $bucket) {

                    $key = $bucket[
                        isset($bucket['key_as_string']) ? 'key_as_string' : 'key'
                    ];

                    $field = $group_by_key['field'];

                    if ($key == ElasticSearchQuery::MISSING_AGGREGATION_FIELD)
                        $key = null;

                    $previous_aggregation_values[ $field ] = $key;
                    $aggregation_node['aggregation_type']  = $group_by_key['type'];

                    // not the last aggregation
                    $sub_rows = $this->simplifyAggregations(
                        $bucket,
                        $previous_aggregation_values,
                        $aggregation_node,
                        $last_nonnested_parent_doc_count
                    );
                    $out = array_merge_recursive($out, $sub_rows);
                }
            }

        }
        elseif (
                ($skipable_aggregation_infos = $this->findNestedKey($aggregation_node))
            ||  ($skipable_aggregation_infos = $this->findFilterKey($aggregation_node))
        ) {
            // Nested aggregations and filter aggregations have no impact
            // on grouping process as in the following example:
            // "aggregations": {
            //     "group_by_event": {
            //         "doc_count_error_upper_bound": 0,
            //         "sum_other_doc_count": 0,
            //         "buckets": [
            //             {
            //                 "key": "rtb_bid",
            //                 "doc_count": 16,
            //                 "group_by_publisher_id": {
            //                     "doc_count_error_upper_bound": 0,
            //                     "sum_other_doc_count": 0,
            //                     "buckets": [
            //                         {
            //                             "key": 529,
            //                             "doc_count": 16,
            //                             "nested_metadata.deals.deal_id": {
            //                                 "doc_count": 16,
            //                                 "filter_metadata.deals.deal_id": {
            //                                     "doc_count": 16,
            //                                     "group_by_deals.deal_id": {
            //                                         "doc_count_error_upper_bound": 0,
            //                                         "sum_other_doc_count": 0,
            //                                         "buckets": [
            //                                             {
            //                                                 "key": "MDB-18-03-19-37569",
            //                                                 "doc_count": 16
            //                                             }
            //                                         ]
            //                                     }
            //                                 }
            //                             }
            //                         }
            //                     ]
            //                 }
            //             }
            //         ]
            //     }
            // }
            $aggregation_node['aggregation_type'] = $skipable_aggregation_infos['type'];

            $sub_rows = $this->simplifyAggregations(
                $aggregation_node[ $skipable_aggregation_infos['key'] ],
                $previous_aggregation_values,
                $aggregation_node,
                $last_nonnested_parent_doc_count
            );

            $out = array_merge_recursive($out, $sub_rows);
        }
        elseif ($operation_key = $this->findCalculationKey($aggregation_node)) {
            // extract values from operation aggregations
            $row_id = $this->doubleImplodeOfGroupedByValues(
                $previous_aggregation_values
            );

            $out[ $row_id ] = $previous_aggregation_values;
            $out[ $row_id ][
                $operation_key['type'] . '_' . $operation_key['field']
            ] = $operation_key['value'];

            if (isset($aggregation_node['doc_count'])) {
                $out[ $row_id ][self::COUNT] = $aggregation_node['doc_count'];
            }
            else {
                // When an aggregagtion (sum, avg) is not nested into an
                // other one, we need to use the global count stored in
                // the hits entry of the result.
                $out[ $row_id ][self::COUNT] = $this->es_result['hits']['total'];
            }
        }
        elseif ($operation_key = $this->findHistogramKey($aggregation_node)) {
            // extract values from operation aggregations
            $row_id = $this->doubleImplodeOfGroupedByValues(
                $previous_aggregation_values
            );

            $out[ $row_id ] = $previous_aggregation_values;
            $out[ $row_id ][
                $operation_key['type'].'_'.$operation_key['field']
            ] = $operation_key['value'];


            if (isset($aggregation_node['doc_count'])) {
                $out[ $row_id ][self::COUNT] = $aggregation_node['doc_count'];
            }
            else {
                $out[ $row_id ][self::COUNT] = array_sum($operation_key['value']);
            }
        }
        else {
            // generate row_id
            $row_id = $this->doubleImplodeOfGroupedByValues(
                $previous_aggregation_values
            );

            $previous_aggregation_values[self::COUNT] = $aggregation_node['doc_count'];
            $out[ $row_id ] = $previous_aggregation_values;
        }

        return $out;
    }

    /**
     * Grouping aggregations on nested fields can produce more doc_count than
     * the initial count. To avoid this, we need to go back to the last
     * "non child or grand child of a nested" parent aggregation.
     *
     * @example: doc_count of "group by publisher" is lower than "group by deals.deal_id"
     *     "group_by_publisher_id": {
     *         "doc_count_error_upper_bound": 0,
     *         "sum_other_doc_count": 0,
     *         "buckets": [
     *             {
     *                 "key": 836,
     *                 "doc_count": 355719,
     *                 "nested_metadata.deals.deal_id": {
     *                     "doc_count": 2135896,
     *                     "filter_metadata.deals.deal_id": {
     *                         "doc_count": 2135896,
     *                         "group_by_deals.deal_id": {
     *                             "doc_count_error_upper_bound": 0,
     *                             "sum_other_doc_count": 0,
     *                             "buckets": [
     *                                 {
     *                                     "key": "MDB-18-01-08-66113",
     *                                     "doc_count": 355715
     *                                 },
     *                                 {
     *                                     "key": "MDB-18-01-08-66439",
     *                                     "doc_count": 355715
     *                                 },
     *                                 ...
     *                             ]
     *                         }
     *                     }
     *                 }
     *             }
     *         ]
     *     }
     *
     * @return array The last non nested of this branch of the tree
     */
    protected function findLastNonNestedParentAggregation($group_by_aggregation)
    {
        // loop back to root
        $non_nested_aggragations = $group_by_aggregation;
        $parent = $group_by_aggregation;
        while (!empty($parent['parent'])) {
            // The aggregation type of the current node is saved on the parent
            if ($parent['parent']['aggregation_type'] == 'nested') {
                // we flush all the kept aggregations as they are under a nested one.
                $non_nested_aggragations = false;
            }
            elseif (empty($non_nested_aggragations)) {
                $non_nested_aggragations = $parent;
            }

            $parent = $parent['parent'];
        }

        return $non_nested_aggragations;
    }

    /**
     * Checks if an aggregation is a group_by one.
     *
     * @param  array $aggregation_bucket
     * @return array [
     *     'key'   => $key,
     *     'field' => $result[1],
     *     'type'  => 'group_by',
     * ]
     */
    protected function findGroupByKey(array $aggregation_bucket)
    {
        foreach ($aggregation_bucket as $key => $value) {

            if (!is_array($value))
                continue;

            if (preg_match('/^group_by_(.*)$/', $key, $result)) {
                return [
                    'key'   => $key,
                    'field' => $result[1],
                    'type'  => 'group_by',
                ];
            }
        }

        return null;
    }

    /**
     * Checks if an aggregation is a group_by one.
     */
    protected function findFilterKey(array $aggregation_bucket)
    {
        foreach ($aggregation_bucket as $key => $value) {
            if (!is_array($value))
                continue;

            if (preg_match('/^filter_(.*)$/', $key, $result)) {
                return [
                    'key'   => $key,
                    'type'  => 'filter',
                    'field' => $result[1],
                ];
            }
        }

        return null;
    }

    /**
     * Checks if an aggregation is a group_by one.
     */
    protected function findNestedKey(array $aggregation_bucket)
    {
        foreach ($aggregation_bucket as $key => $value) {
            if (!is_array($value))
                continue;

            if (preg_match('/^nested_(.*)$/', $key, $result)) {
                return [
                    'key'   => $key,
                    'type'  => 'nested',
                    'field' => $result[1],
                ];
            }
        }

        return null;
    }

    /**
     * Checks if an aggregation is a calculation one. and extracts its value
     */
    protected function findCalculationKey(array $aggregation_bucket)
    {
        foreach ($aggregation_bucket as $key => $value) {

            if (!is_array($value))
                continue;

            if (preg_match('/^calculation_(extended_stats|[^_]+)_(.+)$/', $key, $result)) {

                if (in_array($result[1], ['extended_stats', 'custom'])) {
                    $value = $aggregation_bucket[$key];
                } elseif (isset($aggregation_bucket[$key]['value'])) {
                    $value = $aggregation_bucket[$key]['value'];
                } else {
                    $value = $aggregation_bucket[$key]['values'];
                }

                return [
                    'key'   => $key,
                    'type'  => $result[1],
                    'field' => $result[2],
                    'value' => $value,
                ];
            }
        }

        return null;
    }

    /**
     */
    protected function findHistogramKey(array $aggregation_bucket)
    {
        foreach ($aggregation_bucket as $key => $value) {

            if (!is_array($value))
                continue;

            if (preg_match('/^histogram_(.+)_([^_]+)$/', $key, $result)) {

                $values = [];
                foreach ($aggregation_bucket[$key]['buckets'] as $bucket) {
                    $values[$bucket['key']] = $bucket['doc_count'];
                }

                return [
                    'key'   => $key,
                    'field' => $result[1],
                    'type'  => 'histogram_'.$result[2],
                    'value' => $values,
                ];
            }
        }

        return null;
    }

    /**
     */
    protected function doubleImplodeOfGroupedByValues(array $aggregation_values)
    {
        $out = [];
        foreach ($aggregation_values as $field => $value)
            $out[] = $field . ':' . $value;

        return implode('-', $out);
    }

    /**
     * Returns the hits from the search query
     */
    public function getHits()
    {
        return !$this->es_result ? null : $this->es_result['hits'];
    }

    /**
     * Returns the results of the search query
     */
    public function getResults()
    {
        return !$this->es_result ? null : $this->es_result;
    }

    /**
     * @see https://secure.php.net/manual/en/jsonserializable.jsonserialize.php
     */
    public function jsonSerialize() {
        return $this->getResults();
    }

    /**/
}
