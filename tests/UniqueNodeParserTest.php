<?php

use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream;
use Prewk\XmlStringStreamer\Parser;

class UniqueNodeParserTest extends PHPUnit_Framework_TestCase
{
    public function testLargeSimpleXml()
    {
        $nodeNo = 100000;

        $simpleBlueprint = simplexml_load_file(__dir__ . "/simpleBlueprint.xml");
        $xmlFaker = new \Prewk\XmlFaker($simpleBlueprint);

        $tmpFile = tempnam("/tmp", "xml-string-streamer-test");

        $xmlFaker->asFile($tmpFile, \Prewk\XmlFaker::NODE_COUNT_RESTRICTION_MODE, $nodeNo);

        $memoryUsageBefore = memory_get_usage(true);
        $streamProvider = new Stream\File($tmpFile, 100);

        $counter = 0;
        $parser = new Parser\UniqueNode(array(
            "uniqueNode" => "node",
        ));
        $streamer = new XmlStringStreamer($parser, $streamProvider);

        while ($node = $streamer->getNode()) {
            $counter++;
        }

        $memoryUsageAfter = memory_get_usage(true);

        $this->assertEquals($nodeNo, $counter, "There should be exactly $nodeNo nodes captured");
        $this->assertLessThan(500 * 1024, $memoryUsageAfter - $memoryUsageBefore, "Memory usage should not go higher than 500 KiB");

        unlink($tmpFile);
    }
}
