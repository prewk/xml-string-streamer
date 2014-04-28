<?php namespace Prewk\XmlStringStreamer;

class Parser
{
    private $callback;
    private $streamProvider;

    public function __construct(StreamProvider\iStreamProvider $streamProvider, $callback, $options = array())
    {
        $this->streamProvider = $streamProvider;
        $this->callback = $callback;
    }
    
    private function extract($depth, $xmlChunk, $xmlNode = "")
    {
        $tagStart = strpos($xmlChunk, "<");
        if ($tagStart === false) {
            return array(
                "depth" => $depth,
                "xmlChunk" => $xmlChunk,
                "xmlNode" => $xmlNode,
            );
        }
        $tagEnd = strpos($xmlChunk, ">", $tagStart);
        if ($tagEnd === false) {
            return array(
                "depth" => $depth,
                "xmlChunk" => $xmlChunk,
                "xmlNode" => $xmlNode,
            );
        }

        $tagAsText = substr($xmlChunk, $tagStart, $tagEnd + 1 - $tagStart);

        if ($tagAsText[1] == "?") {
            // <? ... ? >
            $tagEnd = strpos($xmlChunk, "?>", $tagStart);
            if ($tagEnd === false) {
                return array(
                    "depth" => $depth,
                    "xmlChunk" => $xmlChunk,
                    "xmlNode" => $xmlNode,
                );
            }

            $tagAsText = substr($xmlChunk, $tagStart, $tagEnd + 2 - $tagStart);

            $xmlChunk = substr($xmlChunk, $tagEnd + 2);
            $depth = $depth;
            $xmlNode .= substr($xmlChunk, 0, $tagEnd +2);

            return $this->extract($depth, $xmlChunk, $xmlNode);
        } else if ($tagAsText[1] == "!") {
            if (substr($tagAsText, 0, 9) == "<![CDATA[") {
                // <![CDATA[ ... ]]>
                $tagEnd = strpos($xmlChunk, "]]>", $tagStart);
                if ($tagEnd === false) {
                    return array(
                        "depth" => $depth,
                        "xmlChunk" => $xmlChunk,
                        "xmlNode" => $xmlNode,
                    );
                }

                $tagAsText = substr($xmlChunk, $tagStart, $tagEnd + 3 - $tagStart);

                $xmlNode .= substr($xmlChunk, 0, $tagEnd + 3);
                $xmlChunk = substr($xmlChunk, $tagEnd + 3);
                $depth = $depth;

                return $this->extract($depth, $xmlChunk, $xmlNode);
            } else if (substr($tagAsText, 0, 4) == "<!--") {
                // <!-- ... -->
                $tagEnd = strpos($xmlChunk, "-->", $tagStart);
                if ($tagEnd === false) {
                    return array(
                        "depth" => $depth,
                        "xmlChunk" => $xmlChunk,
                        "xmlNode" => $xmlNode,
                    );
                }

                $tagAsText = substr($xmlChunk, $tagStart, $tagEnd + 3 - $tagStart);

                $xmlNode .= substr($xmlChunk, 0, $tagEnd + 3);
                $xmlChunk = substr($xmlChunk, $tagEnd + 3);
                $depth = $depth;

                return $this->extract($depth, $xmlChunk, $xmlNode);
            } else {
                // <!DOCTYPE html>
                $tagEnd = strpos($xmlChunk, ">", $tagStart);
                if ($tagEnd === false) {
                    return array(
                        "depth" => $depth,
                        "xmlChunk" => $xmlChunk,
                        "xmlNode" => $xmlNode,
                    );
                }

                $tagAsText = substr($xmlChunk, $tagStart, $tagEnd + 1 - $tagStart);

                $xmlNode .= substr($xmlChunk, 0, $tagEnd + 1);
                $xmlChunk = substr($xmlChunk, $tagEnd + 1);
                $depth = $depth;

                return $this->extract($depth, $xmlChunk, $xmlNode);
            }
        } else if ($tagAsText[1] == "/") {
            $xmlNode .= substr($xmlChunk, 0, $tagEnd + 1);
            $xmlChunk = substr($xmlChunk, $tagEnd + 1);
            $depth--;

            if ($depth === 0) {
                call_user_func_array($this->callback, array(trim($xmlNode)));

                $xmlNode = "";
            }

            return $this->extract($depth, $xmlChunk, $xmlNode);
        } else {
            $xmlNode .= substr($xmlChunk, 0, $tagEnd + 1);
            $xmlChunk = substr($xmlChunk, $tagEnd + 1);
            $depth++;

            return $this->extract($depth, $xmlChunk, $xmlNode);
        }
    }

    public function parse()
    {
        $firstChunk = $this->streamProvider->getChunk();

        if (preg_match("/<([^>!?]+)>/", $firstChunk, $matches) === 0) {
            throw new \Exception("Couldn't find root node in first chunk");
        }
        
        $rootElem = $matches[0];
        
        $chunk = substr($firstChunk, strpos($firstChunk, $rootElem) + strlen($rootElem));

        $lastExtractedChunk = $chunk;

        $counter = 0;
        while ($counter == 0 || $this->streamProvider->hasMore()) {
            $chunk = $lastExtractedChunk;
            $chunk .= $this->streamProvider->getChunk();

            $extracted = $this->extract(0, $chunk);

            // Combine them to get the overflow stuff as well    
            $lastExtractedChunk = $extracted["xmlNode"] . $extracted["xmlChunk"];

            $counter++;
        }
    }
}