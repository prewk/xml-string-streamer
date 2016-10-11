<?php

namespace Prewk;

use PHPUnit_Framework_TestCase;
use Mockery;
use Prewk\XmlStringStreamer\Parser\StringWalker;
use Prewk\XmlStringStreamer\Parser\UniqueNode;
use Prewk\XmlStringStreamer\Stream\File;

class XmlStringStreamerIntegrationTest extends PHPUnit_Framework_TestCase
{
    public function test_incomplete_file_with_StringWalker()
    {
        $file = __dir__ . "/../../xml/incomplete.xml";

        $stream = new File($file, 16384);
        $parser = new StringWalker(array(
            "captureDepth" => 4,
        ));
        $streamer = new XmlStringStreamer($parser, $stream);

        $expectedValues = array("000000100182", "000000100182");
        $foundValues = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundValues[] = (string)$xmlNode->field[0]["value"];
        }

        $this->assertEquals($expectedValues, $foundValues, "It should only catch two values and abort");
    }

    public function test_incomplete_file_with_UniqueNode()
    {
        $file = __dir__ . "/../../xml/incomplete.xml";

        $stream = new File($file, 16384);
        $parser = new UniqueNode(array(
            "uniqueNode" => "row",
        ));
        $streamer = new XmlStringStreamer($parser, $stream);

        $expectedValues = array("000000100182", "000000100182");
        $foundValues = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundValues[] = (string)$xmlNode->field[0]["value"];
        }

        $this->assertEquals($expectedValues, $foundValues, "It should only catch two values and abort");
    }

    public function test_createStringWalkerParser_convenience_method_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringStreamer::createStringWalkerParser($file);

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }
        
        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected");
    }

    public function test_StringWalker_parser_with_pubmed_xml_and_container_extraction()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $stream = new File($file, 16384);
        $parser = new StringWalker(array(
            "extractContainer" => true,
        ));
        $streamer = new XmlStringStreamer($parser, $stream);

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }

        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected");

        $containerXml = simplexml_load_string($parser->getExtractedContainer());
        $this->assertEquals("PubmedArticleSet", $containerXml->getName(), "Root node should be as expected");
        $this->assertEquals("bar", $containerXml->attributes()->foo, "Attributes should be extracted correctly");
        $this->assertEquals("qux", $containerXml->attributes()->baz, "Attributes should be extracted correctly");
    }

    public function test_createStringWalkerParser_convenience_method_with_orphanet_xml_and_custom_captureDepth()
    {
        $file = __dir__ . "/../../xml/orphanet-xml-example.xml";

        $streamer = XmlStringStreamer::createStringWalkerParser($file, array(
            "captureDepth" => 3,
        ));

        $expectedOrphaNumbers = array("166024", "166032", "58");
        $foundOrphaNumbers = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundOrphaNumbers[] = (string)$xmlNode->OrphaNumber;
        }
        
        $this->assertEquals($expectedOrphaNumbers, $foundOrphaNumbers, "The OrphaNumber nodes should be as expected");
    }

    public function test_createUniqueNodeParser_convenience_method_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringStreamer::createUniqueNodeParser($file, array(
            "uniqueNode" => "PubmedArticle"
        ));

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }
        
        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected");
    }

    public function test_UniqueNode_parser_with_pubmed_xml_and_container_extraction()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $stream = new File($file, 512);
        $parser = new UniqueNode(array(
            "uniqueNode" => "PubmedArticle",
            "extractContainer" => true,
        ));
        $streamer = new XmlStringStreamer($parser, $stream);

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }

        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected");

        $containerXml = simplexml_load_string($parser->getExtractedContainer());
        $this->assertEquals("PubmedArticleSet", $containerXml->getName(), "Root node should be as expected");
        $this->assertEquals("bar", $containerXml->attributes()->foo, "Attributes should be extracted correctly");
        $this->assertEquals("qux", $containerXml->attributes()->baz, "Attributes should be extracted correctly");
    }

    public function test_createUniqueNodeParser_convenience_method_with_orphanet_xml()
    {
        $file = __dir__ . "/../../xml/orphanet-xml-example.xml";

        $streamer = XmlStringStreamer::createUniqueNodeParser($file, array(
            "uniqueNode" => "Disorder"
        ));

        $expectedOrphaNumbers = array("166024", "166032", "58");
        $foundOrphaNumbers = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundOrphaNumbers[] = (string)$xmlNode->OrphaNumber;
        }
        
        $this->assertEquals($expectedOrphaNumbers, $foundOrphaNumbers, "The OrphaNumber nodes should be as expected");
    }

    public function test_UniqueNode_parser_with_file_shorter_than_buffer()
    {
        $file = __dir__ . "/../../xml/short.xml";

        $stream = new XmlStringStreamer\Stream\File($file, 1024);
        $parser = new XmlStringStreamer\Parser\UniqueNode(array(
            "uniqueNode" => "capture"
        ));
        $streamer = new XmlStringStreamer($parser, $stream);

        $expectedNodes = array(
            "foo",
            "bar",
        );

        $foundNodes = array();
        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundNodes[] = (string)$xmlNode->node;
        }

        $this->assertEquals($expectedNodes, $foundNodes, "The found nodes should equal the expected nodes");
    }

    public function test_StringWalker_parser_with_file_shorter_than_buffer()
    {
        $file = __dir__ . "/../../xml/short.xml";

        $stream = new XmlStringStreamer\Stream\File($file, 1024);
        $parser = new XmlStringStreamer\Parser\StringWalker();
        $streamer = new XmlStringStreamer($parser, $stream);

        $expectedNodes = array(
            "foo",
            "bar",
        );

        $foundNodes = array();
        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundNodes[] = (string)$xmlNode->node;
        }

        $this->assertEquals($expectedNodes, $foundNodes, "The found nodes should equal the expected nodes");
    }

    public function test_UniqueNode_parser_with_file_with_data_in_last_chunk()
    {
        $file = __dir__ . "/../../xml/short_last_chunk.xml";

        $stream = new XmlStringStreamer\Stream\File($file, 200);
        $parser = $parser = new UniqueNode(array("uniqueNode" => 'capture'));
        $streamer = new XmlStringStreamer($parser, $stream);

        $foundNodes = 0;
        while ($node = $streamer->getNode()) {
            $foundNodes++;
        }

        $this->assertEquals(2, $foundNodes, "The found nodes should equal the expected nodes number.");
    }
}