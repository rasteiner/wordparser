<?php 

namespace rasteiner\Wordparser;

use SimpleXMLElement;
use ZipArchive;

class Relationship {
    protected ?string $media = null;

    public function __construct(
        
        public readonly string $id,
        public readonly string $targetMode,
        public readonly string $type,
        public readonly string $shortType,
        public readonly string $target,

        public readonly Parser $parser
        
    )
    {
        
    }

    public function loadMedia(): ?string {
        if($this->media !== null) {
            return $this->media;
        }

        $zip = new ZipArchive();
        $zip->open($this->parser->path);
        $this->media = $zip->getFromName('word/' . $this->target);
        $zip->close();

        return $this->media;
    }

    
    public static function parse(SimpleXMLElement $xml, string $id, Parser $parser): self {
        $targetMode = $xml['TargetMode'] ?? 'Internal';
        $type = $xml['Type'] ?? null;
        $shortType = match(dirname($type ?? '')) {
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships' => basename($type),
            default => null
        };            
        $target = $xml['Target'] ?? null;

        return new self(
            $id,
            $targetMode,
            $type,
            $shortType,
            $target,
            $parser
        );
    }
}