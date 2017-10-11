<?php
/**
 * xml-string-rewindable-streamer base class
 * 
 * @package xml-string-streamer
 * @author  Kaveh Minooie <kminooie@gmail.com>
 */

namespace Prewk;

use Prewk\XmlStringStreamer\ParserInterface;
use Prewk\XmlStringStreamer\StreamInterface;
use Prewk\XmlStringStreamer\Parser;
use Prewk\XmlStringStreamer\Stream;

/**
 * The class adds rewind functionality to {@link XmlStringStreamer}
 */
class XmlStringRewindableStreamer extends XmlStringStreamer {

    /**
     * @var int indicate not buffering status
     */
    const NOT_BUFFERING = 0;

    /**
     * @var int indicate buffering status
     */
    const BUFFERING = 1;

    /**
     * @var int indicate rewind mode status
     */
    const REWIND = 2;

    /**
     * @var int indicate operating mode, affects the behaviour of {@link getNode}
     */
    protected $mode = self::NOT_BUFFERING;

    /**
     * @var int last read position of {@link rewindBuffer}
     */
    protected $readIndex = 0;

    
    /**
     * The rewind buffer
     * @var string[] nodes since that last rewind point
     */
    protected $rewindBuffer;

    /**
     * Constructs the XML streamer
     * @param ParserInterface $parser A parser with options set
     * @param StreamInterface $stream A stream for the parser to use
     */
    public function __construct( ParserInterface $parser, StreamInterface $stream ) {
        parent::__construct( $parser, $stream );
    }

    /**
     * clears the {@link rewindBuffer} and causes all the nodes returned by {@link getNode}
     * after this function to be placed in {@link rewindBuffer}
     */
    public function setRewindPoint() {
        $this->readIndex = 0;
        $this->rewindBuffer = [];
        $this->mode = static::BUFFERING;
    }

    /**
     * clears the {@link rewindBuffer} and stops collecting nodes returned by {@link getNode}
     */
    public function removeRewindPoint() {
        $this->setRewindPoint();
        $this->mode = static::NOT_BUFFERING;
    }

    /**
     * causes the {@link getNode} to return nodes in {@link rewindBuffer} until it ran out
     * at which point {@link getNode} resumes its normal behaviour
     */
    public function rewind() {
        $this->readIndex = 0;
        $this->mode = static::REWIND;
    }

    /**
     * Gets the next node from the parser or the buffer depending on the rewind {@link mode}
     * @return bool|string The xml string or false
     */
    public function getNode() {

        $node = null;

        if( static::REWIND == $this->mode ) {

            if( count( $this->rewindBuffer ) > $this->readIndex ) {
                $node = $this->rewindBuffer[ $this->readIndex++ ];
            } else {
                $this->mode = static::BUFFERING;
                $node = $this->getNode();
            }

        } else {

            $node = parent::getNode();
            if( static::BUFFERING == $this->mode && false !== $node ) {
                $this->rewindBuffer[] = $node;
            }

        }

        return $node;
    }

}