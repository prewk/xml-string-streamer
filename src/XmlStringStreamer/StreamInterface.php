<?php
/**
 * xml-string-streamer Stream interface
 * 
 * @package xml-string-streamer
 * @author  Oskar Thornblad <oskar.thornblad@gmail.com>
 */

namespace Prewk\XmlStringStreamer;

/**
 * Interface describing a stream provider
 */
interface StreamInterface
{
	/**
	 * Gets the next chunk form the stream if one is available
	 * @return bool|string The next chunk if available, or false if not available
	 */
    public function getChunk();
}