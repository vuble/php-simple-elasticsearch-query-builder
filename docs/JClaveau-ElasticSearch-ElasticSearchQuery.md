JClaveau\ElasticSearch\ElasticSearchQuery
===============






* Class name: ElasticSearchQuery
* Namespace: JClaveau\ElasticSearch



Constants
----------


### COUNT

    const COUNT = 'count'





### AVERAGE

    const AVERAGE = 'average'





### MAX

    const MAX = 'max'





### MIN

    const MIN = 'min'





### SUM

    const SUM = 'sum'





### CARDINALITY

    const CARDINALITY = 'cardinality'





### PERCENTILES

    const PERCENTILES = 'percentiles'





### PERCENTILES_RANKS

    const PERCENTILES_RANKS = 'percentiles_ranks'





### STATS

    const STATS = 'stats'





### EXTENDED_STATS

    const EXTENDED_STATS = 'extended_stats'





### GEO_BOUNDS

    const GEO_BOUNDS = 'geo_bounds'





### GEO_CENTROID

    const GEO_CENTROID = 'geo_centroid'





### VALUE_COUNT

    const VALUE_COUNT = 'value_count'





### SCRIPTED

    const SCRIPTED = 'scripted'





### HISTOGRAM

    const HISTOGRAM = 'histogram'





### CUSTOM

    const CUSTOM = 'custom'





### MISSING_AGGREGATION_FIELD

    const MISSING_AGGREGATION_FIELD = -1





Properties
----------


### $type

    protected  $type





* Visibility: **protected**


### $parameters

    protected mixed $parameters = array()





* Visibility: **protected**


### $aggregations

    protected mixed $aggregations





* Visibility: **protected**


### $current_aggregation

    protected mixed $current_aggregation = array()





* Visibility: **protected**


### $filters

    protected mixed $filters





* Visibility: **protected**


### $current_filters_level

    protected mixed $current_filters_level = array()





* Visibility: **protected**


### $index_pattern

    protected mixed $index_pattern





* Visibility: **protected**


### $dateRanges

    protected mixed $dateRanges = array()





* Visibility: **protected**


### $nested_fields

    protected mixed $nested_fields = array()





* Visibility: **protected**


### $aggregationNames

    protected mixed $aggregationNames = array()





* Visibility: **protected**


Methods
-------


### __construct

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::__construct($query_type, $parameters)





* Visibility: **public**


#### Arguments
* $query_type **mixed**
* $parameters **mixed**



### supportedQueryTypes

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::supportedQueryTypes()





* Visibility: **protected**




### groupBy

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::groupBy($field_alias, array $aggregation_parameters)

groupBy corresponds to the most basic aggregation type.



* Visibility: **public**


#### Arguments
* $field_alias **mixed**
* $aggregation_parameters **array**



### aggregate

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::aggregate($field_alias, array $aggregation_parameters)





* Visibility: **private**


#### Arguments
* $field_alias **mixed**
* $aggregation_parameters **array**



### getAggregationsQueryPart

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::getAggregationsQueryPart()





* Visibility: **public**




### wrapFilterIfNested

    array JClaveau\ElasticSearch\ElasticSearchQuery::wrapFilterIfNested(string $field, array $filter)

Add the nested wrapper required to filter on njested fields.



* Visibility: **protected**


#### Arguments
* $field **string**
* $filter **array**



### where

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::where($field, $operator, $values, $or_missing)





* Visibility: **public**


#### Arguments
* $field **mixed**
* $operator **mixed**
* $values **mixed**
* $or_missing **mixed**



### openFilterLevel

    \JClaveau\ElasticSearch\ElasticSearchQuery JClaveau\ElasticSearch\ElasticSearchQuery::openFilterLevel(string $type, callable $nested_actions, $is_clause)

Opens a new level of filters



* Visibility: **protected**


#### Arguments
* $type **string** - &lt;p&gt;The type of filter group&lt;/p&gt;
* $nested_actions **callable** - &lt;p&gt;The modifier of the query before
                                the group is closed.&lt;/p&gt;
* $is_clause **mixed**



### addFilter

    \JClaveau\ElasticSearch\ElasticSearchQuery JClaveau\ElasticSearch\ElasticSearchQuery::addFilter(array $filter_rule)





* Visibility: **protected**


#### Arguments
* $filter_rule **array**



### orWhere

    \JClaveau\ElasticSearch\ElasticSearchQuery JClaveau\ElasticSearch\ElasticSearchQuery::orWhere(callable $or_option_definer_callback)





* Visibility: **public**


#### Arguments
* $or_option_definer_callback **callable**



### setIndex

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::setIndex($index_pattern)

Defines the indexes to look into



* Visibility: **public**


#### Arguments
* $index_pattern **mixed**



### addOperationAggregation

    mixed JClaveau\ElasticSearch\ElasticSearchQuery::addOperationAggregation()





* Visibility: **protected**




### getSearchParams

    array JClaveau\ElasticSearch\ElasticSearchQuery::getSearchParams()





* Visibility: **public**




### execute

    \JClaveau\ElasticSearch\ElasticSearch_Result JClaveau\ElasticSearch\ElasticSearchQuery::execute(array $options)

Execute this query.



* Visibility: **public**


#### Arguments
* $options **array** - &lt;p&gt;Handles &#039;disable_cache&#039; as bool&lt;/p&gt;



### directExecute

    \JClaveau\ElasticSearch\ElasticSearch_Result JClaveau\ElasticSearch\ElasticSearchQuery::directExecute(string $index, array $body, array $options)

Execute this query with body and index passed in params.

TODO function replaced by search() with doctrine/es

* Visibility: **public**
* This method is **static**.


#### Arguments
* $index **string**
* $body **array**
* $options **array**


