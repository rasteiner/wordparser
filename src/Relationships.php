<?php 

namespace rasteiner\Wordparser;

use ArrayAccess;
use SimpleXMLElement;

class Relationships implements ArrayAccess {
    protected readonly array $relationships;

    public function __construct(
        public readonly SimpleXMLElement $documentRels,
        public readonly Parser $parser,
    )
    {
        $relationships = [];

        foreach($this->documentRels->Relationship as $rel) {
            $id = $rel['Id'] ?? null;
            if($id === null) continue;

            $relationships[(string) $id] = Relationship::parse($rel, $id, $parser);
        }

        $this->relationships = $relationships;
    }

    public function offsetExists(mixed $offset): bool {
        return isset($this->relationships[$offset]);
    }

    public function offsetGet(mixed $offset): mixed {
        return $this->relationships[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void {
        throw new \Exception('Not implemented');
    }

    public function offsetUnset(mixed $offset): void {
        throw new \Exception('Not implemented');
    }

    public function get(string $id): ?Relationship {
        return $this->relationships[$id] ?? null;
    }
}