<?php
/**
 * xml-string-streamer UniqueNode parser
 *
 * @package xml-string-streamer
 * @author  Oskar Thornblad <oskar.thornblad@gmail.com>
 * @author  Roman Voloboev <animir@ya.ru>
 */

namespace Prewk\XmlStringStreamer\Parser;

use Exception;
use Prewk\XmlStringStreamer\ParserInterface;
use Prewk\XmlStringStreamer\StreamInterface;

/**
 * The unique node parser starts at a given element name and flushes when its corresponding closing tag is found
 */
class UniqueNode implements ParserInterface
{
    /**
     * Current working XML blob
     * @var string
     */
    private $workingBlob = "";
    /**
     * The flushed node
     * @var string
     */
    private $flushed = "";

    /**
     * Start position of the given element in the workingBlob
     * @var integer
     */
    private $startPos = 0;
    /**
     * Records how far we've searched in the XML blob so far
     * @var integer
     */
    private $hasSearchedUntilPos = -1;

    const FIND_OPENING_TAG_ACTION = 0;
    const FIND_CLOSING_TAG_ACTION = 1;

    /**
     * Next action to perform
     * @var integer
     */
    private $nextAction = 0;

    /**
     * Indicates short closing tag
     * @var bool
     */

    private $shortClosedTagNow = false;

    /**
     * If extractContainer is true, this will grow with the XML captured before and after the specified capture depth
     * @var string
     */
    protected $containerXml = "";

    /**
     * Whether we're found our first capture target or not
     * @var bool
     */
    protected $preCapture = true;

    /**
     * Parser constructor
     * @param array $options An options array
     * @throws Exception if the required option uniqueNode isn't set
     */
    public function __construct(array $options = array())
    {
        $this->options = array_merge(array(
            "extractContainer" => false,
        ), $options);

        if (!isset($this->options["uniqueNode"])) {
            throw new Exception("Required option 'uniqueNode' not set");
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
     * Search short closing tag in $workingBlob before
     *
     * @param string $workingBlob
     * @param int $len
     * @return bool|int Either returns the char position of the short closing tag or false
     */
    private function checkShortClosingTag($workingBlob, $len) {
        $resultEndPositionInBlob = false;
        while ($len = strpos($workingBlob, "/>", $len + 1)) {
            $subBlob = substr($workingBlob, $this->startPos, $len + strlen("/>") - $this->startPos);
            $cntOpen = substr_count($subBlob, "<");
            $cntClose = substr_count($subBlob, "/>");
            if ($cntOpen === $cntClose && $cntOpen === 1) {
                $resultEndPositionInBlob = $len + strlen("/>");
                break; // end while. so $endPositionInBlob correct now
            }
        }
        return $resultEndPositionInBlob;
    }

    /**
     * Search the blob for our unique node's closing tag
     * @return bool|int Either returns the char position of the closing tag or false
     */
    protected function getClosingTagPos()
    {
        $endPositionInBlob = strpos($this->workingBlob, "</" . $this->options["uniqueNode"] . ">");
        if ($endPositionInBlob === false) {

            if (isset($this->options["checkShortClosing"]) && $this->options["checkShortClosing"] === true) {
                $endPositionInBlob = $this->checkShortClosingTag($this->workingBlob, $this->startPos);
            }

            if ($endPositionInBlob === false) {
                $this->hasSearchedUntilPos = strlen($this->workingBlob) - 1;
            } else {
                $this->shortClosedTagNow = true;
            }
        } else {
            if (isset($this->options["checkShortClosing"]) && $this->options["checkShortClosing"] === true) {
                $tmpEndPositionInBlob = $this->checkShortClosingTag(substr($this->workingBlob, 0, $endPositionInBlob), $this->startPos);
                if ($tmpEndPositionInBlob !== false) {
                    $this->shortClosedTagNow = true;
                    $endPositionInBlob = $tmpEndPositionInBlob;
                }
            }
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
        $endTagLen = $this->shortClosedTagNow ? 0 : strlen("</" . $this->options["uniqueNode"] . ">");
        $realEndPosition = $endPositionInBlob + $endTagLen;
        $this->flushed = substr($this->workingBlob, $this->startPos, $realEndPosition - $this->startPos);
        $this->workingBlob = substr($this->workingBlob, $realEndPosition);
        $this->hasSearchedUntilPos = 0;
        $this->shortClosedTagNow = false;
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
            if ($this->hasSearchedUntilPos === -1) {
                // EOF, but we haven't even started searching, special case that probably means we're dealing with a file of less size than the stream buffer
                // Therefore, keep looping
                return true;
            }
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
                    if ($this->options["extractContainer"] && $this->preCapture) {
                        $this->containerXml .= substr($this->workingBlob, 0, $positionInBlob);
                        $this->preCapture = false;
                    }


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

        if ($this->options["extractContainer"]) {
            $this->containerXml .= $this->workingBlob;
        }

        return false;
    }

    /**
     * Get the extracted container XML, if called before the whole stream is parsed, the XML returned can be invalid due to missing closing tags
     * @return string XML string
     * @throws Exception if the extractContainer option isn't true
     */
    public function getExtractedContainer()
    {
        if (!$this->options["extractContainer"]) {
            throw new Exception("This method requires the 'extractContainer' option to be true");
        }

        return $this->containerXml;
    }
}