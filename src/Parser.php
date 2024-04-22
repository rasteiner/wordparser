<?php 

namespace rasteiner\Wordparser;

use Countable;
use Exception;
use Iterator;
use IteratorAggregate;
use SimpleXMLElement;
use Traversable;
use ZipArchive;

class Parser implements IteratorAggregate {
    public readonly Stylesheet $stylesheet;
    public readonly Footnotes $footnotes;
    public readonly Numbering $numbering;
    public readonly SimpleXMLElement $document;
    
    private array $unexpetedNodes = [];

    /**
     * @var array<string, ?Relationships>
     */
    public array $relationships = [];

    public string $defaultRelType = 'document';    

    public function __construct(public readonly string $path) {
        // read main files in zip
        $zip = new ZipArchive();
        $zip->open($this->path);
        
        $document = $zip->getFromName('word/document.xml');
        $styles = $zip->getFromName('word/styles.xml');
        $footnotes = $zip->getFromName('word/footnotes.xml');
        $numbering = $zip->getFromName('word/numbering.xml');
        $zip->close();

        if($document === false) {
            throw new Exception("Could not read document.xml from zip file.");
        }

        if($styles === false) {
            throw new Exception("Could not read styles.xml from zip file.");    
        }
        

        $this->document = new SimpleXMLElement($document);

        // load styles
        $this->stylesheet = new Stylesheet(new SimpleXMLElement($styles));

        // load numbering
        if($numbering !== false) {
            $this->numbering = new Numbering(new SimpleXMLElement($numbering));
        }

        // load footnotes
        if($footnotes !== false) {
            $this->footnotes = new Footnotes(new SimpleXMLElement($footnotes));
        }
    }

    /**
     * 
     * @return Traversable<int, Node>
     */
    public function getIterator(): Traversable {
        yield from $this->matchChildren($this->document, [
            nodes\Body::class
        ]);
    }


    /**
     * Returns the Node corresponding to the first Schema that parses the given element.
     * 
     * @param SimpleXMLElement $el
     * @param string[] $schemas
     */
    public function matchElement(SimpleXMLElement $el, array $schemas): ?Node {
        foreach ($schemas as $schema) {
            $node = $schema::parse($el, $this);
            
            if($node !== null) {
                return $node;
            }
        }

        return null;
    }

    /**
     * Parses all children of the given element with the given allowed schemas.
     * 
     * @param SimpleXMLElement $el 
     * @param string[] $schemas 
     * @return Traversable<int, Node>
     */
    public function matchChildren(SimpleXMLElement $el, array $schemas): Traversable {
        foreach (Xml::children($el) as $child) {
            $node = $this->matchElement($child, $schemas);
            if($node !== null) {
                yield $node;
            } else {
                if(!in_array($child->getName(), $this->unexpetedNodes)) {
                    $this->unexpetedNodes[] = $child->getName();
                }
            }
        }
    }

    public function getUnexpetedNodes(): array {
        return $this->unexpetedNodes;
    }

    protected function relationships(string $type): ?Relationships {
        if(($this->relationships[$type] ?? false) === false) {
            $zip = new ZipArchive();
            $zip->open($this->path);
            $rels = $zip->getFromName('word/_rels/' . $type . '.xml.rels');
            $zip->close();

            if($rels === false) {
                $this->relationships[$type] = null;
            } else {
                $this->relationships[$type] = new Relationships(new SimpleXMLElement($rels), $this);
            }
        }

        return $this->relationships[$type];
    }

    public function getRelation(string $id, string $relType = null): ?Relationship {
        if($relType === null) {
            $relType = $this->defaultRelType;
        }

        $rels = $this->relationships($relType);
        if($rels === null) return null;

        return $rels->get($id);
    }


    public function getMedia(string $embedId, string $reltype = null): ?Relationship {
        $rel = $this->getRelation($embedId, $reltype);

        // is this internal? 
        if($rel?->targetMode !== 'Internal') {
            return null;
        }

        // is this a media file (image)?
        if($rel->shortType !== 'image') {
            return null;
        }

        return $rel;        
    }
}