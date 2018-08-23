# PHP Simple ElasticSearch Query Builder

## Table of Contents

* [ElasticSearchQuery](#elasticsearchquery)
    * [__construct](#__construct)
    * [groupBy](#groupby)
    * [getAggregationsQueryPart](#getaggregationsquerypart)
    * [where](#where)
    * [openFilterLevel](#openfilterlevel)
    * [closeFilterLevel](#closefilterlevel)
    * [addFilter](#addfilter)
    * [should](#should)
    * [openShould](#openshould)
    * [closeShould](#closeshould)
    * [must](#must)
    * [openMust](#openmust)
    * [closeMust](#closemust)
    * [setIndex](#setindex)
    * [setFieldRenamer](#setfieldrenamer)
    * [addOperationAggregation](#addoperationaggregation)
    * [getSearchParams](#getsearchparams)
    * [execute](#execute)
    * [directExecute](#directexecute)
    * [jsonSerialize](#jsonserialize)
* [ElasticSearchResult](#elasticsearchresult)
    * [__construct](#__construct-1)
    * [getAsSqlResult](#getassqlresult)
    * [getHits](#gethits)
    * [getResults](#getresults)
    * [jsonSerialize](#jsonserialize-1)

## ElasticSearchQuery





* Full name: \JClaveau\ElasticSearch\ElasticSearchQuery
* This class implements: \JsonSerializable


### __construct



```php
ElasticSearchQuery::__construct(  )
```







---

### groupBy

groupBy corresponds to the most basic aggregation type.

```php
ElasticSearchQuery::groupBy(  $field_alias, array $aggregation_parameters = array() )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$field_alias` | **** |  |
| `$aggregation_parameters` | **array** |  |




---

### getAggregationsQueryPart



```php
ElasticSearchQuery::getAggregationsQueryPart(  )
```







---

### where



```php
ElasticSearchQuery::where(  $field,  $operator,  $values = null,  $or_missing = false )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$field` | **** |  |
| `$operator` | **** |  |
| `$values` | **** |  |
| `$or_missing` | **** |  |




---

### openFilterLevel

Opens a new level of filters

```php
ElasticSearchQuery::openFilterLevel( string $type,  $is_clause = false ): $this
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **string** | The type of filter group |
| `$is_clause` | **** |  |




---

### closeFilterLevel

Opens a new level of filters

```php
ElasticSearchQuery::closeFilterLevel(  ): $this
```







---

### addFilter



```php
ElasticSearchQuery::addFilter( array $filter_rule ): $this
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$filter_rule` | **array** |  |




---

### should



```php
ElasticSearchQuery::should( callable $or_option_definer_callback ): $this
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$or_option_definer_callback` | **callable** |  |




---

### openShould



```php
ElasticSearchQuery::openShould(  ): $this
```







---

### closeShould



```php
ElasticSearchQuery::closeShould(  ): $this
```







---

### must



```php
ElasticSearchQuery::must( callable $nested_actions ): $this
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$nested_actions` | **callable** |  |




---

### openMust



```php
ElasticSearchQuery::openMust(  ): $this
```







---

### closeMust



```php
ElasticSearchQuery::closeMust(  ): $this
```







---

### setIndex

Defines the indexes to look into

```php
ElasticSearchQuery::setIndex(  $index_pattern )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$index_pattern` | **** |  |




---

### setFieldRenamer

Defines a callback that will rename fields before the query

```php
ElasticSearchQuery::setFieldRenamer( callable $renamer = null )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$renamer` | **callable** |  |




---

### addOperationAggregation



```php
ElasticSearchQuery::addOperationAggregation(  $type, array $parameters = array(),  $as_leaf_of_aggregation_tree = true )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$type` | **** |  |
| `$parameters` | **array** |  |
| `$as_leaf_of_aggregation_tree` | **** |  |




---

### getSearchParams



```php
ElasticSearchQuery::getSearchParams(  ): array
```





**Return Value:**

The parameters of generated the ES query.



---

### execute

Execute this query.

```php
ElasticSearchQuery::execute(  $client, array $options = array() ): \JClaveau\ElasticSearch\ElasticSearch_Result
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$client` | **** |  |
| `$options` | **array** | Handles 'disable_cache' as bool |




---

### directExecute

Execute this query with body and index passed in params.

```php
ElasticSearchQuery::directExecute( string $index, array $body, array $options = array() ): \JClaveau\ElasticSearch\ElasticSearch_Result
```

TODO function replaced by search() with doctrine/es

* This method is **static**.
**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$index` | **string** |  |
| `$body` | **array** |  |
| `$options` | **array** |  |




---

### jsonSerialize

Support json_encode

```php
ElasticSearchQuery::jsonSerialize(  )
```






**See Also:**

* https://secure.php.net/manual/en/jsonserializable.jsonserialize.php 

---

## ElasticSearchResult





* Full name: \JClaveau\ElasticSearch\ElasticSearchResult
* This class implements: \JsonSerializable


### __construct



```php
ElasticSearchResult::__construct( array $elasticsearch_result )
```




**Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `$elasticsearch_result` | **array** |  |




---

### getAsSqlResult

Flattens the response of ES to provide an array looking like
an SQL result.

```php
ElasticSearchResult::getAsSqlResult(  ): array
```

To do that, nested aggregations must processed.





---

### getHits

Returns the hits from the search query

```php
ElasticSearchResult::getHits(  )
```







---

### getResults

Returns the results of the search query

```php
ElasticSearchResult::getResults(  )
```







---

### jsonSerialize



```php
ElasticSearchResult::jsonSerialize(  )
```






**See Also:**

* https://secure.php.net/manual/en/jsonserializable.jsonserialize.php 

---



--------
> This document was automatically generated from source code comments on 2018-08-07 using [phpDocumentor](http://www.phpdoc.org/) and [cvuorinen/phpdoc-markdown-public](https://github.com/cvuorinen/phpdoc-markdown-public)
