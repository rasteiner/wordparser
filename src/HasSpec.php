<?php

namespace rasteiner\Wordparser;

use SimpleXMLElement;

interface HasSpec {
    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static;

    /**
     * @return string[] a list of possible child nodes (class names)
     */
    public static function contains(): array;
}