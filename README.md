xml-string-streamer
===================

What?
-----
Stream very large XML files in PHP without using any memory. Is a successor to the older [XmlStreamer](https://github.com/prewk/XmlStreamer).

Installation
------------

composer.json:

    "require": {
        "prewk/xml-string-streamer": "*"
    }

Usage
-----

### 1. Create a streamer provider
Currently, there are two:

#### FileStreamProvider
This is probably what you want. Stream files in chunks with low memory consumption.

    $CHUNK_SIZE = 16384;
    $fsp = new Prewk\XmlStringStreamer\FileStreamProvider("large-xml-file.xml", $CHUNK_SIZE);
    
#### StringStreamProvider
This is mainly for testing. "Streams" (just returns) a text string as a large chunk. Therefore: eats all your memory.

    $xmlStr = "<xml>..............</xml>"; // Some XML string
    $fsp = new Prewk\XmlStringStreamer\StringStreamProvider($xmlStr);

### 2. Construct a streamer and provide a closure

    $streamer = new Prewk\XmlStringStreamer($fsp, function($xmlString) {
        // This closure will be called every time a full node has been parsed
    });

### 3. Run

    $streamer->parse();

Example
-------

TODO
