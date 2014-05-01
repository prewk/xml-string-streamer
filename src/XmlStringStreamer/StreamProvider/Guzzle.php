<?php namespace Prewk\XmlStringStreamer\StreamProvider;

class Guzzle implements iStreamProvider
{
    private $stream;
    private $readBytes = 0;
    private $chunkSize;
    private $chunkCallback;

    public function __construct($url, $chunkSize = 1024, $chunkCallback = null)
    {
        $this->chunkSize = $chunkSize;
        $this->chunkCallback = $chunkCallback;
        $this->stream = \GuzzleHttp\Stream\create(fopen($url, "r"));
    }

    public function getChunk()
    {
        if (!$this->stream->eof()) {
        	$buffer = $this->stream->read($this->chunkSize);
            $this->readBytes += strlen($buffer);

            if (is_callable($this->chunkCallback)) {
                call_user_func_array($this->chunkCallback, array($buffer, $this->readBytes));
            }
            
            return $buffer;
        } else {
            return "";
        }
    }

    public function hasMore()
    {
    	return !$this->stream->eof();
    }
}
