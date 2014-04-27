<?php namespace Prewk\XmlStringStreamer;

class FileStreamProvider implements iStreamProvider
{
    private $handle;
    private $readBytes = 0;
    private $chunkSize;

    public function __construct($mixed, $chunkSize)
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
    }

    public function getChunk()
    {
        if (!feof($this->handle)) {
            $buffer = fread($this->handle, $this->chunkSize);
            return $buffer;
        } else {
            return "";
        }
    }

    public function hasMore()
    {
        if (feof($this->handle)) {
            fclose($this->handle);
            return false;
        } else {
            return true;
        }
    }
}
