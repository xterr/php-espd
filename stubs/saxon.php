<?php

namespace Saxon;

class SaxonProcessor
{
    /** @throws \Exception */
    public function __construct() {}

    public function version(): string
    {
        return '';
    }

    public function newXslt30Processor(): Xslt30Processor
    {
        return new Xslt30Processor();
    }

    public function parseXmlFromString(string $xml): XdmNode
    {
        return new XdmNode();
    }
}

class Xslt30Processor
{
    public function compileFromFile(string $path): XsltExecutable
    {
        return new XsltExecutable();
    }
}

class XsltExecutable
{
    public function transformToString(XdmNode $node): ?string
    {
        return null;
    }
}

class XdmNode {}
