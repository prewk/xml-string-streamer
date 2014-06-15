<?php

namespace Prewk\XmlStringStreamer\Stream;

use \PHPUnit_Framework_TestCase;
use \Mockery;

class FileIntegrationTest extends PHPUnit_Framework_TestCase
{
    public function test_stream_a_file_by_path()
    {
        $chunk1 = "1234567890";
        $chunk2 = "abcdefghij";
        $bufferSize = 10;
        $full = $chunk1 . $chunk2;

        $tmpFile = tempnam(sys_get_temp_dir(), "xmlss-phpunit");
        file_put_contents($tmpFile, $full);

        $stream = new File($tmpFile, $bufferSize);

        $this->assertEquals($stream->getChunk(), $chunk1, "First chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), $chunk2, "Second chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), false, "Third chunk received from the stream should be false");
    }

    public function test_stream_a_file_by_handle()
    {
        $chunk1 = "1234567890";
        $chunk2 = "abcdefghij";
        $bufferSize = 10;
        $full = $chunk1 . $chunk2;

        $tmpFile = tempnam(sys_get_temp_dir(), "xmlss-phpunit");
        file_put_contents($tmpFile, $full);

        $stream = new File(fopen($tmpFile, "r"), $bufferSize);

        $this->assertEquals($stream->getChunk(), $chunk1, "First chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), $chunk2, "Second chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), false, "Third chunk received from the stream should be false");
    }

    public function test_chunk_callback()
    {
        $file = __dir__ . "/../../../xml/pubmed-example.xml";
        $chunkSize = 100;

        $callbackCount = 0;
        $stream = new File($file, 100, function($buffer, $readBytes) use (&$callbackCount) {
            $callbackCount++;
        });

        $chunkCount = 0;
        while ($chunk = $stream->getChunk()) {
            $chunkCount++;
        }

        $this->assertEquals($callbackCount, $chunkCount, "Chunk callback count should be the same as getChunk count");
    }
}