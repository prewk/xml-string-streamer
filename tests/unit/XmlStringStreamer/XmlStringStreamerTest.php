<?php

use Prewk\XmlStringStreamer;
use Mockery;

class XmlStringStreamerTest extends PHPUnit_Framework_TestCase
{
    public function testGetNode()
    {
        $mock = Mockery::mock("XmlStringStreamer\\ParserInterface");
    }
}