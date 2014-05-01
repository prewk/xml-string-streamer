<?php namespace Prewk\XmlStringStreamer\StreamProvider;

class Stdin extends File
{
	public function __construct($chunkSize = 1024, $chunkCallback = null)
	{
		parent::__construct(fopen("php://stdin", "r"), $chunkSize, $chunkCallback);
	}
}