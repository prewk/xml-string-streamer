<?php namespace Prewk\XmlStringStreamer\StreamProvider;

class String implements iStreamProvider
{
    private $chunkSent = false;

    public function __construct($xmlString)
    {
        $this->xmlString = $xmlString;
    }

    public function getChunk()
    {
        if ($this->chunkSent) {
            return false;
        } else {
            $this->chunkSent = true;
            return $this->xmlString;
        }
    }
}
