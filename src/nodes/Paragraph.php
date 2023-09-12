<?php

namespace rasteiner\Wordparser\nodes;

use Exception;
use Intervals;
use IteratorAggregate;
use rasteiner\Wordparser\HasStyle;
use rasteiner\Wordparser\ListInstance;
use rasteiner\Wordparser\Node;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Style;
use rasteiner\Wordparser\Stylesheet;
use rasteiner\Wordparser\Xml;
use SimpleXMLElement;
use Traversable;

class Paragraph extends ContainerNode implements HasStyle {
    public function __construct(
        public Traversable $children,
        public Style $paragraphStyle,
        public ?ListInstance $list = null,
        public ?int $listLevel = null
    ) {  
    }

    public function getStyle(): Style {
        return $this->paragraphStyle;
    }

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        if(!Xml::is($xml, 'p')) return null;

        // load properties
        $props = Xml::child($xml, 'pPr');

        if($props !== null) {
            $style = match($id = Xml::val($props, 'pStyle')) {
                null => new Style(),
                default => $parser->stylesheet->get($id)
            };

            $localStyle = Style::parseXML($props);
            $style = $style->merge($localStyle);

            $numId = Xml::val($props, 'numPr/numId');
            $numLevel = Xml::val($props, 'numPr/ilvl');

            $listLevel = null;
            $list = null;

            if($numId !== null) {
                $list = $parser->numbering->get($numId);
                
                // sometimes word uses a list id that is not defined in the numbering.xml
                // in this case we just ignore the list, as also word does
                // throw new Exception("List with id $numId not found");
                if($list !== null) {
                    if($numLevel !== null) {
                        $listLevel = intval($numLevel);
                    } else {
                        $listLevel = null;
                    }
                }
            }
            
            // maybe the style has a numbering?
            if(!isset($list)) {
                $list = $style->numbering($parser->numbering);
            }
            
        } else {
            $style = new Style();
        }
        
        return new static(
            $parser->matchChildren($xml, static::contains()),
            $style,
            $list ?? null,
            $listLevel ?? null
        );
    }

    public static function contains(): array
    {
        return [
            Run::class,
            Link::class,
        ];
    }
}