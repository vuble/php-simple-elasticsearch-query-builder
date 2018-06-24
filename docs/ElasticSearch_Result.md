ElasticSearch_Result
===============

&#039;aggregations&#039; =&gt;
  array (size=1)
    &#039;group_by_date&#039; =&gt;
      array (size=1)
        &#039;buckets&#039; =&gt;
          array (size=2)
            0 =&gt;
              array (size=4)
                &#039;key_as_string&#039; =&gt; string &#039;2016-07&#039; (length=7)
                &#039;key&#039; =&gt; int 1467331200000
                &#039;doc_count&#039; =&gt; int 3059831
                &#039;group_by_device&#039; =&gt;
                  array (size=3)
                    &#039;doc_count_error_upper_bound&#039; =&gt; int 0
                    &#039;sum_other_doc_count&#039; =&gt; int 176
                    &#039;buckets&#039; =&gt;
                      array (size=10)
                        0 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;desktop&#039; (length=7)
                            &#039;doc_count&#039; =&gt; int 2179402
                        1 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;tablet&#039; (length=6)
                            &#039;doc_count&#039; =&gt; int 765825
                        2 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;mobile&#039; (length=6)
                            &#039;doc_count&#039; =&gt; int 97485
                        3 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;phone&#039; (length=5)
                            &#039;doc_count&#039; =&gt; int 97485
            1 =&gt;
              array (size=4)
                &#039;key_as_string&#039; =&gt; string &#039;2016-08&#039; (length=7)
                &#039;key&#039; =&gt; int 1470009600000
                &#039;doc_count&#039; =&gt; int 4123831
                &#039;group_by_device&#039; =&gt;
                  array (size=3)
                    &#039;doc_count_error_upper_bound&#039; =&gt; int 0
                    &#039;sum_other_doc_count&#039; =&gt; int 316
                    &#039;buckets&#039; =&gt;
                      array (size=10)
                        0 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;desktop&#039; (length=7)
                            &#039;doc_count&#039; =&gt; int 3092445
                        1 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;tablet&#039; (length=6)
                            &#039;doc_count&#039; =&gt; int 903903
                        2 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;mobile&#039; (length=6)
                            &#039;doc_count&#039; =&gt; int 101265
                        3 =&gt;
                          array (size=2)
                            &#039;key&#039; =&gt; string &#039;phone&#039; (length=5)
                            &#039;doc_count&#039; =&gt; int 101265




* Class name: ElasticSearch_Result
* Namespace: 



Constants
----------


### COUNT

    const COUNT = 'total'





Properties
----------


### $es_result

    protected mixed $es_result





* Visibility: **protected**


Methods
-------


### __construct

    mixed ElasticSearch_Result::__construct(array $elasticsearch_result)





* Visibility: **public**


#### Arguments
* $elasticsearch_result **array**



### getAsSqlResult

    array ElasticSearch_Result::getAsSqlResult()

Flattens the response of ES to provide an array looking like
an SQL result.

To do that, nested aggregations must processed.

* Visibility: **public**




### simplifyAggregations

    array ElasticSearch_Result::simplifyAggregations(array $aggregation_node, array $previous_aggregation_values, array $parent)

This method scans recursivelly the aggregation result and extract
the value from the leafs.

At the end it returns a row looking like an SQL one.

* Visibility: **protected**


#### Arguments
* $aggregation_node **array**
* $previous_aggregation_values **array**
* $parent **array**



### findLastNonNestedParentAggregation

    array ElasticSearch_Result::findLastNonNestedParentAggregation($group_by_aggregation)

Grouping aggregations on nested fields can produce more doc_count than
the initial count. To avoid this, we need to go back to the last
"non child or grand child of a nested" parent aggregation.



* Visibility: **protected**


#### Arguments
* $group_by_aggregation **mixed**



### findGroupByKey

    array ElasticSearch_Result::findGroupByKey(array $aggregation_bucket)

Checks if an aggregation is a group_by one.



* Visibility: **protected**


#### Arguments
* $aggregation_bucket **array**



### findFilterKey

    mixed ElasticSearch_Result::findFilterKey(array $aggregation_bucket)

Checks if an aggregation is a group_by one.



* Visibility: **protected**


#### Arguments
* $aggregation_bucket **array**



### findNestedKey

    mixed ElasticSearch_Result::findNestedKey(array $aggregation_bucket)

Checks if an aggregation is a group_by one.



* Visibility: **protected**


#### Arguments
* $aggregation_bucket **array**



### findCalculationKey

    mixed ElasticSearch_Result::findCalculationKey(array $aggregation_bucket)

Checks if an aggregation is a calculation one. and extracts its value



* Visibility: **protected**


#### Arguments
* $aggregation_bucket **array**



### findHistogramKey

    mixed ElasticSearch_Result::findHistogramKey(array $aggregation_bucket)





* Visibility: **protected**


#### Arguments
* $aggregation_bucket **array**



### doubleImplodeOfGroupedByValues

    mixed ElasticSearch_Result::doubleImplodeOfGroupedByValues(array $aggregation_values)





* Visibility: **protected**


#### Arguments
* $aggregation_values **array**



### getHits

    mixed ElasticSearch_Result::getHits()

Returns the hits from the search query



* Visibility: **public**




### getResults

    mixed ElasticSearch_Result::getResults()

Returns the results of the search query



* Visibility: **public**



