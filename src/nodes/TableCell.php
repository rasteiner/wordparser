<?php

namespace rasteiner\Wordparser\nodes;

use IteratorAggregate;
use rasteiner\Wordparser\HasStyle;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Style;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

class TableCell extends ContainerNode implements HasStyle {
    public function __construct(
        public Traversable $children,
        public Style $style,
        public ?int $gridSpan = null
    )
    {    
    }

    public function getStyle(): Style {
        return $this->style;
    }

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        if(!Xml::is($xml, 'tc')) return null;

        $props = Xml::child($xml, 'tcPr');
        $style = new Style(properties: Style::parseXML($props));

        $gridSpan = Xml::val($props, 'gridSpan');
        if($gridSpan !== null) {
            $gridSpan = (int) $gridSpan;
        }

        return new static($parser->matchChildren($xml, static::contains()), $style, $gridSpan);
    }

    public static function contains(): array
    {
        return Body::contains();
    }
}