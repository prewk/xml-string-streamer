<?php

namespace Prewk;

use PHPUnit\Framework\TestCase;
use \Mockery;

class XmlStringStreamerTest extends TestCase
{
    public function test_getNode()
    {
        $node = "<node><a>lorem</a><b>ipsum</b></node>";

        $parser = Mockery::mock("\\Prewk\\XmlStringStreamer\\ParserInterface");
        $parser->shouldReceive("getNodeFrom")
               ->with(Mockery::type("\\Prewk\\XmlStringStreamer\\StreamInterface"))
               ->once()
               ->andReturn($node);

        $stream = Mockery::mock("\\Prewk\\XmlStringStreamer\\StreamInterface");

        $streamer = new XmlStringStreamer($parser, $stream);
        
        $this->assertEquals($node, $streamer->getNode(), "Node received from the parser should be what was expected");
    }
}