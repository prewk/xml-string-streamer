<?php namespace Prewk\XmlStringStreamer\Parser;

use Prewk\XmlStringStreamer\ParserInterface;
use Prewk\XmlStringStreamer\StreamInterface;

class UniqueNode implements ParserInterface
{
    protected $workingBlob = "";
    protected $flushed = "";

    protected $startPos = 0;
    protected $hasSearchedUntilPos = -1;

    const FIND_OPENING_TAG_ACTION = 0;
    const FIND_CLOSING_TAG_ACTION = 1;
    protected $nextAction = 0;

    /**
     * Parser contructor
     * @param array $options An options array
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(), $options);

        if (!isset($this->options["uniqueNode"])) {
            throw new \Exception("Required option 'uniqueNode' not set");
        }
    }

    /**
     * Search the blob for our unique node's opening tag
     * @return bool|int Either returns the char position of the opening tag or false
     */
    protected function getOpeningTagPos()
    {
        $startPositionInBlob = false;
        if (preg_match("/<" . preg_quote($this->options["uniqueNode"]) . "(>| )/", $this->workingBlob, $matches, PREG_OFFSET_CAPTURE) === 1) {
            $startPositionInBlob = $matches[0][1];
        }

        
        if ($startPositionInBlob === false) {
            $this->hasSearchedUntilPos = strlen($this->workingBlob) - 1;
        }

        return $startPositionInBlob;
    }

    /**
     * Search the blob for our unique node's closing tag
     * @return bool|int Either returns the char position of the closing tag or false
     */
    protected function getClosingTagPos()
    {
        $endPositionInBlob = strpos($this->workingBlob, "</" . $this->options["uniqueNode"] . ">");

        if ($endPositionInBlob === false) {
            $this->hasSearchedUntilPos = strlen($this->workingBlob) - 1;
        }

        return $endPositionInBlob;
    }

    /**
     * Set the start position in the workingBlob from where we should start reading when the closing tag is found
     * @param  int $startPositionInBlob Position of starting tag
     */
    protected function startSalvaging($startPositionInBlob)
    {
        $this->startPos = $startPositionInBlob;
    }

    /**
     * Cut everything from the start position to the end position in the workingBlob (+ tag length) and flush it out for later return in getNodeFrom
     * @param  int $endPositionInBlob Position of the closing tag
     */
    protected function flush($endPositionInBlob) {
        $realEndPosition = $endPositionInBlob + strlen("</" . $this->options["uniqueNode"] . ">");
        $this->flushed = substr($this->workingBlob, $this->startPos, $realEndPosition - $this->startPos);
        $this->workingBlob = substr($this->workingBlob, $realEndPosition);
        $this->hasSearchedUntilPos = 0;
    }

    /**
     * Decides whether we're to fetch more chunks from the stream or keep working with what we have.
     * @param  StreamInterface $stream The stream provider
     * @return bool                    Keep working?
     */
    protected function prepareChunk(StreamInterface $stream)
    {
        if ($this->hasSearchedUntilPos > -1 && $this->hasSearchedUntilPos < (strlen($this->workingBlob) - 1)) {
            // More work to do
            return true;
        }

        $chunk = $stream->getChunk();
        
        if ($chunk === false) {
            // EOF
            return false;
        } else {
            // New chunk fetched
            $this->workingBlob .= $chunk;
            return true;
        }
    }

    /**
     * Tries to retrieve the next node or returns false
     * @param  StreamInterface $stream The stream to use
     * @return string|bool             The next xml node or false if one could not be retrieved
     */
    public function getNodeFrom(StreamInterface $stream)
    {
        while ($this->prepareChunk($stream)) {
            // What's our next course of action?
            if ($this->nextAction === self::FIND_OPENING_TAG_ACTION) {
                // Try to find an opening tag
                $positionInBlob = $this->getOpeningTagPos();

                if ($positionInBlob !== false) {
                    // We found it, start salvaging
                    $this->startSalvaging($positionInBlob);

                    // The next course of action will be to find a closing tag
                    $this->nextAction = self::FIND_CLOSING_TAG_ACTION;
                }
            } else if ($this->nextAction === self::FIND_CLOSING_TAG_ACTION) {
                // Try to find a closing tag
                $positionInBlob = $this->getClosingTagPos();
                if ($positionInBlob !== false) {
                    // We found it, we now have a full node to flush out
                    $this->flush($positionInBlob);

                    // The next course of action will be to find an opening tag
                    $this->nextAction = self::FIND_OPENING_TAG_ACTION;

                    // Get the flushed node and make way for the next node
                    $flushed = $this->flushed;
                    $this->flushed = "";

                    return $flushed;
                }
            }
        }
    }
}