<?php

use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream;
use Prewk\XmlStringStreamer\Parser;

class UniqueNodeParserTest extends PHPUnit_Framework_TestCase
{
    public function testLargeSimpleXml()
    {
        $nodeNo = 10000;

        $simpleBlueprint = simplexml_load_file(__dir__ . "/simpleBlueprint.xml");
        $xmlFaker = new \Prewk\XmlFaker($simpleBlueprint);

        $tmpFile = tempnam("/tmp", "xml-string-streamer-test");

        $xmlFaker->asFile($tmpFile, \Prewk\XmlFaker::NODE_COUNT_RESTRICTION_MODE, $nodeNo);
        $memoryUsageBefore = memory_get_usage(true);
        $provider = new Stream\File($tmpFile, 100);

        $counter = 0;
        $parser = new Parser\UniqueNode(array(
            "uniqueNode" => "node",
        ));
        $streamer = new XmlStringStreamer($parser, $provider);

        while ($node = $streamer->getNode()) {
            $counter++;
        }

        $memoryUsageAfter = memory_get_usage(true);

        $this->assertEquals($nodeNo, $counter, "There should be exactly $nodeNo nodes captured");
        $this->assertLessThan(500 * 1024, $memoryUsageAfter - $memoryUsageBefore, "Memory usage should not go higher than 500 KiB");

        unlink($tmpFile);
    }

    public function testPubmedXml()
    {
        $provider = new Stream\File(__dir__ . "/pubmed-example.xml", 100);

        $counter = 0;
        $parser = new Parser\UniqueNode(array(
            "uniqueNode" => "PubmedArticle",
        ));
        $streamer = new XmlStringStreamer($parser, $provider);

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;

            $counter++;
        }

        $this->assertEquals(3, $counter, "There should be exactly 3 nodes captured");
        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected");
    }

    public function testConvenienceMethod()
    {
        $streamer = XmlStringStreamer::createUniqueNodeParser(__dir__ . "/pubmed-example.xml", array(
            "uniqueNode" => "PubmedArticle",
        ));
        $counter = 0;

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;

            $counter++;
        }

        $this->assertEquals(3, $counter, "There should be exactly 3 nodes captured");
        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected");        
    }
}
