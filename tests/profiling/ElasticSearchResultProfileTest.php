<?php

namespace JClaveau\ElasticSearch;

class ElasticSearchResultProfileTest extends \AbstractTest
{
    use \JClaveau\PHPUnit\Framework\UsageConstraintTrait;

    /**
     * @profile
     * @coversNothing
     */
    public function test_profile_getAsSqlResult_big_aggregation()
    {
        $result = (new ElasticSearchResult(
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_BigAggregation.php'
        ))
        ->getAsSqlResult()
        ;

        // var_dump($this->getExecutionTime());
        $this->assertExecutionTimeBelow(0.2);
    }

    /**
     * @profile
     * @coversNothing
     */
    public function test_profile_getAsSqlResult_nested()
    {
        $result = (new ElasticSearchResult(
            require_once __DIR__.'/../data/ElasticSearchResultTest_ES_Result_Nested.php'
        ))
        ->getAsSqlResult()
        ;

        // var_dump($this->getExecutionTime());
        $this->assertExecutionTimeBelow(0.004);
    }

}