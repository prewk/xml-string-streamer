xml-string-streamer [![Build Status](https://travis-ci.org/prewk/xml-string-streamer.svg?branch=master)](https://travis-ci.org/prewk/xml-string-streamer)
===================

Purpose
-------
To stream XML files too big to fit into memory, with very low memory consumption. This library is a successor to [XmlStreamer](https://github.com/prewk/XmlStreamer).

Installation
------------

### With composer

Add to `composer.json`:

````json
{
	"require": {
		"prewk/xml-string-streamer": "~0.3.1"
	}
}

````

Run `composer install`.

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

Parse through it in chunks like this:

````php
use Prewk\XmlStringStreamer;

// Prepare our stream to be read with a 1kb buffer
$streamProvider = new XmlStringStreamer\StreamProvider\File("gigantic.xml", 1024);

// Construct the parser
$parser = new XmlStringStreamer\Parser($streamProvider);

// Iterate through the `<customer>` nodes
while ($node = $parser->getNode()) {
	// $node will be a string like this: "<customer><firstName>Jane</firstName><lastName>Doe</lastName></customer>"
	$simpleXmlNode = simplexml_load_string($node);
	echo (string)$simpleXmlNode->firstName;
}
````

Stream providers
---------

### StreamProvider\File

Use this provider to parse large XML files on disk. Pick a chunk size, for example: 1024 bytes.

````php
$CHUNK_SIZE = 1024;
$provider = new Prewk\XmlStringStreamer\StreamProvider\File("large-xml-file.xml", $CHUNK_SIZE);
````

### StreamProvider\Stdin

Use this provider if you want to create a CLI application that streams large XML files through STDIN.

````php
$CHUNK_SIZE = 1024;
$fsp = new Prewk\XmlStringStreamer\StreamProvider\Stdin($CHUNK_SIZE);
````

## StreamProvider\Guzzler

Uses [Guzzler](https://github.com/guzzle/guzzle) to stream XML data from an URL.

Requires PHP 5.4+, so in a different repo: [https://github.com/prewk/xml-string-streamer-guzzle](https://github.com/prewk/xml-string-streamer-guzzle)

Advanced examples
-----------------

### Chunk closure

The `StreamProvider\File` provider can be provided with a closure that will be called on every chunk read:

````php
$counter = 0;
$streamProvider = new XmlStringStreamer\StreamProvider\File("gigantic.xml", 1024, function($buffer, $readBytes) use (&$counter) {
	// $buffer contains last read buffer
	// $readBytes is the total read bytes count
	$counter++;
});
````

### Creating a CLI tool

Create a file `streamer.php`:

````php
require("vendor/autoload.php"); // Using the composer autoloader

use Prewk\XmlStringStreamer;
use Prewk\XmlStringStreamer\StreamProvider;

$streamProvider = new StreamProvider\Stdin(50);
$parser = new XmlStringStreamer\Parser($streamProvider);
while ($node = $parser->getNode()) {
    echo "-----\n";
    echo "$xmlNode\n";
    echo "-----\n";
}
````

Usage:

````sh
php streamer.php <<< "<root-node><node><a>123</a></node><node><a>456</a></node><node><a>789</a></node></root-node>"
````


Options
-------

### Usage

````php
use Prewk\XmlStringStreamer;

$options = array(
	"captureDepth" => 2
);

$parser = new XmlStringStreamer\Parser($streamProvider, $options);
````

### Available options

| Option | Default | Description |
| ------ | ------- | ----------- |
| (int) captureDepth | `1` | Depth we start collecting nodes at |
| (array) tags | See example | Supported tags |
| (bool) expectGT | `false` | Whether to support `>` in XML comments/CDATA or not |
| (array) tagsWithAllowedGT | See example | If _expectGT_ is `true`, this option lists the tags with allowed `>` characters in them |

### Examples

#### captureDepth

Default behavior with a capture depth of `1`:

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
Then you'll need to set the capture depth to `2` to capture the `<capture-me-instead>` nodes.

Node depth visualized:

````xml
<0>
	<1>
		<2>
		</2>
	</1>
</0>
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
