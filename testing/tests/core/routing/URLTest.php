<?php
use Cx\Core\Routing\Url as Url;

include_once(ASCMS_TEST_PATH.'/testCases/ContrexxTestCase.php');

class URLTest extends \ContrexxTestCase {
    public function testDomainAndPath() {
        $url = new Url('http://example.com/');
        $this->assertEquals('http://example.com/', $url->getDomain());
        $this->assertEquals('', $url->getPath());

        $url = new Url('http://example.com/Test');
        $this->assertEquals('http://example.com/', $url->getDomain());
        $this->assertEquals('Test', $url->getPath());

        $url = new Url('http://example.com/Second/Test/?a=asfd');
        $this->assertEquals('http://example.com/', $url->getDomain());
        $this->assertEquals('Second/Test/?a=asfd', $url->getPath());

        $this->assertEquals(false, $url->isRouted());
    }

    public function testSuggestions() {
        $url = new Url('http://example.com/Test');
        $this->assertEquals('Test', $url->getSuggestedTargetPath());
        $this->assertEquals('', $url->getSuggestedParams());

        $url = new Url('http://example.com/Test?foo=bar');
        $this->assertEquals('Test', $url->getSuggestedTargetPath());
        $this->assertEquals('?foo=bar', $url->getSuggestedParams());
    }

    /**
     * @expectedException \Cx\Core\Routing\UrlException
     */
    public function testMalformedConstruction() {
        $url = new Url('htp://example.com/');
    }
    
}