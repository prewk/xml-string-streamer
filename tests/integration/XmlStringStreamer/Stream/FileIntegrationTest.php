<?php

namespace Prewk\XmlStringStreamer\Stream;

use PHPUnit\Framework\TestCase;

class FileIntegrationTest extends TestCase
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

    public function test_compressed_file()
    {
        if (!extension_loaded("zlib")) {
            $this->markTestSkipped("zlib extension is not installed");
        }

        $chunk1 = "1234567890";
        $chunk2 = "abcdefghij";
        $bufferSize = 10;
        $full = $chunk1 . $chunk2;

        $tmpFile = tempnam(sys_get_temp_dir(), "xmlss-phpunit");
        $wp = fopen("compress.zlib://$tmpFile", "wb");
        fwrite($wp, $full, $bufferSize);
        fclose($wp);

        file_put_contents($tmpFile, $full);

        $stream = new File("compress.zlib://$tmpFile", $bufferSize);

        $this->assertEquals($stream->getChunk(), $chunk1, "First chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), $chunk2, "Second chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), false, "Third chunk received from the stream should be false");
    }

    public function test_remote_stream()
    {
        if (ini_get("allow_url_fopen") !== "1") {
            $this->markTestSkipped("allow_url_fopen is disabled");
        }

        $chunk1 = "<?xml version=\"1.0\" encoding=\"iso-8859-1\" ?>\n<!DOCTYPE html PUBLIC";
        $bufferSize = 66;

        $url = "http://www.w3.org/TR/2001/REC-xml-c14n-20010315";
        $stream = new File($url, $bufferSize);

        $this->assertEquals($chunk1, $stream->getChunk(), "First chunk received from the stream should be as expected");
    }

    public function test_rewind()
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
        $stream->rewind();
        $this->assertEquals($stream->getChunk(), $chunk1, "First chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), $chunk2, "Second chunk received from the stream should be as expected");
        $this->assertEquals($stream->getChunk(), false, "Third chunk received from the stream should be false");
    }
}
