<?php

namespace Prewk\XmlStringStreamer\Parser;

use \PHPUnit_Framework_TestCase;
use \Mockery;

class UniqueNodeTest extends PHPUnit_Framework_TestCase
{
    private function getStreamMock($fullString, $bufferSize)
    {
        $defaultXMLLength = strlen($fullString);

        $stream = Mockery::mock("\\Prewk\\XmlStringStreamer\\StreamInterface");
        
        // Mock a stream
        for ($i = 0; $i < $defaultXMLLength; $i += $bufferSize) {
            $stream->shouldReceive("getChunk")
                   ->once()
                   ->andReturn(substr($fullString, $i, $bufferSize));
        }
        $stream->shouldReceive("getChunk")
               ->andReturn(false);

        return $stream;
    }

    public function test_uniqueNode_empty_xml()
    {
        $stream = $this->getStreamMock("", 1024);

        $parser = new UniqueNode(array(
            "uniqueNode" => "child"
        ));

        $this->assertFalse($parser->getNodeFrom($stream), "An empty stream should just exit nicely");
    }

    public function test_uniqueNode_setting()
    {
        $node1 = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>1</index>
            </child>
eot;
        $node2 = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>2</index>
            </child>
eot;
        $node3 = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>3</index>
            </child>
eot;
        $xml = <<<eot
            <?xml version="1.0"?>
            <root>
                $node1
                $node2
                $node3
            </root>
eot;
    
        $stream = $this->getStreamMock($xml, 50);
        
        $parser = new UniqueNode(array(
            "uniqueNode" => "child"
        ));
        
        $this->assertEquals(
            trim($node1),
            trim($parser->getNodeFrom($stream)),
            "Node 1 should be obtained on the first getNodeFrom"
        );
        $this->assertEquals(
            trim($node2),
            trim($parser->getNodeFrom($stream)),
            "Node 2 should be obtained on the first getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the first getNodeFrom"
        );
        $this->assertFalse(
            false,
            "When no nodes are left, false should be returned"
        );
    }

    public function test_uniqueNode_memory_leaks()
    {
        $node = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>1</index>
            </child>
eot;
        $content = "";

        for ($i = 0; $i < 100; $i++) {
            $content .= $node;
        }

        $xml = <<<eot
            <?xml version="1.0"?>
            <root>
                $content
            </root>
eot;
    
        $BUFFER_SIZE = 50;
        $stream = $this->getStreamMock($xml, $BUFFER_SIZE);

        $parser = new UniqueNode(array(
            "uniqueNode" => "unknown"
        ));
        
        $memoryFootprintBefore = strlen(serialize($parser));
        
        $parser->getNodeFrom($stream);

        $memoryFootprintAfter = strlen(serialize($parser));

        $this->assertLessThan(
            $BUFFER_SIZE,
            $memoryFootprintAfter - $memoryFootprintBefore,
            "Memory shouldn't grow in an uncontrolled manner when the first node isn't found"
        );
    }

    public function test_uniqueNode_shortClosing_setting() {
        $node1 = <<<eot
            <child foo="Lorem" index="1" />
eot;
        $node2 = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>2</index>
            </child>
eot;
        $node3 = <<<eot
            <child foo="Lorem" index="3" />
eot;
        $xml = <<<eot
            <?xml version="1.0"?>
            <root>
                $node1
                $node2
                $node3
            </root>
eot;
        $stream = $this->getStreamMock($xml, 50);

        $parser = new UniqueNode(array(
            "uniqueNode" => "child",
            'checkShortClosing' => true
        ));

        $this->assertEquals(
                trim($node1), 
                trim($parser->getNodeFrom($stream)), 
                "Node 1 should be obtained on the first getNodeFrom"
        );
        $this->assertEquals(
                trim($node2), 
                trim($parser->getNodeFrom($stream)), 
                "Node 2 should be obtained on the first getNodeFrom"
        );
        $this->assertEquals(
                trim($node3), 
                trim($parser->getNodeFrom($stream)), 
                "Node 3 should be obtained on the first getNodeFrom"
        );
    }

    /**
     * @expectedException Exception
     */
    public function test_requires_uniqueNode_setting()
    {
        $parser = new UniqueNode;
    }

    public function test_multiple_roots()
    {
        $node1 = <<<eot
            <child>
                <foo baz="attribute" />
                <bar/>
                <index>1</index>
            </child>
eot;
        $node2 = <<<eot
            <child>
                <foo baz="attribute" />
                <bar/>
                <index>2</index>
            </child>
eot;
        $node3 = <<<eot
            <child>
                <foo baz="attribute" />
                <bar/>
                <index>3</index>
            </child>
eot;
        $node4 = <<<eot
            <child>
                <foo baz="attribute" />
                <bar/>
                <index>3</index>
            </child>
eot;
        $xml = <<<eot
            <?xml version="1.0"?>
            <root-a>
                $node1
                $node2
            </root-a>
            <root-b>
                $node3
                $node4
            </root-b>
eot;
        
        $stream = $this->getStreamMock($xml, 50);
        
        $parser = new UniqueNode(array(
            "uniqueNode" => "child"
        ));
        
        $this->assertEquals(
            trim($node1),
            trim($parser->getNodeFrom($stream)),
            "Node 1 should be obtained on the first getNodeFrom from root-a"
        );
        $this->assertEquals(
            trim($node2),
            trim($parser->getNodeFrom($stream)),
            "Node 2 should be obtained on the second getNodeFrom from root-a"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom from root-b"
        );
        $this->assertEquals(
            trim($node4),
            trim($parser->getNodeFrom($stream)),
            "Node 4 should be obtained on the third getNodeFrom from root-b"
        );
        $this->assertFalse(
            false,
            "When no nodes are left, false should be returned"
        );
    }
}