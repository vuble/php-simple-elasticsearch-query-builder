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
     * @unit
     */
    public function test_getAsSqlResult()
    {
        $result = (new ElasticSearchResult(
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_BigAggregation.php'
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            $result,
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_As_Sql_BigAggregation.php'
        );
    }

    /**
     * @unit
     */
    public function test_getAsSqlResult_NestedAggregations()
    {
        $result = (new ElasticSearchResult(
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_Nested.php'
        ))
        ->getAsSqlResult()
        ;

        $this->assertEquals(
            $result,
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_As_Sql_Nested.php'
        );
    }

    /**/
}
