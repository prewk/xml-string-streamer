<?php
/**
 * xml-string-streamer XmlReader parser
 * 
 * @package xml-string-streamer
 * @author  Oskar Thornblad <oskar.thornblad@gmail.com>
 */

namespace Prewk\XmlStringStreamer\Parser;

use Prewk\XmlStringStreamer\ParserInterface;
use Prewk\XmlStringStreamer\StreamInterface;

class Reader implements ParserInterface
{
    /**
     * Parser contructor
     * @param array $options An options array
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(), $options);
    }

    /**
     * Tries to retrieve the next node or returns false
     * @param  StreamInterface $stream The stream to use
     * @return string|bool             The next xml node or false if one could not be retrieved
     */
    public function getNodeFrom(StreamInterface $stream)
    {

    }
}
