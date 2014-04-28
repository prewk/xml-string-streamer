xml-string-streamer
===================

What?
-----
Stream very large XML files in PHP with low memory consumption. Is a successor to [XmlStreamer](https://github.com/prewk/XmlStreamer).

Installation
------------

composer.json:
    
    "require": {
        "prewk/xml-string-streamer": "*"
    }

Usage
-----

### 1. Create a stream provider
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

Examples
--------

### Simple
Let's say you have a 2 GB XML file __gigantic.xml__ containing customer items that look like this:

    <?xml version="1.0" encoding="UTF-8"?>
    <gigantic>
        <customer>
            <firstName>Jane</firstName>
            <lastName>Doe</lastName>
        </customer>
        ...
    </gigantic>

Parse through it in chunks like this:

    use Prewk\XmlStringStreamer;
    
    // Prepare our stream to be read with a 16kb buffer
    $streamProvider = new FileStreamProvider("gigantic.xml", 16384);

    // Construct the streamer and provide a closure to do your stuff
    $streamer = new XmlStringStreamer($streamProvider, function($xmlString) {
        // $xmlString will contain: <customer><firstName>Jane</firstName><lastName>Doe</lastName></customer>
    
        // Load the node with SimpleXML if you want
        $customer = simplexml_load_string($xmlString);
        echo (string)$customer->firstName . "\n";
    });

    // Everything is prepared - time to start parsing
    $streamer->parse();

### Handling progress and buffer read callback

You can provide a closure to the stream provider that will fire every time a chunk is read.

    use Prewk\XmlStringStreamer;
    
    // Prepare our stream to be read with a 16kb buffer
    $streamProvider = new FileStreamProvider("gigantic.xml", 16384, function($buffer, $readBytes) {
        // $buffer contains the last read buffer
        // $readBytes equals the total read bytes so far

        echo "Progress: $readBytes / 2 GB\n"; // (Prepare to get spammed)
    });

FAQ
---

### How do I pass stuff to the closure?
    
Check out [Example #3 Closures and scoping](http://www.php.net/manual/en/functions.anonymous.php). Example of the `use` keyword:

    $someCounter = 0;
    $someArray = array();
    $someDbInstance = new SomeDb;

    $streamer = new XmlStringStreamer($streamProvider, function($xmlString) use (&$someCounter, &$someArray, $someDbInstance) {
        $xml = simplexml_load_string($xmlString);

        $counter++;
        $someArray[] = (string)$xml->foo;
        $someDbInstance->query("....");
    });

    $streamer->parse();

    echo $counter;
    print_r($someArray);
