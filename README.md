xml-string-streamer [![Build Status](https://api.travis-ci.org/prewk/xml-string-streamer.svg?branch=master)](https://travis-ci.org/prewk/xml-string-streamer)
===================

Purpose
-------
To stream XML files too big to fit into memory, with very low memory consumption. This library is a successor to [XmlStreamer](https://github.com/prewk/XmlStreamer).

Installation
------------

### Legacy support

* All versions below 1 support PHP 5.3 - 7.2
* Version 1 and above support PHP 7.2+

### With composer

Run `composer require prewk/xml-string-streamer` to install this package.

Usage
-----

Let's say you have a 2 GB XML file gigantic.xml containing customer items that look like this:

````xml
<?xml version="1.0" encoding="UTF-8"?>
<gigantic>
    <customer>
        <firstName>Jane</firstName>
        <lastName>Doe</lastName>
    </customer>
    ...
</gigantic>
````

Create a streamer and parse it:

````php
// Convenience method for creating a file streamer with the default parser
$streamer = Prewk\XmlStringStreamer::createStringWalkerParser("gigantic.xml");

while ($node = $streamer->getNode()) {
    // $node will be a string like this: "<customer><firstName>Jane</firstName><lastName>Doe</lastName></customer>"
    $simpleXmlNode = simplexml_load_string($node);
    echo (string)$simpleXmlNode->firstName;
}
````

Without the convenience method (functionally equivalient):

````php
use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream;
use Prewk\XmlStringStreamer\Parser;

// Prepare our stream to be read with a 1kb buffer
$stream = new Stream\File("gigantic.xml", 1024);

// Construct the default parser (StringWalker)
$parser = new Parser\StringWalker();

// Create the streamer
$streamer = new XmlStringStreamer($parser, $stream);

// Iterate through the `<customer>` nodes
while ($node = $streamer->getNode()) {
    // $node will be a string like this: "<customer><firstName>Jane</firstName><lastName>Doe</lastName></customer>"
    $simpleXmlNode = simplexml_load_string($node);
    echo (string)$simpleXmlNode->firstName;
}
````

Convenience method for the UniqueNode parser:

````php
$streamer = Prewk\XmlStringStreamer::createUniqueNodeParser("file.xml", array("uniqueNode" => "customer"));
````

Parsers
-------

### Parser\StringWalker

Works like an XmlReader, and walks the XML tree node by node. Captures by node depth setting.

### Parser\UniqueNode

A much faster parser that captures everything between a provided element's opening and closing tags. Special prerequisites apply.

Stream providers
----------------

### Stream\File

Use this provider to parse large XML files on disk. Pick a chunk size, for example: 1024 bytes.

````php
$CHUNK_SIZE = 1024;
$provider = new Prewk\XmlStringStreamer\Stream\File("large-xml-file.xml", $CHUNK_SIZE);
````

### Stream\Stdin

Use this provider if you want to create a CLI application that streams large XML files through STDIN.

````php
$CHUNK_SIZE = 1024;
$fsp = new Prewk\XmlStringStreamer\Stream\Stdin($CHUNK_SIZE);
````

### Stream\Guzzle

Use this provider if you want to stream over HTTP with [Guzzle](https://github.com/guzzle/guzzle). Resides in its own repo due to its higher PHP version requirements (5.5): [https://github.com/prewk/xml-string-streamer-guzzle](https://github.com/prewk/xml-string-streamer-guzzle)

StringWalker Options
--------------------

### Usage

````php
use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Parser;
use Prewk\XmlStringStreamer\Stream;

$options = array(
    "captureDepth" => 3
);

$parser = new Parser\StringWalker($options);
````

### Available options for the StringWalker parser

| Option | Default | Description |
| ------ | ------- | ----------- |
| (int) captureDepth | `2` | Depth we start collecting nodes at |
| (array) tags | See example | Supported tags |
| (bool) expectGT | `false` | Whether to support `>` in XML comments/CDATA or not |
| (array) tagsWithAllowedGT | See example | If _expectGT_ is `true`, this option lists the tags with allowed `>` characters in them |

### Examples

#### captureDepth

Default behavior with a capture depth of `2`:

````xml
<?xml encoding="utf-8"?>
<root-node>
    <capture-me>
        ...
    </capture-me>
    <capture-me>
        ...
    </capture-me>
</root-node>
````

..will capture the `<capture-me>` nodes.

But say your XML looks like this:

````xml
<?xml encoding="utf-8"?>
<root-node>
    <a-sub-node>
        <capture-me-instead>
            ...
        </capture-me-instead>
        <capture-me-instead>
            ...
        </capture-me-instead>
    </a-sub-node>
</root-node>
````
Then you'll need to set the capture depth to `3` to capture the `<capture-me-instead>` nodes.

Node depth visualized:

````xml
<?xml?> <!-- Depth 0 because it's at the root and doesn't affect depth -->
<root-node> <!-- Depth 1 because it's at the root and increases depth -->
    <a-sub-node> <!-- Depth 2 because it has one ancestor and increases depth -->
        <capture-me-instead> <!-- Depth 3 because it has two ancestors and increases depth -->
        </capture-me-instead>
    </a-sub-node>
</root-node>
````

#### tags

Default value:

````php
array(
    array("<?", "?>", 0),
    array("<!--", "-->", 0),
    array("<![CDATA[", "]]>", 0),
    array("<!", ">", 0),
    array("</", ">", -1),
    array("<", "/>", 0),
    array("<", ">", 1)
),
````

First parameter: opening tag, second parameter: closing tag, third parameter: depth.

If you know that your XML doesn't have any XML comments, CDATA or self-closing tags, you can tune your performance by setting the _tags_ option and omitting them:

````php
array(
    array("<?", "?>", 0),
    array("<!", ">", 0),
    array("</", ">", -1),
    array("<", ">", 1)
),
````

#### expectGT & tagsWithAllowedGT

You can allow the `>` character within XML comments and CDATA sections if you want. This is pretty uncommon, and therefore turned off by default for performance reasons.

Default value for tagsWithAllowedGT:

````php
array(
    array("<!--", "-->"),
    array("<![CDATA[", "]]>")
),
````

UniqueNode Options
------------------

### Usage

````php
use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Parser;
use Prewk\XmlStringStreamer\Stream;

$options = array(
    "uniqueNode" => "TheNodeToCapture"
);

$parser = new Parser\UniqueNode($options);
````

### Available options for the UniqueNode parser

| Option | Description |
| ------ | ----------- |
| (string) uniqueNode | Required option: Specify the node name to capture |
| (bool) checkShortClosing | Whether to check short closing tag or not |

### Examples

#### uniqueNode

Say you have an XML file like this:
````xml
<?xml encoding="utf-8"?>
<root-node>
    <stuff foo="bar">
        ...
    </stuff>
    <stuff foo="baz">
        ...
    </stuff>
    <stuff foo="123">
        ...
    </stuff>
</root-node>
````
You want to capture the stuff nodes, therefore set _uniqueNode_ to `"stuff"`.

If you have an XML file with short closing tags like this:
````xml
<?xml encoding="utf-8"?>
<root-node>
    <stuff foo="bar" />
    <stuff foo="baz">
        ...
    </stuff>
    <stuff foo="123" />
</root-node>
````
You want to capture the stuff nodes, therefore set _uniqueNode_ to `"stuff"` and _checkShortClosing_ to `true`.

But if your XML file look like this:
````xml
<?xml encoding="utf-8"?>
<root-node>
    <stuff foo="bar">
        <heading>Lorem ipsum</heading>
        <content>
            <stuff>Oops, another stuff node</stuff>
        </content>
    </stuff>
    ...
</root-node>
````

..you won't be able to use the UniqueNode parser, because `<stuff>` exists inside of another `<stuff>` node.

Advanced Usage
------------------------

### Progress bar

You can track progress using a closure as the third argument when constructing the stream class. Example with the `File` stream using the `StringWalker` parser:

````php
use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream\File;
use Prewk\XmlStringStreamer\Parser\StringWalker;

$file = "path/to/file.xml";

// Save the total file size
$totalSize = filesize($file);

// Construct the file stream
$stream = new File($file, 16384, function($chunk, $readBytes) use ($totalSize) {
    // This closure will be called every time the streamer requests a new chunk of data from the XML file
    echo "Progress: $readBytes / $totalSize\n";
});
// Construct the parser
$parser = new StringWalker;

// Construct the streamer
$streamer = new XmlStringStreamer($parser, $stream);

// Start parsing
while ($node = $streamer->getNode()) {
    // ....
}
````

_You could of course do something more intelligent than spamming with `echo`._

### Accessing the root element (version 0.7.0+)

Setting the parser option `extractContainer` tells the parser to gather everything before and after your intended child element capture. The results are available via the parser's `getExtractedContainer()` method.

_Note:_ `getExtractedContainer()` will return different things depending on if you've streamed the whole file or not. If you need the containing XML data prematurely you can get it inside of the while loop, but it will just be the opening elements and therefore considered invalid XML by parsers such as SimpleXML.

````php
use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\Stream\File;
use Prewk\XmlStringStreamer\Parser\StringWalker;

$file = "path/to/file.xml";

// Construct the file stream
$stream = new File($file, 16384);
// Construct the parser
$parser = new StringWalker(array(
    "extractContainer" => true, // Required option
));

// Construct the streamer
$streamer = new XmlStringStreamer($parser, $stream);

// Start parsing
while ($node = $streamer->getNode()) {
    // ....
}

// Get the containing XML
$containingXml = $parser->getExtractedContainer();

$xmlObj = simplexml_load_string($containingXml);
$rootElementName = $xmlObj->getName();
$rootElementFooAttribute = $xmlObj->attributes()->foo;
````

_This method should be considered experimental, and may extract weird stuff in edge cases_
