<?php namespace Prewk\XmlStringStreamer\Parser;

use Prewk\XmlStringStreamer\ParserInterface;
use Prewk\XmlStringStreamer\StreamInterface;

class StringWalker implements ParserInterface
{
    protected $options;

    protected $firstRun = true;
    protected $depth = 0;
    protected $chunk;
    protected $shaved = null;

    protected $capture = false;

    /**
     * Parser contructor
     * @param array $options An options array
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            "captureDepth" => 1,
            "expectGT" => false,
            "tags" => array(
                array("<?", "?>", 0),
                array("<!--", "-->", 0),
                array("<![CDATA[", "]]>", 0),
                array("<!", ">", 0),
                array("</", ">", -1),
                array("<", "/>", 0),
                array("<", ">", 1),
            ),
            "tagsWithAllowedGT" => array(
                array("<!--", "-->"),
                array("<![CDATA[", "]]>"),
            ),
        ), $options);
    }

    /**
     * Shaves off the next element from the chunk
     * @return string[]|bool Either a shaved off element array(0 => Captured element, 1 => Data from last shaving point up to and including captured element) or false if one could not be obtained
     */
    protected function shave()
    {
        preg_match("/<[^>]+>/", $this->chunk, $matches, PREG_OFFSET_CAPTURE);

        if (isset($matches[0], $matches[0][0], $matches[0][1])) {
            list($captured, $offset) = $matches[0];
            
            if ($this->options["expectGT"]) {            
                // Some elements support > inside
                foreach ($this->options["tagsWithAllowedGT"] as $tag) {
                    list($opening, $closing) = $tag;

                    if (substr($captured, 0, strlen($opening)) === $opening) {
                        // We have a match, our preg_match may have ended too early
                        // Most often, this isn't the case
                        if (substr($captured, -1 * strlen($closing)) !== $closing) {
                            // In this case, the preg_match ended too early, let's find the real end
                            $position = strpos($this->chunk, $closing);
                            if ($position === false) {
                                // We need more XML!

                                $this->timeShave += microtime(true) - $time;

                                return false;
                            }

                            // We found the end, modify $captured
                            $captured = substr($this->chunk, $offset, $position + strlen($closing) - $offset);
                        }
                    }
                }
            }

            // Data in between
            $data = substr($this->chunk, 0, $offset);

            // Shave from chunk
            $this->chunk = substr($this->chunk, $offset + strlen($captured));

            return array($captured, $data . $captured);
        }

        return false;
    }

    /**
     * Extract XML compatible tag head and tail
     * @param  string $element XML element
     * @return string[] 0 => Opening tag, 1 => Closing tag
     */
    protected function getEdges($element)
    {
        // TODO: Performance tuning possible here by not looping

        foreach ($this->options["tags"] as $tag) {
            list($opening, $closing, $depth) = $tag;

            if (substr($element, 0, strlen($opening)) === $opening
                && substr($element, -1 * strlen($closing)) === $closing) {

                return $tag;
            }
        }
    }

    /**
     * The shave method must be able to request more data even though there isn't any more to fetch from the stream, this method wraps the getChunk call so that it returns true as long as there is XML data left
     * @return bool Returns whether there is more XML data or not
     */
    protected function prepareChunk($stream)
    {
        if (!$this->firstRun && is_null($this->shaved)) {
            // We're starting again after a flush
            $this->shaved = "";

            return true;
        } else if (is_null($this->shaved)) {
            $this->shaved = "";
        }

        $newChunk = $stream->getChunk();

        if ($newChunk !== false) {
            $this->chunk .= $newChunk;

            return true;
        } else {
            if (trim($this->chunk) !== "") {
                return true;
            }
        }

        return false;
    }

    /**
     * Tries to retrieve the next node or returns false
     * @param  StreamInterface $stream The stream to use
     * @return string|bool             The next xml node or false if one could not be retrieved
     */
    public function getNodeFrom(StreamInterface $stream)
    {
        // Iterate and append to $this->chunk

        while ($this->prepareChunk($stream)) {
            $this->firstRun = false;
            // Shave off elements
            while ($shaved = $this->shave()) {
                list($element, $data) = $shaved;

                if ($this->capture) {
                    $this->shaved .= $data;
                }

                // Analyze element
                list($opening, $closing, $depth) = $this->getEdges($element);

                // Update depth
                $this->depth += $depth;

                if ($this->depth === $this->options["captureDepth"]) {
                    if (!$this->capture) {
                        // Desired depth encountered - Start capturing
                        $this->capture = true;
                    } else {
                        // Whole node is captured, flush it out
                        $flush = $this->shaved;
                        $this->shaved = null;

                        return $flush;
                    }
                }
            }
        }

        return false;
	}
}