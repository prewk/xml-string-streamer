<?php namespace Prewk\XmlStringStreamer\Stream;

use Exception;
use Prewk\XmlStringStreamer\StreamInterface;

class File implements StreamInterface
{
    private $handle;
    private $readBytes = 0;
    private $chunkSize;
    private $chunkCallback;

    public function __construct($mixed, $chunkSize = 16384, $chunkCallback = null)
    {
        if (is_string($mixed)) {
            // Account for common stream/URL wrappers before checking if a file exists
            $realPath = $mixed;
            if (preg_match('/^([\w.]+):\/\//', $realPath, $matched)) {
                if (preg_match('/(http|ftp|php|data|ssh2)/', $matched[1])) {
                    // Disable file_exists() check
                    $realPath = null;
                } else {
                    // Remove wrapper for file_exists() check.
                    $realPath = substr($realPath, strlen($matched[0]));
                }
            }
            // If there's a real disk path to check, make sure it exists
            if ($realPath !== null && !file_exists($realPath)) {
                throw new \Exception("File '$realPath' doesn't exist");
            }
            $this->handle = fopen($mixed, 'rb');
        } elseif (is_resource($mixed) && get_resource_type($mixed) === "stream") {
            $this->handle = $mixed;
        } else {
            throw new \Exception("First argument must be either a filename or a file handle");
        }

        if ($this->handle === false) {
            throw new \Exception("Couldn't create file handle");
        }

        $this->chunkSize = $chunkSize;
        $this->chunkCallback = $chunkCallback;
    }

    public function __destruct() {
        if (is_resource($this->handle)) {
            fclose($this->handle);
        }
    }

    public function getChunk()
    {
        if (is_resource($this->handle) && !feof($this->handle)) {
            $buffer = fread($this->handle, $this->chunkSize);
            $this->readBytes += strlen($buffer);

            if (is_callable($this->chunkCallback)) {
                call_user_func_array($this->chunkCallback, array($buffer, $this->readBytes));
            }

            return $buffer;
        }

        return false;
    }

    public function isSeekable()
    {
        $meta = stream_get_meta_data($this->handle);

        return $meta["seekable"];
    }

    public function rewind()
    {
        if (!$this->isSeekable()) {
            throw new Exception("Attempted to rewind an unseekable stream");
        }

        $this->readBytes = 0;
        rewind($this->handle);
    }
}
