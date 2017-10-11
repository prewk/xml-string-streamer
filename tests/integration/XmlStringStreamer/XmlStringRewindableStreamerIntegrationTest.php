<?php

namespace Prewk;

use PHPUnit_Framework_TestCase;
use Mockery;
use Prewk\XmlStringStreamer\Parser\StringWalker;
use Prewk\XmlStringStreamer\Parser\UniqueNode;
use Prewk\XmlStringStreamer\Stream\File;

class XmlStringRewindableStreamerIntegrationTest extends PHPUnit_Framework_TestCase
{

    public function test_setRewindPoint_removed_buffer_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringRewindableStreamer::createStringWalkerParser($file);
        $streamer->setRewindPoint();

        $expectedPMIDs = array();
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
           $streamer->setRewindPoint();
        }

        $streamer->rewind();

        while ($node = $streamer->getNode()) {
            $foundPMIDs[] = $node;
        }

        $this->assertEquals($expectedPMIDs, $foundPMIDs, "no node should have been found after rewind");
    }

    public function test_removeRewindPoint_removed_buffer_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringRewindableStreamer::createStringWalkerParser($file);
        $streamer->setRewindPoint();

        $expectedPMIDs = array();
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
           // do nothin
        }

        $streamer->removeRewindPoint();
        $streamer->rewind();

        while ($node = $streamer->getNode()) {
            $foundPMIDs[] = $node;
        }

        $this->assertEquals($expectedPMIDs, $foundPMIDs, "no node should have been found after rewind");
    }

    public function test_rewind_method_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringRewindableStreamer::createStringWalkerParser($file);
        $streamer->setRewindPoint();

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }
        
        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected before rewind");

        $streamer->rewind();
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }

        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected after rewind");
    }

    public function test_multiple_rewind_method_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringRewindableStreamer::createStringWalkerParser($file);
        $streamer->setRewindPoint();

        $expectedPMIDs = array("24531174", "24529294", "24449586");
        $foundPMIDs = array();

        $node = $streamer->getNode();
        $streamer->rewind();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }
        
        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected after first rewind");

        $streamer->rewind();
        $foundPMIDs = array();

        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundPMIDs[] = (string)$xmlNode->MedlineCitation->PMID;
        }

        $this->assertEquals($expectedPMIDs, $foundPMIDs, "The PMID nodes should be as expected after second rewind");
    }

    public function test_setRewindPoint_after_finishing_iterating_with_orphanet_xml()
    {
        $file = __dir__ . "/../../xml/orphanet-xml-example.xml";

        $streamer = XmlStringRewindableStreamer::createUniqueNodeParser($file, array(
            "uniqueNode" => "Disorder"
        ));
        $streamer->setRewindPoint();

        while ($node = $streamer->getNode()) {
            // just iterate till the end
        }    

        $expectedOrphaNumbers = array("166024", "166032", "58");
        $foundOrphaNumbers = array();

        $streamer->rewind();
        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundOrphaNumbers[] = (string)$xmlNode->OrphaNumber;
        }
        
        $this->assertEquals($expectedOrphaNumbers, $foundOrphaNumbers, "The OrphaNumber nodes should be as expected");

        $expectedOrphaNumbers = array();
        $foundOrphaNumbers = array();

        $streamer->setRewindPoint();
        while ($node = $streamer->getNode()) {
            $foundOrphaNumbers[] = $node;
        }
        
        $this->assertEquals($expectedOrphaNumbers, $foundOrphaNumbers, "no more nodes should have been found");
    }

    public function test_setRewindPoint_after_first_node_with_pubmed_xml()
    {
        $file = __dir__ . "/../../xml/pubmed-example.xml";

        $streamer = XmlStringRewindableStreamer::createStringWalkerParser($file);

        $expectedNodes = array("24529294", "24449586");

        $oldNode = null;

        while ( $node = $streamer->getNode()){            
            if( $oldNode == null && $node ) {
                $streamer->setRewindPoint();
            }
            $oldNode = $node;
        };

        $foundNodes = array();
        $streamer->rewind();
        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundNodes[] = (string)$xmlNode->MedlineCitation->PMID;
        }

        $this->assertEquals($expectedNodes, $foundNodes, "the nodes should equal the expected nodes");
    }

    public function test_set_and_rewind_with_file_shorter_than_buffer()
    {
        $file = __dir__ . "/../../xml/short.xml";

        $stream = new XmlStringStreamer\Stream\File($file, 1024);
        $parser = new XmlStringStreamer\Parser\StringWalker();
        $streamer = new XmlStringRewindableStreamer($parser, $stream);
        $streamer->setRewindPoint();

        $expectedNodes = array(
            "foo",
            "bar",
        );

        $foundNodes = array();
        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundNodes[] = (string)$xmlNode->node;
        }

        $streamer->rewind();
        $foundNodesAfterRewind = array();
        while ($node = $streamer->getNode()) {
            $xmlNode = simplexml_load_string($node);
            $foundNodesAfterRewind[] = (string)$xmlNode->node;
        }

        $this->assertEquals($expectedNodes, $foundNodes, "The found nodes should equal the expected nodes");
        $this->assertEquals($foundNodesAfterRewind, $foundNodes, "The found nodes after rewind should equal the found nodes");
    }
}