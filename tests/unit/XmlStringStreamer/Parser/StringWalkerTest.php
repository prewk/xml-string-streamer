<?php

namespace Prewk\XmlStringStreamer\Parser;

use PHPUnit\Framework\TestCase;
use \Mockery;

class StringWalkerTest extends TestCase
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

    public function test_stringWalker_empty_xml()
    {
        $stream = $this->getStreamMock("", 1024);

        $parser = new StringWalker();

        $this->assertFalse($parser->getNodeFrom($stream), "An empty stream should just exit nicely");
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
            "Node 2 should be obtained on the second getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom"
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
            "captureDepth" => 3,
        ));
        
        $this->assertEquals(
            trim($node1),
            trim($parser->getNodeFrom($stream)),
            "Node 1 should be obtained on the first getNodeFrom"
        );
        $this->assertEquals(
            trim($node2),
            trim($parser->getNodeFrom($stream)),
            "Node 2 should be obtained on the second getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom"
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
            "Node 2 should be obtained on the second getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom"
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
            "Node 2 should be obtained on the second getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom"
        );
        $this->assertFalse(
            false,
            "When no nodes are left, false should be returned"
        );
    }

    public function test_self_closing_elements()
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
            "Node 2 should be obtained on the second getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom"
        );
        $this->assertFalse(
            false,
            "When no nodes are left, false should be returned"
        );
    }

    public function test_self_closing_elements_at_depth()
    {
        $xml = <<<eot
            <?xml version="1.0" ?>
            <root>
              <foo>baz</foo>
              <bar />
            </root>
eot;

        $stream = $this->getStreamMock($xml, 50);

        $parser = new StringWalker(array(
            "captureDepth" => 2,
        ));

        $this->assertEquals(
            trim("<foo>baz</foo>"),
            trim($parser->getNodeFrom($stream))
        );

        $this->assertEquals(
            trim("<bar />"),
            trim($parser->getNodeFrom($stream))
        );
    }

    public function test_different_capture_node_types()
    {
        $node1 = <<<eot
            <child-a>
                <foo baz="attribute" />
                <bar/>
                <index>1</index>
            </child-a>
eot;
        $node2 = <<<eot
            <child-b>
                <foo baz="attribute" />
                <bar/>
                <index>2</index>
            </child-b>
eot;
        $node3 = <<<eot
            <child-c>
                <foo baz="attribute" />
                <bar/>
                <index>3</index>
            </child-c>
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
            "Node 2 should be obtained on the second getNodeFrom"
        );
        $this->assertEquals(
            trim($node3),
            trim($parser->getNodeFrom($stream)),
            "Node 3 should be obtained on the third getNodeFrom"
        );
        $this->assertFalse(
            false,
            "When no nodes are left, false should be returned"
        );
    }

    public function test_multiple_roots()
    {
        $node1 = <<<eot
            <child-a>
                <foo baz="attribute" />
                <bar/>
                <index>1</index>
            </child-a>
eot;
        $node2 = <<<eot
            <child-a>
                <foo baz="attribute" />
                <bar/>
                <index>2</index>
            </child-a>
eot;
        $node3 = <<<eot
            <child-b>
                <foo baz="attribute" />
                <bar/>
                <index>3</index>
            </child-b>
eot;
        $node4 = <<<eot
            <child-b>
                <foo baz="attribute" />
                <bar/>
                <index>3</index>
            </child-b>
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
        
        $parser = new StringWalker();
        
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