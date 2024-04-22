<?php 

namespace rasteiner\Wordparser\nodes;

use rasteiner\Wordparser\HasStyle;
use rasteiner\Wordparser\ListInstance;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Style;
use SimpleXMLElement;
use Traversable;

class Heading extends Paragraph {
    public function __construct(
        public int $level,
        Traversable $children,
        Style $paragraphStyle,
        ListInstance $list = null,
        ?int $listLevel = null,
    ) {
        parent::__construct($children, $paragraphStyle, $list, $listLevel);
    }

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static
    {
        $p = Paragraph::parse($xml, $parser);
        if($p === null) return null;
        
        // does the paragraph have a style with an id?
        $id = $p->paragraphStyle->id;
        if($id === null) return null;
        
        $styleName = $parser->stylesheet->getName($id);
        if($styleName === null) return null;
        
        // is it a heading?
        if($styleName === 'Title') {
            $level = 1;
        } else {
            preg_match('/heading (\d+)/i', $styleName, $matches);
            if(count($matches) === 0) return null;
            $level = (int) $matches[1] + 1;
        }

        // it is a heading!
        return new static(
            $level,
            $parser->matchChildren($xml, static::contains()),
            $parser->stylesheet->get($id),
            $p->list,
            $p->listLevel,
        );
    }
}