<?php namespace Prewk\XmlStringStreamer\StreamProvider;


interface iStreamProvider
{
    public function getChunk();
    public function hasMore();
}
