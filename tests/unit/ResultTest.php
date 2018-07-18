<?php
namespace JClaveau\ElasticSearch;

use JClaveau\VisibilityViolator\VisibilityViolator;


/**
 */
class ResultTest extends \PHPUnit_Framework_TestCase
{
    public function test_jsonSerialize()
    {
        $result = new ElasticSearchResult(['fake_result']);
        
        $this->assertEquals(
            '["fake_result"]',
            json_encode($result)
        );
    }
    
    /**/
}
