<?php

namespace Prewk\XmlStringStreamer\Parser;

use \PHPUnit_Framework_TestCase;
use \Mockery;

class StringWalkerTest extends PHPUnit_Framework_TestCase
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

    public function test_default_options()
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
        
        $parser = new StringWalker();
        
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

    public function test_custom_captureDepth()
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
                <parent>
                    $node1
                    $node2
                    $node3
                </parent>
            </root>
eot;
    
        $stream = $this->getStreamMock($xml, 50);
        
        $parser = new StringWalker(array(
            "captureDepth" => 2,
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

    public function test_special_elements()
    {
        $node1 = <<<eot
            <child>
                <!-- An XML comment -->
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>1</index>
            </child>
eot;
        $node2 = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <![CDATA[A CDATA element]]>
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
            <!DOCTYPE foo bar baz>
            <root>
                $node1
                $node2
                $node3
            </root>
eot;
    
        $stream = $this->getStreamMock($xml, 50);
        
        $parser = new StringWalker();
        
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

    public function test_special_elements_with_GT()
    {
        $node1 = <<<eot
            <child>
                <!--
                    An <XML> comment with > <chars> 
                -->
                <foo baz="attribute">Lorem</foo>
                <bar>Ipsum</bar>
                <index>1</index>
            </child>
eot;
        $node2 = <<<eot
            <child>
                <foo baz="attribute">Lorem</foo>
                <![CDATA[
                    A CDATA element with > chars
                    >>><>
                ]]>
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
            <!DOCTYPE foo bar baz>
            <root>
                $node1
                $node2
                $node3
            </root>
eot;
    
        $stream = $this->getStreamMock($xml, 50);
        
        $parser = new StringWalker(array(
            "expectGT" => true,
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
}