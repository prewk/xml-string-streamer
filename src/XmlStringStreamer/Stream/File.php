<?php namespace Prewk\XmlStringStreamer\Stream;

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
            // Treat as filename
            if (!file_exists($mixed)) {
                throw new \Exception("File '$mixed' doesn't exist");
            }
            $this->handle = fopen($mixed, "rb");
            $this->handle;
        } else if (get_resource_type($mixed) == "stream") {
            // Treat as file handle
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

    public function getChunk()
    {
        if (is_resource($this->handle) && !feof($this->handle)) {
            $buffer = fread($this->handle, $this->chunkSize);
            $this->readBytes += strlen($buffer);

            if (is_callable($this->chunkCallback)) {
                call_user_func_array($this->chunkCallback, array($buffer, $this->readBytes));
            }
            
            return $buffer;
        } else if (is_resource($this->handle)) {
            fclose($this->handle);
            return false;
        } else {
            return false;
        }
    }
}