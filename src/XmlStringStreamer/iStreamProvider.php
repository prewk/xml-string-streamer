<?php namespace Prewk\XmlStringStreamer;


interface iStreamProvider
{
    public function getChunk();
    public function hasMore();
}
