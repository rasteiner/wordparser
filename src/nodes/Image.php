<?php

namespace rasteiner\Wordparser\nodes;

use rasteiner\Wordparser\Node;
use SimpleXMLElement;
use rasteiner\Wordparser\Parser;
use rasteiner\Wordparser\Relationship;
use rasteiner\Wordparser\Xml;

class Image extends Node {
    protected ?array $info = null;

    /**
     * Constructs a new Image node.
     * @param string $name The filename of the image.
     * @param Relationship $media The relationship to the image.
     * @return void 
     */
    public function __construct(public string $name, public Relationship $rel) {
    }

    public function getDataUri(): string {
        $target = $this->rel->target;
        $media = $this->rel->loadMedia();

        // get type
        $type = match(pathinfo($target, PATHINFO_EXTENSION)) {
            'png' => 'image/png',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            default => 'application/octet-stream'
        };

        return 'data:' . $type . ';base64,' . base64_encode($media);
    }

    public function info() {
        return $this->info ??= getimagesizefromstring($this->rel->loadMedia());
    }

    public static function parse(SimpleXMLElement $xml, Parser $parser): ?static {
        // is drawing? 
        if(Xml::is($xml, 'drawing')) {
            // find image
            $xml->registerXPathNamespace('wp', 'http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing');
            $xml->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
            $xml->registerXPathNamespace('pic', 'http://schemas.openxmlformats.org/drawingml/2006/picture');
            $xml->registerXPathNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');

            $name = $xml->xpath('(wp:inline|wp:anchor)/a:graphic/a:graphicData/pic:pic/pic:nvPicPr/pic:cNvPr/@name');
            $id = $xml->xpath('(wp:inline|wp:anchor)/a:graphic/a:graphicData/pic:pic/pic:blipFill/a:blip/@r:embed');

            // count to check validity
            if(count($name) !== 1 || count($id) !== 1) return null;

            $name = (string) $name[0];
            $id = (string) $id[0];

            $rel = $parser->getMedia($id);
            if($rel === null) return null;

            return new static($name, $rel);
        } elseif(Xml::is($xml, 'pict')) {
            $id = $xml->xpath('v:shape/v:imagedata/@r:id');
            
        }

        return null;
    }
    

    
}