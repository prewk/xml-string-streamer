<?php namespace Prewk\XmlStringStreamer;

interface ParserInterface
{
    /**
     * Parser contructor
     * @param array $options An options array decided by the parser implementation
     */
    public function __construct(array $options = array());

    /**
     * Tries to retrieve the next node or returns false
     * @param  StreamInterface $stream The stream to use
     * @return string|bool             The next xml node or false if one could not be retrieved
     */
    public function getNodeFrom(StreamInterface $stream);
}