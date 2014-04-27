<?php namespace Prewk\XmlStringStreamer;

class StringStreamProvider implements iStreamProvider
{
    private $chunkSent = false;

    public function __construct($xmlString)
    {
        $this->xmlString = $xmlString;
    }

    public function getChunk()
    {
        if ($this->chunkSent) {
            return "";
        } else {
            $this->chunkSent = true;
            return $this->xmlString;
        }
    }

    public function hasMore() {
        return false;
    }
}
