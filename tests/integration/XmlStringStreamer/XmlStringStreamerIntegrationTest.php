<?php

namespace Prewk;

use PHPUnit\Framework\TestCase;
use Prewk\XmlStringStreamer\Parser\StringWalker;
use Prewk\XmlStringStreamer\Parser\UniqueNode;
use Prewk\XmlStringStreamer\Stream\File;

class XmlStringStreamerIntegrationTest extends TestCase
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

    public function test_UniqueNode_parser_reset_working_blob()
    {
        $file = __dir__ . "/../../xml/rewind_working_blob.xml";

        $stream = new XmlStringStreamer\Stream\File($file, 50);
        $parser = new UniqueNode(array("uniqueNode" => 'item'));
        $streamer = new XmlStringStreamer($parser, $stream);

        self::assertSame('<item>0</item>', $streamer->getNode());
        self::assertSame('<item>1</item>', $streamer->getNode());
        self::assertSame('<item>2</item>', $streamer->getNode());

        // at this stage, internal working blob in parser has "preloaded" one extra valid item
        self::assertSame("\n    <item>3</item>\n    <item>4</item", $parser->getCurrentWorkingBlob());

        $stream->rewind();
        // because internal working blob had one extra valid item, we still get it
        self::assertSame('<item>3</item>', $streamer->getNode());

        // now  next item will result into fetching previous working blob plus beginning of file after rewinding
        self::assertSame("<item>4</item<root>\n    <item>0</item>", $streamer->getNode());

        self::assertSame('<item>1</item>', $streamer->getNode());
        self::assertSame('<item>2</item>', $streamer->getNode());

        $stream->rewind(); // rewind stream again
        $parser->reset(); // but now also reset internal working blob

        self::assertSame('<item>0</item>', $streamer->getNode());
        self::assertSame('<item>1</item>', $streamer->getNode());
        self::assertSame('<item>2</item>', $streamer->getNode());

        self::assertSame("\n    <item>3</item>\n    <item>4</item", $parser->getCurrentWorkingBlob());
        $parser->reset();
        self::assertSame('', $parser->getCurrentWorkingBlob());

        // in opposite case, reseting blob without rewinding will jump over 2 items
        self::assertSame('<item>5</item>', $streamer->getNode());
    }

    public function test_StringWalker_parser_reset_working_blob()
    {
        $file = __dir__ . "/../../xml/rewind_working_blob.xml";

        $stream = new XmlStringStreamer\Stream\File($file, 80);
        $parser = new XmlStringStreamer\Parser\StringWalker();
        $streamer = new XmlStringStreamer($parser, $stream);

        self::assertSame("\n    <item>0</item>", $streamer->getNode());
        self::assertSame("\n    <item>1</item>", $streamer->getNode());
        self::assertSame("\n    <item>2</item>", $streamer->getNode());

        $stream->rewind();
        // after rewind, previous part of chunk with beginning of file is current node
        self::assertSame("\n    <item>3</ite<root>", $streamer->getNode());

        // but after that, we are able to get proper nodes (depends on chunk length)
        self::assertSame("\n    <item>0</item>", $streamer->getNode());
        self::assertSame("\n    <item>1</item>", $streamer->getNode());
        self::assertSame("\n    <item>2</item>", $streamer->getNode());

        $stream->rewind();
        $parser->reset();
        // now rewind and reset will cause proper loading from beginning of file
        self::assertSame("\n    <item>0</item>", $streamer->getNode());
        self::assertSame("\n    <item>1</item>", $streamer->getNode());
        self::assertSame("\n    <item>2</item>", $streamer->getNode());

        // in opposite, just resetting parser without rewinding will cause false as result - unable to retrieve
        $parser->reset();
        self::assertFalse($streamer->getNode());

        // it is possible to recover by rewind/reset again
        $stream->rewind();
        $parser->reset();
        self::assertSame("\n    <item>0</item>", $streamer->getNode());
    }
    
    public function test_UniqueNode_parser_stream_seeking()
    {
        $filePath = __dir__ . '/../../xml/stream_seeking.xml';
        $fileHandle = fopen($filePath, 'rb');
        
        $stream = new XmlStringStreamer\Stream\File($fileHandle, 50);
        $parser = new UniqueNode(["uniqueNode" => 'item']);
        $streamer = new XmlStringStreamer($parser, $stream);
        
        self::assertSame('<item>first item to read</item>', $streamer->getNode());
        
        /**
         * @see /tests/xml/stream_seeking.xml
         * hash character is used as seek target in file, creating case where closing tag precedes opening tag
         */
        $seekTargetPosition = strpos(file_get_contents($filePath), '#');
        fseek($fileHandle, $seekTargetPosition);
        $parser->reset();
        
        self::assertSame('<item>second item to read</item>', $streamer->getNode());
    }
}
