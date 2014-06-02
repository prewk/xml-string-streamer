<?php namespace Prewk\XmlStringStreamer\Parser;

use Prewk\XmlStringStreamer\ParserInterface;
use Prewk\XmlStringStreamer\StreamInterface;

class UniqueNode implements ParserInterface
{
    protected $workingBlob = "";
    protected $flushed = "";

    protected const FIND_OPENING_TAG_ACTION = 0;
    protected const FIND_CLOSING_TAG_ACTION = 1;
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
        if (strlen($this->workingBlob) > 0) {
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

    }

    /**
     * Search the blob for our unique node's closing tag
     * @return bool|int Either returns the char position of the closing tag or false
     */
    protected function getClosingTagPos()
    {

    }

    /**
     * Set the start position in the workingBlob from where we should start reading when the closing tag is found
     * @param  int $startPositionInBlob Position of starting tag
     */
    protected function startSalvaging($startPositionInBlob)
    {

    }

    /**
     * Grab everything from the start position to the end position in the workingBlob (+ tag length) and flush it out for later return in getNodeFrom
     * @param  int $endPositionInBlob Position of the closing tag
     */
    protected function flush($endPositionInBlob) {

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
                $positionInBlob = $this->getOpeningTagPos()
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