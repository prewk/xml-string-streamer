<?php

use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream;
use Prewk\XmlStringStreamer\Parser;

class StringWalkerParserTest extends PHPUnit_Framework_TestCase
{
    public function testConvenienceMethod()
    {
        $streamer = XmlStringStreamer::createStringWalkerParser(__dir__ . "/pubmed-example.xml");
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
