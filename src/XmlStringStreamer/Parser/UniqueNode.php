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

    protected function prepareChunk($stream)
    {
        if ($this->hasSearchedUntilPos > -1 && $this->hasSearchedUntilPos < (strlen($this->workingBlob) - 1)) {
            // More work to do
            return true;
        }

        $chunk = $stream->getChunk();
        
        if ($chunk === false) {
            return false;
        } else {
            $this->workingBlob .= $chunk;
            return true;
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
     * Tries to retrieve the next node or returns false
     * @param  StreamInterface $stream The stream to use
     * @return string|bool             The next xml node or false if one could not be retrieved
     */
    public function getNodeFrom(StreamInterface $stream)
    {
        while ($this->prepareChunk($stream)) {
            if ($this->nextAction === self::FIND_OPENING_TAG_ACTION) {
                $positionInBlob = $this->getOpeningTagPos();
                if ($positionInBlob !== false) {
                    $this->startSalvaging($positionInBlob);
                    $this->nextAction = self::FIND_CLOSING_TAG_ACTION;
                }
            } else if ($this->nextAction === self::FIND_CLOSING_TAG_ACTION) {
                $positionInBlob = $this->getClosingTagPos();
                if ($positionInBlob !== false) {
                    $this->flush($positionInBlob);
                    $this->nextAction = self::FIND_OPENING_TAG_ACTION;

                    $flushed = $this->flushed;
                    $this->flushed = "";

                    return $flushed;
                }
            }
        }
    }
}